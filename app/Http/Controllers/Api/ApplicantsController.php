<?php

namespace App\Http\Controllers\Api;

use App\Models\JobApplication;
use App\Models\ApplicantProfile;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicantsController extends ApiController
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * GET /applicants - List applicants (preview mode for employers without subscription)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->requireAuth();

        // Build query for applicants
        $query = User::where('role', 'applicant')
            ->with('applicantProfile')
            ->where('status', 'active');

        // Filter by job applications if employer (only show applicants who applied to their jobs)
        if ($user->role === 'employer') {
            // Get applicant IDs who applied to this employer's jobs
            $applicantIds = JobApplication::query()
                ->join('jobs', 'job_applications.job_id', '=', 'jobs.id')
                ->where('jobs.owner_user_id', $user->id)
                ->where('jobs.owner_type', 'employer')
                ->distinct()
                ->pluck('job_applications.applicant_user_id');

            \Log::info('Applicants query debug', [
                'employer_id' => $user->id,
                'applicant_ids' => $applicantIds->toArray(),
                'count' => $applicantIds->count(),
                'sql' => JobApplication::query()
                    ->join('jobs', 'job_applications.job_id', '=', 'jobs.id')
                    ->where('jobs.owner_user_id', $user->id)
                    ->where('jobs.owner_type', 'employer')
                    ->toSql(),
            ]);

            if ($applicantIds->isEmpty()) {
                // No applicants yet
                return $this->ok([
                    'applicants' => [
                        'data' => [],
                        'total' => 0,
                    ],
                    'message' => 'No applicants have applied to your jobs yet.',
                    'debug' => [
                        'employer_id' => $user->id,
                        'total_jobs' => \App\Models\Job::where('owner_user_id', $user->id)->count(),
                        'total_applications' => JobApplication::query()
                            ->join('jobs', 'job_applications.job_id', '=', 'jobs.id')
                            ->where('jobs.owner_user_id', $user->id)
                            ->count(),
                    ],
                    'subscription_status' => $this->subscriptionService->getSubscriptionStatus($user->id),
                ]);
            }

            $query->whereIn('id', $applicantIds);
        }

        $applicants = $query->paginate(20);

        // Check subscription status
        $hasSubscription = $this->subscriptionService->hasActiveSubscription($user->id);

        // Transform data based on subscription
        $applicants->getCollection()->transform(function ($applicant) use ($hasSubscription, $user) {
            return $this->transformApplicantPreview($applicant, $hasSubscription, $user);
        });

        return $this->ok([
            'applicants' => $applicants,
            'subscription_status' => $this->subscriptionService->getSubscriptionStatus($user->id),
        ]);
    }

    /**
     * GET /applicants/{userId} - View full applicant details (requires subscription for employers)
     */
    public function show(Request $request, int $userId): JsonResponse
    {
        $user = $this->requireAuth();
        
        $applicant = User::where('id', $userId)
            ->where('role', 'applicant')
            ->with(['applicantProfile', 'documents'])
            ->firstOrFail();

        // Check if user can access full details
        if ($user->role === 'employer') {
            if (!$this->subscriptionService->hasActiveSubscription($user->id)) {
                return response()->json([
                    'error' => 'subscription_required',
                    'message' => 'Subscribe to unlock applicant profiles and resumes.',
                    'locked' => true,
                    'subscription_status' => $this->subscriptionService->getSubscriptionStatus($user->id),
                    'preview' => $this->transformApplicantPreview($applicant, false, $user),
                ], 403);
            }
        }

        // Return full details
        return $this->ok([
            'applicant' => $this->transformApplicantFull($applicant),
            'subscription_status' => $this->subscriptionService->getSubscriptionStatus($user->id),
        ]);
    }

    /**
     * GET /applications/{applicationId}/applicant - View applicant from application
     */
    public function showFromApplication(Request $request, int $applicationId): JsonResponse
    {
        $user = $this->requireAuth();
        
        $application = JobApplication::with(['applicant.applicantProfile', 'applicant.documents', 'job'])
            ->findOrFail($applicationId);

        // Verify access
        if ($user->role === 'employer') {
            if ($application->job->owner_user_id !== $user->id) {
                return $this->fail('Forbidden', null, 403);
            }

            // Check subscription
            if (!$this->subscriptionService->hasActiveSubscription($user->id)) {
                return response()->json([
                    'error' => 'subscription_required',
                    'message' => 'Subscribe to unlock applicant profiles and resumes.',
                    'locked' => true,
                    'subscription_status' => $this->subscriptionService->getSubscriptionStatus($user->id),
                    'preview' => $this->transformApplicantPreview($application->applicant, false, $user),
                ], 403);
            }
        }

        return $this->ok([
            'applicant' => $this->transformApplicantFull($application->applicant),
            'application' => $application,
            'subscription_status' => $this->subscriptionService->getSubscriptionStatus($user->id),
        ]);
    }

    /**
     * Transform applicant data for preview (limited info)
     */
    protected function transformApplicantPreview($applicant, bool $hasSubscription, $viewer): array
    {
        $profile = $applicant->applicantProfile;

        if (!$profile) {
            return [
                'id' => $applicant->id,
                'locked' => !$hasSubscription,
                'initials' => 'N/A',
            ];
        }

        // If has subscription or viewer is not employer, show full data
        if ($hasSubscription || $viewer->role !== 'employer') {
            return $this->transformApplicantFull($applicant);
        }

        // Limited preview for employers without subscription
        $firstName = $profile->first_name ?? '';
        $lastName = $profile->last_name ?? '';
        
        $initials = '';
        if ($firstName) {
            $initials .= strtoupper(substr($firstName, 0, 1)) . '***';
        }
        if ($lastName) {
            $initials .= ' ' . strtoupper(substr($lastName, 0, 1)) . '***';
        }

        return [
            'id' => $applicant->id,
            'locked' => true,
            'initials' => $initials ?: 'Anonymous',
            'headline' => $profile->headline ?? null,
            'location' => $profile->city ? ($profile->city . ', ' . $profile->country_code) : $profile->country_code,
            'years_experience' => $profile->years_experience,
            'public_display_name' => $profile->public_display_name,
        ];
    }

    /**
     * Transform applicant data for full view
     */
    protected function transformApplicantFull($applicant): array
    {
        $profile = $applicant->applicantProfile;

        return [
            'id' => $applicant->id,
            'locked' => false,
            'email' => $applicant->email,
            'phone' => $applicant->phone,
            'first_name' => $profile->first_name ?? null,
            'last_name' => $profile->last_name ?? null,
            'full_name' => trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')),
            'headline' => $profile->headline ?? null,
            'summary' => $profile->summary ?? null,
            'years_experience' => $profile->years_experience,
            'location' => [
                'country' => $profile->country_code,
                'state' => $profile->state,
                'city' => $profile->city,
            ],
            'avatar_url' => $applicant->avatar_url,
            'documents' => $applicant->documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'type' => $doc->doc_type,
                    'name' => $doc->file_name,
                    'url' => $doc->file_url,
                    'size' => $doc->file_size_bytes,
                    'mime_type' => $doc->mime_type,
                ];
            }),
            'created_at' => $applicant->created_at,
        ];
    }
}
