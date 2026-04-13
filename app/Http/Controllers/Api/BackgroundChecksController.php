<?php

namespace App\Http\Controllers\Api;

use App\Models\BackgroundCheck;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BackgroundChecksController extends ApiController
{
    /**
     * GET /applications/{application}/background-checks
     */
    public function index(JobApplication $application): JsonResponse
    {
        $u = $this->requireAuth();

        $application->load('job');
        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $application->job?->owner_user_id === $u->id
            && $application->job?->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $checks = $application->backgroundChecks()
            ->with(['requestedBy', 'assignedTo'])
            ->orderByDesc('created_at')
            ->get();

        return $this->ok($checks);
    }

    /**
     * POST /applications/{application}/background-checks
     * Initiate a background check.
     */
    public function store(Request $request, JobApplication $application): JsonResponse
    {
        $u = $this->requireAuth();

        $application->load('job');
        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $application->job?->owner_user_id === $u->id
            && $application->job?->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:criminal,employment,education,drug,credit,comprehensive,manual',
            'provider' => 'nullable|string|max:100',
            'assigned_to_user_id' => 'nullable|integer|exists:users,id',
            'expires_at' => 'nullable|date|after:now',
            'candidate_consent' => 'required|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', $validator->errors(), 422);
        }

        if (!$request->boolean('candidate_consent')) {
            return $this->fail('Candidate consent is required for background checks', null, 400);
        }

        $validated = $validator->validated();
        $provider = $validated['provider'] ?? 'manual';

        $check = BackgroundCheck::create([
            'application_id' => $application->id,
            'requested_by_user_id' => $u->id,
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'provider' => $provider,
            'type' => $validated['type'],
            'status' => 'pending',
            'candidate_consent' => true,
            'consent_given_at' => now(),
            'expires_at' => $validated['expires_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // If using an external provider, attempt to initiate
        if ($provider !== 'manual') {
            try {
                $this->initiateProviderCheck($check);
            } catch (\Throwable $e) {
                \Log::warning('Background check provider initiation failed', [
                    'check_id' => $check->id,
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ]);
                // Keep as pending for manual processing
            }
        }

        return $this->ok($check, 'Background check initiated', 201);
    }

    /**
     * GET /background-checks/{backgroundCheck}
     */
    public function show(BackgroundCheck $backgroundCheck): JsonResponse
    {
        $u = $this->requireAuth();

        $backgroundCheck->load(['application.job', 'requestedBy', 'assignedTo']);

        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $backgroundCheck->application?->job?->owner_user_id === $u->id
            && $backgroundCheck->application?->job?->owner_type === $u->role;

        $isCandidate = $backgroundCheck->application?->applicant_user_id === $u->id;

        if ($u->role !== 'admin' && !$isOwner && !$isCandidate) {
            return $this->fail('Forbidden', null, 403);
        }

        // Candidates see limited info
        if ($isCandidate && $u->role !== 'admin') {
            return $this->ok([
                'id' => $backgroundCheck->id,
                'type' => $backgroundCheck->type,
                'status' => $backgroundCheck->status,
                'result' => $backgroundCheck->result,
                'summary' => $backgroundCheck->summary,
                'candidate_consent' => $backgroundCheck->candidate_consent,
                'consent_given_at' => $backgroundCheck->consent_given_at,
                'completed_at' => $backgroundCheck->completed_at,
            ]);
        }

        return $this->ok($backgroundCheck);
    }

    /**
     * PUT /background-checks/{backgroundCheck}
     * Update status/results (employer/admin only).
     */
    public function update(Request $request, BackgroundCheck $backgroundCheck): JsonResponse
    {
        $u = $this->requireAuth();

        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $backgroundCheck->application?->job?->owner_user_id === $u->id
            && $backgroundCheck->application?->job?->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,initiated,in_progress,completed,flagged,cancelled',
            'result' => 'nullable|in:clear,flagged,inconclusive,failed',
            'report_data' => 'nullable|array',
            'summary' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:2000',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', $validator->errors(), 422);
        }

        $validated = $validator->validated();

        if (isset($validated['status'])) {
            if ($validated['status'] === 'completed' || $validated['status'] === 'flagged') {
                $validated['completed_at'] = now();
            }
            if ($validated['status'] === 'initiated') {
                $validated['initiated_at'] = now();
            }
        }

        $backgroundCheck->update($validated);

        return $this->ok($backgroundCheck, 'Background check updated');
    }

    /**
     * POST /background-checks/{backgroundCheck}/consent
     * Candidate gives consent for background check.
     */
    public function giveConsent(Request $request, BackgroundCheck $backgroundCheck): JsonResponse
    {
        $u = $this->requireAuth();

        if ($backgroundCheck->application?->applicant_user_id !== $u->id) {
            return $this->fail('Forbidden', null, 403);
        }

        $backgroundCheck->update([
            'candidate_consent' => true,
            'consent_given_at' => now(),
        ]);

        return $this->ok($backgroundCheck, 'Consent recorded');
    }

    /**
     * POST /background-checks/{backgroundCheck}/cancel
     */
    public function cancel(BackgroundCheck $backgroundCheck): JsonResponse
    {
        $u = $this->requireAuth();

        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $backgroundCheck->application?->job?->owner_user_id === $u->id
            && $backgroundCheck->application?->job?->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        if (in_array($backgroundCheck->status, ['completed', 'cancelled'], true)) {
            return $this->fail('Cannot cancel a completed/cancelled check', null, 409);
        }

        $backgroundCheck->update(['status' => 'cancelled']);

        return $this->ok($backgroundCheck, 'Background check cancelled');
    }

    /**
     * GET /background-checks/providers
     * List available providers.
     */
    public function providers(): JsonResponse
    {
        return $this->ok([
            'providers' => BackgroundCheck::availableProviders(),
            'types' => BackgroundCheck::availableTypes(),
        ]);
    }

    /**
     * GET /admin/background-checks
     * Admin: list all background checks.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        if ($u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        $query = BackgroundCheck::with(['application.job', 'requestedBy', 'assignedTo']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->provider) {
            $query->where('provider', $request->provider);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }

        $checks = $query->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return $this->ok($checks);
    }

    // ── Provider Integration ──

    /**
     * Initiate check with external provider.
     * This is a placeholder — implement actual API calls for your chosen provider.
     */
    private function initiateProviderCheck(BackgroundCheck $check): void
    {
        $provider = $check->provider;
        $apiKey = config('services.background_check.api_key');

        if (!$apiKey) {
            throw new \Exception("Background check provider '{$provider}' not configured");
        }

        // TODO: Implement actual provider API integration
        // Example structure for Checkr:
        //
        // $response = Http::withToken($apiKey)
        //     ->post('https://api.checkr.com/v1/reports', [
        //         'package' => $this->mapTypeToPackage($check->type),
        //         'candidate' => [
        //             'first_name' => $check->application->applicant->first_name,
        //             'last_name' => $check->application->applicant->last_name,
        //             'email' => $check->application->applicant->email,
        //         ],
        //     ]);
        //
        // $check->update([
        //     'status' => 'initiated',
        //     'initiated_at' => now(),
        //     'provider_reference_id' => $response->json('id'),
        //     'report_data' => $response->json(),
        // ]);

        \Log::info('Background check provider initiated', [
            'check_id' => $check->id,
            'provider' => $provider,
            'type' => $check->type,
        ]);
    }

    /**
     * Handle webhook from external provider.
     * POST /background-checks/webhook/{provider}
     */
    public function webhook(Request $request, string $provider): JsonResponse
    {
        $payload = $request->all();

        \Log::info("Background check webhook received from {$provider}", $payload);

        // TODO: Process webhook payload and update check status
        // Example for Checkr:
        //
        // if ($payload['object'] === 'report' && isset($payload['id'])) {
        //     $check = BackgroundCheck::where('provider_reference_id', $payload['id'])->first();
        //     if ($check) {
        //         $check->update([
        //             'status' => $this->mapCheckrStatus($payload['status']),
        //             'result' => $this->mapCheckrResult($payload['status']),
        //             'report_data' => $payload,
        //             'completed_at' => $payload['status'] === 'clear' ? now() : null,
        //         ]);
        //     }
        // }

        return response()->json(['received' => true]);
    }
}
