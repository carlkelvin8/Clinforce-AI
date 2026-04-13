<?php

namespace App\Http\Controllers\Api;

use App\Models\CredentialVerification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CredentialVerificationController extends ApiController
{
    /**
     * GET /credentials
     * List credentials for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        $query = CredentialVerification::query();

        // Admin/employer: can query any user's credentials
        if ($request->has('user_id')) {
            if (!in_array($u->role, ['admin', 'employer', 'agency'], true)) {
                return $this->fail('Forbidden', null, 403);
            }
            $query->where('applicant_user_id', $request->user_id);
        } else {
            // Candidates see their own
            $query->where('applicant_user_id', $u->id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->credential_type) {
            $query->where('credential_type', $request->credential_type);
        }

        $credentials = $query->with(['applicant', 'requestedBy'])
            ->orderByDesc('created_at')
            ->get();

        return $this->ok($credentials);
    }

    /**
     * POST /credentials
     * Submit a credential for verification (candidate).
     */
    public function store(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        if ($u->role !== 'applicant') {
            return $this->fail('Only candidates can submit credentials', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'credential_type' => 'required|string|max:255',
            'license_number' => 'required|string|max:100',
            'issuing_authority' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'state_province' => 'nullable|string|max:100',
            'issued_date' => 'nullable|date|before:today',
            'expiry_date' => 'nullable|date|after:today',
            'verification_url' => 'nullable|url|max:500',
            'document_url' => 'nullable|string|max:2000',
            'method' => 'nullable|in:manual,api,document_review',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', $validator->errors(), 422);
        }

        $validated = $validator->validated();

        // Check for duplicate (same user + same credential type + same license number)
        $duplicate = CredentialVerification::where('applicant_user_id', $u->id)
            ->where('credential_type', $validated['credential_type'])
            ->where('license_number', $validated['license_number'])
            ->whereIn('status', ['pending', 'verified'])
            ->first();

        if ($duplicate) {
            return $this->fail('This credential is already being tracked', null, 409);
        }

        $credential = CredentialVerification::create([
            'applicant_user_id' => $u->id,
            'requested_by_user_id' => null,
            'credential_type' => $validated['credential_type'],
            'license_number' => $validated['license_number'],
            'issuing_authority' => $validated['issuing_authority'] ?? null,
            'country' => $validated['country'] ?? null,
            'state_province' => $validated['state_province'] ?? null,
            'issued_date' => $validated['issued_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'verification_url' => $validated['verification_url'] ?? null,
            'method' => $validated['method'] ?? 'manual',
            'status' => 'pending',
            'document_url' => $validated['document_url'] ?? null,
        ]);

        // Auto-verify if verification URL is provided and we can reach it
        if ($credential->verification_url && $credential->method === 'api') {
            try {
                $this->autoVerify($credential);
            } catch (\Throwable $e) {
                \Log::info('Auto-verification failed, falling back to manual', [
                    'credential_id' => $credential->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->ok($credential, 'Credential submitted for verification', 201);
    }

    /**
     * GET /credentials/{credential}
     */
    public function show(CredentialVerification $credential): JsonResponse
    {
        $u = $this->requireAuth();

        $credential->load(['applicant', 'requestedBy']);

        $isOwner = $credential->applicant_user_id === $u->id;
        $isAdmin = $u->role === 'admin';
        $isEmployer = in_array($u->role, ['employer', 'agency'], true);

        if (!$isOwner && !$isAdmin && !$isEmployer) {
            return $this->fail('Forbidden', null, 403);
        }

        return $this->ok($credential);
    }

    /**
     * PUT /credentials/{credential}/verify
     * Mark credential as verified (admin/employer only).
     */
    public function verify(Request $request, CredentialVerification $credential): JsonResponse
    {
        $u = $this->requireAuth();

        if (!in_array($u->role, ['admin', 'employer', 'agency'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'verification_data' => 'nullable|array',
            'notes' => 'nullable|string|max:2000',
            'status' => 'sometimes|in:verified,invalid,mismatch',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', $validator->errors(), 422);
        }

        $status = $request->status ?? 'verified';

        if ($status === 'verified') {
            $credential->markVerified([
                'verification_data' => $request->verification_data ?? $credential->verification_data,
                'notes' => $request->notes ?? $credential->notes,
                'requested_by_user_id' => $u->id,
            ]);

            // Sync to applicant profile certifications
            $this->syncToProfile($credential);
        } elseif ($status === 'invalid' || $status === 'mismatch') {
            $credential->markInvalid($request->notes ?? 'Verification failed');
        }

        return $this->ok($credential, 'Credential status updated');
    }

    /**
     * POST /credentials/{credential}/recheck
     * Re-verify an existing credential.
     */
    public function recheck(CredentialVerification $credential): JsonResponse
    {
        $u = $this->requireAuth();

        if (!in_array($u->role, ['admin', 'employer', 'agency'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $credential->update([
            'status' => 'pending',
            'verified_at' => null,
            'last_checked_at' => now(),
            'requested_by_user_id' => $u->id,
        ]);

        if ($credential->verification_url && $credential->method === 'api') {
            try {
                $this->autoVerify($credential);
            } catch (\Throwable $e) {
                \Log::warning('Auto-recheck failed', [
                    'credential_id' => $credential->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->ok($credential, 'Credential recheck initiated');
    }

    /**
     * GET /credentials/expiring
     * Get credentials expiring soon (admin/employer).
     */
    public function expiring(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        if (!in_array($u->role, ['admin', 'employer', 'agency'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $days = $request->input('days', 30);
        $query = CredentialVerification::expiringSoon($days)
            ->with(['applicant']);

        if ($request->user_id) {
            $query->where('applicant_user_id', $request->user_id);
        }

        $credentials = $query->orderBy('expiry_date')->get();

        return $this->ok($credentials);
    }

    /**
     * GET /credentials/types
     * Get common healthcare credential types.
     */
    public function types(): JsonResponse
    {
        return $this->ok(CredentialVerification::commonTypes());
    }

    /**
     * GET /admin/credentials
     * Admin: list all credentials.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        if ($u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        $query = CredentialVerification::with(['applicant', 'requestedBy']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->credential_type) {
            $query->where('credential_type', $request->credential_type);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('license_number', 'like', "%{$search}%")
                    ->orWhere('credential_type', 'like', "%{$search}%");
            });
        }

        $credentials = $query->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return $this->ok($credentials);
    }

    // ── Auto-verification ──

    /**
     * Attempt to auto-verify a credential via external API.
     * This is a placeholder — implement actual verification for your jurisdiction.
     */
    private function autoVerify(CredentialVerification $credential): void
    {
        // TODO: Implement actual license verification APIs
        // Examples:
        // - PRC (Philippines): https://online.prc.gov.ph/ — scrape or API
        // - NURSYS (US): https://www.nursys.com/ — license verification
        // - GMC (UK): https://www.gmc-uk.org/ — register check

        // Placeholder: if verification URL is set and reachable, mark as verified
        // In production, you'd parse the response and validate the license number

        \Log::info('Credential auto-verification attempted', [
            'credential_id' => $credential->id,
            'type' => $credential->credential_type,
            'license' => $credential->license_number,
        ]);

        // For now, leave as pending for manual review
        // Uncomment below when you have a working verification API:
        //
        // $response = Http::timeout(10)->get($credential->verification_url, [
        //     'license_number' => $credential->license_number,
        // ]);
        //
        // if ($response->successful() && $this->parseVerificationResponse($response)) {
        //     $credential->markVerified([
        //         'verification_data' => $response->json(),
        //         'method' => 'api',
        //     ]);
        // }
    }

    /**
     * Sync verified credential to applicant profile's certifications JSON.
     */
    private function syncToProfile(CredentialVerification $credential): void
    {
        $profile = $credential->applicant->applicantProfile;
        if (!$profile) {
            return;
        }

        $certs = $profile->certifications ?? [];

        // Check if already exists
        $exists = false;
        foreach ($certs as &$cert) {
            if (($cert['license_number'] ?? '') === $credential->license_number
                && ($cert['type'] ?? '') === $credential->credential_type
            ) {
                $cert['verified'] = true;
                $cert['verified_at'] = now()->toIso8601String();
                $cert['expiry_date'] = $credential->expiry_date?->toIso8601String();
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $certs[] = [
                'type' => $credential->credential_type,
                'license_number' => $credential->license_number,
                'issuing_authority' => $credential->issuing_authority,
                'issued_date' => $credential->issued_date?->toIso8601String(),
                'expiry_date' => $credential->expiry_date?->toIso8601String(),
                'verified' => true,
                'verified_at' => now()->toIso8601String(),
            ];
        }

        $profile->certifications = $certs;
        $profile->save();
    }
}
