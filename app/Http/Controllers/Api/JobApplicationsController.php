<?php
// app/Http/Controllers/Api/JobApplicationsController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\JobApplyRequest;
use App\Http\Requests\Api\JobApplicationStatusUpdateRequest;
use App\Mail\ApplicationStatusUpdated;
use App\Models\ApplicationStatusHistory;
use App\Models\Document;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class JobApplicationsController extends ApiController
{
    /**
     * Your DB enum roles: admin, employer, agency, applicant
     * So applicant = "applicant" (no "candidate" role).
     */
    private const APPLICANT_ROLES = ['applicant', 'admin'];
    private const OWNER_ROLES     = ['employer', 'agency', 'admin'];

    private function isAdmin($u): bool
    {
        return $u && $u->role === 'admin';
    }

    private function isApplicantRole($u): bool
    {
        return $u && in_array($u->role, self::APPLICANT_ROLES, true);
    }

    private function isOwnerRole($u): bool
    {
        return $u && in_array($u->role, self::OWNER_ROLES, true);
    }

    /**
     * POST /jobs/{job}/apply
     */
    public function apply(JobApplyRequest $request, Job $job): JsonResponse
    {
        $u = $this->requireAuth();

        // Only applicant/admin can apply
        if (!$this->isApplicantRole($u)) {
            return $this->fail('Forbidden (403). Only applicant accounts can apply.', null, 403);
        }

        // Check for existing active application to the same job
        $existing = JobApplication::where('job_id', $job->id)
            ->where('applicant_user_id', $u->id)
            ->whereNotIn('status', ['withdrawn', 'rejected'])
            ->first();

        if ($existing) {
            return $this->fail('You have already applied for this job.', ['job' => ['Already applied']], 409);
        }

        $v = $request->validated();

        $app = null;

        DB::transaction(function () use ($job, $u, $v, &$app) {
            $app = JobApplication::query()->create([
                'job_id'            => $job->id,
                'applicant_user_id' => $u->id,
                'status'            => 'submitted',
                'cover_letter'      => $v['cover_letter'] ?? null,
                'submitted_at'      => now(),
            ]);

            ApplicationStatusHistory::query()->create([
                'application_id'      => $app->id,
                'from_status'         => null,
                'to_status'           => 'submitted',
                'changed_by_user_id'  => $u->id,
                'note'                => 'Initial submission',
                'created_at'          => now(),
            ]);
        });

        $resumeFile = $request->file('resume');
        if ($resumeFile) {
            $dir = "documents/{$u->id}";
            $path = $resumeFile->store($dir, 'public');
            $url  = Storage::disk('public')->url($path);
            Document::query()->create([
                'user_id' => $u->id,
                'doc_type' => 'resume',
                'file_url' => $url,
                'file_name' => $resumeFile->getClientOriginalName(),
                'mime_type' => $resumeFile->getClientMimeType(),
                'file_size_bytes' => $resumeFile->getSize(),
                'status' => 'active',
                'created_at' => now(),
            ]);
        }

        $others = $request->file('other_docs') ?: [];
        foreach ($others as $file) {
            if (!$file) continue;
            $dir = "documents/{$u->id}";
            $path = $file->store($dir, 'public');
            $url  = Storage::disk('public')->url($path);
            Document::query()->create([
                'user_id' => $u->id,
                'doc_type' => 'other',
                'file_url' => $url,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size_bytes' => $file->getSize(),
                'status' => 'active',
                'created_at' => now(),
            ]);
        }

        // Notifications
        try {
            $this->notifyEmployerNewApplication($job->owner_user_id, $app);
            $this->notifyApplicantStatus($u->id, $app, 'submitted');
        } catch (\Throwable $e) {
        }

        return $this->ok($app, 'Application submitted', 201);
    }

    protected function notifyEmployerNewApplication(int $employerId, JobApplication $app): void
    {
        \App\Models\Notification::pushNotification([
            'user_id' => $employerId,
            'role' => 'employer',
            'category' => 'applications',
            'type' => 'new_application',
            'title' => 'New application received',
            'body' => 'A candidate applied to your job.',
            'data' => [
                'job_id' => $app->job_id,
                'application_id' => $app->id,
                'applicant_user_id' => $app->applicant_user_id,
                'metrics' => [
                    'skills_match' => 0.72,
                    'experience_score' => 0.68,
                    'education_score' => 0.6,
                    'portfolio_score' => 0.65,
                ],
            ],
            'url' => "/applicants/{$app->id}",
            'batch_key' => "employer:{$employerId}:job:{$app->job_id}:new_application",
        ]);
    }

    protected function notifyApplicantStatus(int $applicantId, JobApplication $app, string $status): void
    {
        \App\Models\Notification::pushNotification([
            'user_id' => $applicantId,
            'role' => 'applicant',
            'category' => 'applications',
            'type' => "status_{$status}",
            'title' => 'Application update',
            'body' => 'Your application status changed.',
            'data' => [
                'job_id' => $app->job_id,
                'application_id' => $app->id,
                'status' => $status,
            ],
            'url' => "/candidate/applications/{$app->id}",
            'batch_key' => "applicant:{$applicantId}:app:{$app->id}:status",
        ]);
    }

    /**
     * GET /applications/{application}/resume
     * View/download applicant's resume (requires subscription for employers)
     */
    public function viewResume(JobApplication $application): mixed
    {
        $u = $this->requireAuth();

        $application->load(['job', 'applicant']);

        // Check ownership
        $isOwner = $application->job
            && in_array($u->role, ['employer', 'agency'], true)
            && $application->job->owner_user_id === $u->id
            && $application->job->owner_type === $u->role;

        $isApplicant = $application->applicant_user_id === $u->id;

        if (!$this->isAdmin($u) && !$isApplicant && !$isOwner) {
            return response()->json([
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'You do not have permission to view this resume.',
            ], 403);
        }

        // Employers need BOTH subscription AND document access payment
        if (in_array($u->role, ['employer', 'agency'], true) && !$isApplicant) {
            // First check subscription (for messaging, interviews, hiring)
            $subscription = $u->subscription;

            if (!$subscription || $subscription->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'code' => 'SUBSCRIPTION_REQUIRED',
                    'message' => 'Active subscription required.',
                ], 402);
            }

            if ($subscription->current_period_end && $subscription->current_period_end->isPast()) {
                return response()->json([
                    'success' => false,
                    'code' => 'SUBSCRIPTION_REQUIRED',
                    'message' => 'Your subscription has expired.',
                ], 402);
            }

            // Then check document access payment (separate from subscription)
            $hasDocAccess = \App\Models\DocumentAccessPayment::hasAccess($u->id, $application->applicant_user_id);

            if (!$hasDocAccess) {
                return response()->json([
                    'success' => false,
                    'code' => 'DOCUMENT_ACCESS_REQUIRED',
                    'message' => 'Document access payment required to view resume and documents. Subscription unlocks messaging and hiring, but documents require separate payment.',
                    'action' => 'purchase_document_access',
                    'applicant_id' => $application->applicant_user_id,
                ], 402);
            }
        }

        // Find resume document (check active or null status)
        $resume = Document::query()
            ->where('user_id', $application->applicant_user_id)
            ->where('doc_type', 'resume')
            ->where(function($q) {
                $q->where('status', 'active')
                  ->orWhereNull('status');
            })
            ->latest()
            ->first();

        if (!$resume) {
            return response()->json([
                'success' => false,
                'message' => 'Resume not found for this applicant.',
            ], 404);
        }

        // Log access
        if ($isOwner) {
            try {
                \DB::table('access_logs')->insert([
                    'employer_id' => $u->id,
                    'candidate_user_id' => $application->applicant_user_id,
                    'action' => 'download_resume',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to log resume access: ' . $e->getMessage());
            }
        }

        // Serve file securely
        $filePath = $resume->file_url;
        
        // Fix for production /build/ prefix or full URLs
        if (str_contains($filePath, '/build/')) {
            $filePath = str_replace('/build/', '/', $filePath);
        }

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
                'message' => 'Resume file not found on server.',
                'debug' => [
                    'original_url' => $resume->file_url,
                    'processed_path' => $filePath,
                ],
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
     * GET /applications - Updated to include resume availability
     */
    public function index(): JsonResponse
    {
        $u = $this->requireAuth();

        $scope = (string) request()->query('scope', 'mine');
        $scope = in_array($scope, ['mine', 'owned'], true) ? $scope : 'mine';

        // Eager-load applicant (and profile for avatar) to expose avatar_url without N+1 queries
        $q = JobApplication::query()->with(['job', 'applicant.applicantProfile']);

        if ($scope === 'owned') {
            if (!$this->isOwnerRole($u)) {
                return $this->fail('Forbidden (403). Your request is not allowed for this account/scope.', null, 403);
            }

            // employer/agency: only jobs they own
            if (!$this->isAdmin($u)) {
                $q->whereHas('job', function ($jq) use ($u) {
                    $jq->where('owner_user_id', $u->id)
                       ->where('owner_type', $u->role);
                });
            }
        } else {
            // mine
            if (!$this->isApplicantRole($u)) {
                return $this->fail('Forbidden (403). Your request is not allowed for this account/scope.', null, 403);
            }

            if (!$this->isAdmin($u)) {
                $q->where('applicant_user_id', $u->id);
            }
        }

        if ($status = request()->query('status')) {
            $allowed = ['submitted', 'shortlisted', 'rejected', 'interview', 'hired', 'withdrawn'];
            if (!in_array($status, $allowed, true)) {
                return $this->fail('Invalid status filter', ['status' => ['Invalid']], 422);
            }
            $q->where('status', $status);
        }

        $applications = $q->orderByDesc('id')->paginate(20);

        \Log::info('Applications fetched', [
            'user_id' => $u->id,
            'scope' => $scope,
            'count' => $applications->count(),
            'total' => $applications->total(),
        ]);

        // Add subscription status and resume availability for employers
        if ($scope === 'owned' && in_array($u->role, ['employer', 'agency'], true)) {
            $hasSubscription = $u->subscription !== null;
            
            $applications->getCollection()->transform(function ($app) use ($hasSubscription) {
                $app->has_resume = Document::query()
                    ->where('user_id', $app->applicant_user_id)
                    ->where('doc_type', 'resume')
                    ->where('status', 'active')
                    ->exists();
                
                $app->can_view_resume = $hasSubscription;
                
                return $app;
            });

            return $this->ok([
                'applications' => $applications,
                'has_subscription' => $hasSubscription,
                'total' => $applications->total(),
            ]);
        }

        return $this->ok($applications);
    }

    /**
     * GET /applications/{application}
     */
    public function show(JobApplication $application): JsonResponse
    {
        $u = $this->requireAuth();

        $application->load(['job', 'statusHistory', 'interview', 'aiScreenings', 'applicant.applicantProfile']);

        $isApplicant = $application->applicant_user_id === $u->id;

        $isOwner = $application->job
            && in_array($u->role, ['employer', 'agency'], true)
            && $application->job->owner_user_id === $u->id
            && $application->job->owner_type === $u->role;

        if (!$this->isAdmin($u) && !$isApplicant && !$isOwner) {
            return $this->fail('Forbidden (403). Your request is not allowed for this application.', null, 403);
        }

        // Add resume availability flag (check without status filter first)
        $hasResume = Document::query()
            ->where('user_id', $application->applicant_user_id)
            ->where('doc_type', 'resume')
            ->where(function($q) {
                $q->where('status', 'active')
                  ->orWhereNull('status');
            })
            ->exists();

        $data = $application->toArray();
        $data['has_resume'] = $hasResume;

        return $this->ok($data);
    }

    /**
     * PATCH /applications/{application}/status
     */
    public function updateStatus(JobApplicationStatusUpdateRequest $request, JobApplication $application): JsonResponse
    {
        $u = $this->requireAuth();
        $v = $request->validated();

        $application->load('job');

        $isApplicant = $application->applicant_user_id === $u->id;

        $isOwner = $application->job
            && in_array($u->role, ['employer', 'agency'], true)
            && $application->job->owner_user_id === $u->id
            && $application->job->owner_type === $u->role;

        if (!$this->isAdmin($u) && !$isApplicant && !$isOwner) {
            return $this->fail('Forbidden (403). Your request is not allowed for this application.', null, 403);
        }

        // Employers need active subscription for hiring actions
        if ($isOwner && in_array($u->role, ['employer', 'agency'], true)) {
            $subscription = $u->subscription;

            if (!$subscription || $subscription->status !== 'active') {
                return $this->fail('Active subscription required to manage applications, schedule interviews, and hire candidates.', ['subscription' => ['Subscription required']], 402);
            }

            if ($subscription->current_period_end && $subscription->current_period_end->isPast()) {
                return $this->fail('Your subscription has expired. Please renew to continue managing applications.', ['subscription' => ['Subscription expired']], 402);
            }
        }

        $from = $application->status;
        $to   = $v['status'];

        if (!$this->isAdmin($u)) {
            if ($isApplicant && !in_array($to, ['withdrawn', 'hired'], true)) {
                return $this->fail('Applicant can only change to withdrawn or hired', null, 422);
            }

            if ($from === 'hired') {
                return $this->fail('Cannot change status after hired', null, 409);
            }

            $allowedOwnerTransitions = [
                'submitted'   => ['shortlisted', 'rejected', 'interview', 'hired'],
                'shortlisted' => ['interview', 'rejected', 'hired'],
                'interview'   => ['hired', 'rejected'],
                'rejected'    => [],
                'withdrawn'   => [],
                'hired'       => [],
            ];

            if ($isOwner) {
                $allowed = $allowedOwnerTransitions[$from] ?? [];
                if (!in_array($to, $allowed, true)) {
                    return $this->fail('Invalid status transition', [
                        'status' => ["Cannot change from {$from} to {$to}"],
                    ], 422);
                }
            } else {
                if ($isApplicant) {
                    if ($to === 'withdrawn') {
                        if (in_array($from, ['rejected', 'hired'], true)) {
                            return $this->fail('Cannot withdraw after final decision', null, 409);
                        }
                    } elseif ($to === 'hired') {
                        if (in_array($from, ['rejected', 'withdrawn', 'hired'], true)) {
                            return $this->fail('Cannot mark hired from current status', null, 409);
                        }
                    }
                } 
            }
        }

        DB::transaction(function () use ($application, $from, $to, $u, $v) {
            $application->status = $to;
            $application->save();

            ApplicationStatusHistory::query()->create([
                'application_id'      => $application->id,
                'from_status'         => $from,
                'to_status'           => $to,
                'changed_by_user_id'  => $u->id,
                'note'                => $v['note'] ?? null,
                'created_at'          => now(),
            ]);
        });

        try {
            $this->notifyApplicantStatus($application->applicant_user_id, $application, $v['status']);
        } catch (\Throwable $e) {
        }

        // Email the applicant about the status change
        try {
            $application->load(['job', 'applicant']);
            if ($application->applicant?->email) {
                Mail::to($application->applicant->email)
                    ->send(new ApplicationStatusUpdated($application, $from, $to));
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to send status update email', ['error' => $e->getMessage()]);
        }

        return $this->ok($application->fresh(), 'Status updated');
    }

    public function withdraw(Request $request, JobApplication $application): JsonResponse
    {
        $u = $this->requireAuth();

        if ($application->applicant_user_id !== $u->id && !$this->isAdmin($u)) {
            return $this->fail('Forbidden', null, 403);
        }

        if (in_array($application->status, ['hired', 'rejected', 'withdrawn'], true)) {
            return $this->fail('Cannot withdraw from current status', null, 409);
        }

        $from = $application->status;

        DB::transaction(function () use ($application, $u, $request) {
            $application->status = 'withdrawn';
            $application->save();

            ApplicationStatusHistory::query()->create([
                'application_id'     => $application->id,
                'from_status'        => $application->getOriginal('status') ?? 'submitted',
                'to_status'          => 'withdrawn',
                'changed_by_user_id' => $u->id,
                'note'               => $request->input('reason') ?? 'Withdrawn by candidate',
                'created_at'         => now(),
            ]);
        });

        // Email the employer
        try {
            $application->load(['job.owner', 'applicant']);
            $employerEmail = $application->job?->owner?->email;
            if ($employerEmail) {
                Mail::raw(
                    "A candidate has withdrawn their application for \"{$application->job?->title}\".\n\nApplication ID: #{$application->id}",
                    fn ($m) => $m->to($employerEmail)->subject("Application withdrawn — {$application->job?->title}")
                );
            }
        } catch (\Throwable $e) {}

        return $this->ok($application->fresh(), 'Application withdrawn');
    }

    public function rateCandidate(Request $request, JobApplication $application): JsonResponse
    {
        $u = $this->requireAuth();
        $application->load('job');

        $isOwner = $application->job
            && in_array($u->role, ['employer', 'agency'], true)
            && $application->job->owner_user_id === $u->id
            && $application->job->owner_type === $u->role;

        if (!$this->isAdmin($u) && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $v = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'note'   => 'nullable|string|max:1000',
        ]);

        $application->employer_rating = $v['rating'];
        $application->employer_rating_note = $v['note'] ?? null;
        $application->save();

        return $this->ok($application, 'Rating saved');
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $u = $this->requireAuth();

        if (!$this->isOwnerRole($u)) {
            abort(403);
        }

        $q = JobApplication::query()->with(['job', 'applicant.applicantProfile']);

        if (!$this->isAdmin($u)) {
            $q->whereHas('job', fn($jq) => $jq->where('owner_user_id', $u->id)->where('owner_type', $u->role));
        }

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="candidates-export.csv"',
        ];

        return response()->stream(function () use ($q) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Candidate', 'Job', 'Status', 'Rating', 'Applied', 'Email', 'Phone']);

            $q->chunk(200, function ($apps) use ($handle) {
                foreach ($apps as $a) {
                    $profile = $a->applicant?->applicantProfile;
                    $first = $profile?->first_name ?? '';
                    $last  = $profile?->last_name ?? '';
                    $name  = trim($first . ' ' . $last) ?: ($a->applicant?->email ?? 'Candidate #' . $a->applicant_user_id);

                    fputcsv($handle, [
                        $a->id,
                        $name,
                        $a->job?->title ?? '',
                        $a->status,
                        $a->employer_rating ?? '',
                        $a->submitted_at?->toDateString() ?? '',
                        $a->applicant?->email ?? '',
                        $a->applicant?->phone ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
