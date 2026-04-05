<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CandidatesController extends ApiController
{
    /**
     * List candidates (preview mode for non-subscribers)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->requireAuth();

        if ($user->role !== 'employer') {
            return $this->fail('Only employers can view candidates', null, 403);
        }

        // Get candidates who applied to employer's jobs
        $candidateIds = JobApplication::query()
            ->join('jobs', 'job_applications.job_id', '=', 'jobs.id')
            ->where('jobs.owner_user_id', $user->id)
            ->distinct()
            ->pluck('job_applications.applicant_user_id');

        $hasSubscription = $user->subscription !== null;

        $candidates = User::query()
            ->whereIn('id', $candidateIds)
            ->where('role', 'applicant')
            ->with('applicantProfile')
            ->get()
            ->map(function ($candidate) use ($hasSubscription) {
                return $this->formatCandidate($candidate, $hasSubscription);
            });

        return $this->ok([
            'candidates' => $candidates,
            'has_subscription' => $hasSubscription,
        ]);
    }

    /**
     * Show full candidate details (requires subscription + document access)
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $this->requireAuth();

        if ($user->role !== 'employer') {
            return $this->fail('Only employers can view candidates', null, 403);
        }

        // IDOR Protection: Check if candidate applied to employer's job
        $hasApplied = JobApplication::query()
            ->join('jobs', 'job_applications.job_id', '=', 'jobs.id')
            ->where('jobs.owner_user_id', $user->id)
            ->where('job_applications.applicant_user_id', $id)
            ->exists();

        if (!$hasApplied) {
            return $this->fail('Candidate not found or not accessible', null, 403);
        }

        $candidate = User::with(['applicantProfile', 'documents'])
            ->where('id', $id)
            ->where('role', 'applicant')
            ->first();

        if (!$candidate) {
            return $this->fail('Candidate not found', null, 404);
        }

        // Check subscription (required for basic access)
        $hasSubscription = $user->subscription && $user->subscription->status === 'active';
        
        // Check document access payment (required for resume/documents)
        $hasDocAccess = \App\Models\DocumentAccessPayment::hasAccess($user->id, $id);

        // Log access
        $this->logAccess($user->id, $candidate->id, 'view_candidate', $request);

        return $this->ok([
            'candidate' => $this->formatCandidateFull($candidate, $hasDocAccess),
            'access_status' => [
                'has_subscription' => $hasSubscription,
                'has_document_access' => $hasDocAccess,
                'subscription_status' => $user->subscription->status ?? null,
            ],
        ]);
    }

    /**
     * Download candidate resume (requires subscription)
     */
    public function downloadResume(Request $request, int $id): mixed
    {
        $user = $this->requireAuth();

        if ($user->role !== 'employer') {
            return $this->fail('Only employers can download resumes', null, 403);
        }

        // IDOR Protection
        $hasApplied = JobApplication::query()
            ->join('jobs', 'job_applications.job_id', '=', 'jobs.id')
            ->where('jobs.owner_user_id', $user->id)
            ->where('job_applications.applicant_user_id', $id)
            ->exists();

        if (!$hasApplied) {
            return response()->json([
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'Candidate not found or not accessible',
            ], 403);
        }

        $candidate = User::find($id);
        if (!$candidate) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate not found',
            ], 404);
        }

        // Find resume document
        $resume = $candidate->documents()
            ->where('doc_type', 'resume')
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$resume) {
            return response()->json([
                'success' => false,
                'message' => 'Resume not found',
            ], 404);
        }

        // Log download
        $this->logAccess($user->id, $candidate->id, 'download_resume', $request);

        // Serve file securely
        $filePath = $resume->file_url;
        
        // If it's a full URL, we need to extract the relative path after /storage/
        if (str_contains($filePath, '/storage/')) {
            $pos = strpos($filePath, '/storage/');
            $filePath = substr($filePath, $pos + 9); // length of "/storage/"
        } else {
            // Remove leading slash and public prefix if present
            $filePath = ltrim($filePath, '/');
            if (str_starts_with($filePath, 'public/')) {
                $filePath = substr($filePath, 7);
            }
        }

        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Resume file not found',
            ], 404);
        }

        $fullPath = Storage::disk('public')->path($filePath);
        $disposition = request()->query('download') ? 'attachment' : 'inline';

        return response()->file($fullPath, [
            'Content-Type' => $resume->mime_type ?: 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $resume->file_name . '"'
        ]);
    }

    /**
     * Format candidate for preview (masked data)
     */
    protected function formatCandidate(User $candidate, bool $hasSubscription): array
    {
        $profile = $candidate->applicantProfile;

        if (!$hasSubscription) {
            // Preview mode - masked data
            $firstName = $profile->first_name ?? '';
            $lastName = $profile->last_name ?? '';
            
            return [
                'id' => $candidate->id,
                'locked' => true,
                'initials' => $this->maskName($firstName, $lastName),
                'headline' => $profile->headline ?? 'Healthcare Professional',
                'location' => $this->maskLocation($profile->city, $profile->state),
                'avatar_url' => $candidate->avatar_url,
                'years_experience' => $profile->years_experience ?? null,
            ];
        }

        // Full preview for subscribers
        return [
            'id' => $candidate->id,
            'locked' => false,
            'first_name' => $profile->first_name ?? '',
            'last_name' => $profile->last_name ?? '',
            'full_name' => trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')),
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'headline' => $profile->headline ?? '',
            'location' => trim(($profile->city ?? '') . ', ' . ($profile->state ?? '')),
            'avatar_url' => $candidate->avatar_url,
            'years_experience' => $profile->years_experience ?? null,
        ];
    }

    /**
     * Format full candidate details
     */
    protected function formatCandidateFull(User $candidate, bool $hasDocAccess = false): array
    {
        $profile = $candidate->applicantProfile;

        $data = [
            'id' => $candidate->id,
            'locked' => !$hasDocAccess,
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'first_name' => $profile->first_name ?? '',
            'last_name' => $profile->last_name ?? '',
            'full_name' => trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')),
            'headline' => $profile->headline ?? '',
            'bio' => $profile->bio ?? '',
            'city' => $profile->city ?? '',
            'state' => $profile->state ?? '',
            'country' => $profile->country ?? '',
            'years_experience' => $profile->years_experience ?? null,
            'specialization' => $profile->specialization ?? '',
            'avatar_url' => $candidate->avatar_url,
        ];

        // Only show sensitive documents if document access is paid
        if ($hasDocAccess) {
            $data['license_number'] = $profile->license_number ?? '';
            $data['education'] = $profile->education ?? '';
            $data['certifications'] = $profile->certifications ?? '';
            $data['skills'] = $profile->skills ?? '';
            $data['documents'] = $candidate->documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'doc_type' => $doc->doc_type,
                    'file_name' => $doc->file_name,
                    'mime_type' => $doc->mime_type,
                    'file_size_bytes' => $doc->file_size_bytes,
                    'created_at' => $doc->created_at->toIso8601String(),
                ];
            });
        } else {
            $data['license_number'] = '🔒 Locked - Purchase document access';
            $data['education'] = '🔒 Locked - Purchase document access';
            $data['certifications'] = '🔒 Locked - Purchase document access';
            $data['skills'] = '🔒 Locked - Purchase document access';
            $data['documents'] = [
                ['locked' => true, 'message' => 'Purchase document access to view resume and attachments']
            ];
        }

        return $data;
    }

    /**
     * Mask name for preview
     */
    protected function maskName(string $firstName, string $lastName): string
    {
        $first = $firstName ? substr($firstName, 0, 1) . str_repeat('*', max(0, strlen($firstName) - 1)) : '';
        $last = $lastName ? substr($lastName, 0, 1) . str_repeat('*', max(0, strlen($lastName) - 1)) : '';
        
        return trim($first . ' ' . $last);
    }

    /**
     * Mask location for preview
     */
    protected function maskLocation(?string $city, ?string $state): string
    {
        if (!$city && !$state) {
            return 'Location Hidden';
        }

        return trim(($city ?? 'City') . ', ' . ($state ?? 'State'));
    }

    /**
     * Log access for audit trail
     */
    protected function logAccess(int $employerId, int $candidateId, string $action, Request $request): void
    {
        try {
            \DB::table('access_logs')->insert([
                'employer_id' => $employerId,
                'candidate_user_id' => $candidateId,
                'action' => $action,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log access: ' . $e->getMessage());
        }
    }
}
