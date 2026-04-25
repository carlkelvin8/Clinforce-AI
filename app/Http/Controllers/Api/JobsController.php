<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\JobPublishRequest;
use App\Http\Requests\Api\JobStoreRequest;
use App\Http\Requests\Api\JobUpdateRequest;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class JobsController extends Controller
{
    /**
     * GET /api/jobs
     * Owner / Admin listing (dashboard)
     */
  public function index(Request $request): JsonResponse
{
    $user = $request->user();

    $q = Job::query();

    // ✅ Candidate/Guest path: published only
    if (!$user || !in_array($user->role, ['admin', 'employer', 'agency'], true)) {
        $q->where('status', 'published')
          ->whereNotNull('published_at')
          ->orderByDesc('published_at')
          ->withCount('applications')
          ->withCount(['applications as applications_active_count' => function ($qq) {
              $qq->whereIn('status', ['submitted','shortlisted','interview']);
          }]);

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $q->where(function ($qq) use ($term) {
                $qq->where('title', 'like', "%{$term}%")
                   ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if ($request->filled('city')) {
            $q->where('city', $request->query('city'));
        }

        if ($request->filled('employment_type')) {
            $q->where('employment_type', $request->query('employment_type'));
        }

        if ($request->filled('work_mode')) {
            $q->where('work_mode', $request->query('work_mode'));
        }

        return response()->json($q->paginate(10));
    }

    // ✅ Owner/Admin path
    $q->orderByDesc('created_at')
      ->withCount('applications')
      ->withCount(['applications as applications_active_count' => function ($qq) {
          $qq->whereIn('status', ['submitted','shortlisted','interview']);
      }]);

    if ($user->role !== 'admin') {
        $q->where('owner_type', $user->role)
          ->where('owner_user_id', $user->id);
    }

    if ($request->filled('status')) {
        $status = strtolower((string) $request->query('status'));
        if (in_array($status, ['draft', 'published', 'archived'], true)) {
            $q->where('status', $status);
        }
    }

    if ($request->filled('q')) {
        $term = trim((string) $request->query('q'));
        $q->where(function ($qq) use ($term) {
            $qq->where('title', 'like', "%{$term}%")
               ->orWhere('description', 'like', "%{$term}%")
               ->orWhere('city', 'like', "%{$term}%");
        });
    }

    return response()->json($q->paginate(10));
}

    /**
     * POST /api/jobs
     */
    public function store(JobStoreRequest $request): JsonResponse
    {
        $user = $request->user();

        // Enforce job_post_limit from active subscription plan
        if (in_array($user->role, ['employer', 'agency'], true)) {
            $sub = \App\Models\Subscription::query()
                ->with('plan')
                ->where('user_id', $user->id)
                ->whereIn('status', ['active', 'past_due'])
                ->where('end_at', '>', now())
                ->latest('id')
                ->first();

            if ($sub && $sub->plan && $sub->plan->job_post_limit !== null) {
                $activeCount = Job::query()
                    ->where('owner_user_id', $user->id)
                    ->whereIn('status', ['published', 'draft'])
                    ->count();

                if ($activeCount >= $sub->plan->job_post_limit) {
                    return response()->json([
                        'success' => false,
                        'message' => "You have reached your plan limit of {$sub->plan->job_post_limit} job posting(s). Archive existing jobs or upgrade your plan.",
                        'errors' => ['job_post_limit' => ['Plan limit reached']],
                    ], 422);
                }
            }
        }

        $ownerType = in_array($user->role, ['employer', 'agency'], true)
            ? $user->role
            : 'employer';

        $job = Job::create([
            'owner_type'      => $ownerType,
            'owner_user_id'   => $user->id,
            'title'           => $request->title,
            'description'     => $request->description,
            'employment_type' => $request->employment_type,
            'work_mode'       => $request->work_mode,
            'country'         => $request->country,
            'state'           => $request->state,
            'city'            => $request->city,
            'salary_min'      => $request->salary_min,
            'salary_max'      => $request->salary_max,
            'salary_type'     => $request->salary_type ?: 'annually',
            'salary_currency' => $request->salary_currency,
            'status'          => 'draft',
        ]);

        return response()->json(['data' => $job], 201);
    }

    /**
     * GET /api/jobs/{job}
     */
    public function show(Request $request, Job $job): JsonResponse
    {
        $this->assertCanAccess($request->user(), $job);
        return response()->json(['data' => $job]);
    }

    /**
     * PUT /api/jobs/{job}
     */
    public function update(JobUpdateRequest $request, Job $job): JsonResponse
    {
        $job->update($request->validated());
        return response()->json(['data' => $job]);
    }

    /**
     * POST /api/jobs/{job}/publish
     */
    public function publish(JobPublishRequest $request, Job $job): JsonResponse
    {
        $this->assertCanAccess($request->user(), $job);

        $job->update([
            'status'       => 'published',
            'published_at' => $request->filled('publish_at')
                ? Carbon::parse($request->publish_at)
                : now(),
            'archived_at'  => null,
        ]);

        try {
            $this->notifyApplicantsForJob($job);
            $this->fireJobAlerts($job);
        } catch (\Throwable $e) {
        }
        return response()->json(['data' => $job]);
    }

    /**
     * POST /api/jobs/{job}/archive
     */
    public function archive(Request $request, Job $job): JsonResponse
    {
        $this->assertCanAccess($request->user(), $job);

        $job->update([
            'status'      => 'archived',
            'archived_at' => now(),
        ]);

        return response()->json(['data' => $job]);
    }

    protected function notifyApplicantsForJob(Job $job): void
    {
        $candidates = \App\Models\User::query()->where('role', 'applicant')->limit(50)->get();
        foreach ($candidates as $u) {
            \App\Models\Notification::pushNotification([
                'user_id' => $u->id,
                'role' => 'applicant',
                'category' => 'recommendations',
                'type' => 'job_recommendation',
                'title' => 'New job matches your profile',
                'body' => $job->title,
                'data' => [
                    'job_id' => $job->id,
                    'match' => ['score' => 0.7],
                ],
                'url' => "/candidate/jobs/{$job->id}",
                'batch_key' => "applicant:{$u->id}:job_reco",
            ]);
        }
    }

    protected function fireJobAlerts(Job $job): void
    {
        $alerts = \App\Models\JobAlert::where('active', true)->with('user')->get();
        foreach ($alerts as $alert) {
            if (!$alert->matches($job)) continue;
            try {
                \Illuminate\Support\Facades\Mail::to($alert->user->email)
                    ->send(new \App\Mail\JobAlertMail($job, $alert));
            } catch (\Throwable $e) {
                \Log::warning('Job alert email failed', ['error' => $e->getMessage()]);
            }
        }
    }
    /**
     * DELETE /api/jobs/{job}
     */
    public function destroy(Request $request, Job $job): JsonResponse
    {
        $this->assertCanAccess($request->user(), $job);
        $job->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    /**
     * GET /api/jobs/{job}/pipeline-report — CSV export of pipeline stats
     */
    public function pipelineReport(Request $request, Job $job): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->assertCanAccess($request->user(), $job);

        $apps = $job->applications()
            ->with('applicant:id,email,phone')
            ->get();

        $counts = [
            'submitted'   => 0,
            'shortlisted' => 0,
            'interview'   => 0,
            'offered'     => 0,
            'hired'       => 0,
            'rejected'    => 0,
            'withdrawn'   => 0,
        ];
        foreach ($apps as $a) {
            $s = $a->status ?? 'submitted';
            if (isset($counts[$s])) $counts[$s]++;
        }

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="pipeline-' . $job->id . '.csv"',
        ];

        return response()->stream(function () use ($job, $apps, $counts) {
            $h = fopen('php://output', 'w');

            // Summary
            fputcsv($h, ['Pipeline Report — ' . $job->title]);
            fputcsv($h, ['Generated', now()->toDateTimeString()]);
            fputcsv($h, []);
            fputcsv($h, ['Stage', 'Count']);
            foreach ($counts as $stage => $count) {
                fputcsv($h, [ucfirst($stage), $count]);
            }
            fputcsv($h, ['Total', $apps->count()]);
            fputcsv($h, []);

            // Individual applications
            fputcsv($h, ['Application ID', 'Candidate Email', 'Status', 'Applied At', 'Rating']);
            foreach ($apps as $a) {
                fputcsv($h, [
                    $a->id,
                    $a->applicant?->email ?? '',
                    $a->status,
                    $a->submitted_at?->toDateString() ?? '',
                    $a->employer_rating ?? '',
                ]);
            }

            fclose($h);
        }, 200, $headers);
    }

    /**
     * GET /api/jobs/duplicate-check — check for similar titles
     */
    public function duplicateCheck(Request $request): \Illuminate\Http\JsonResponse
    {
        $u = $request->user();
        if (!$u) return response()->json(['message' => 'Unauthenticated'], 401);

        $t = trim($request->query('title', ''));
        if (strlen($t) < 5) return response()->json(['data' => ['duplicates' => []]]);

        $similar = Job::query()
            ->where('owner_user_id', $u->id)
            ->where('status', 'published')
            ->where('title', 'like', '%' . $t . '%')
            ->limit(3)
            ->get(['id', 'title', 'status', 'created_at']);

        return response()->json(['data' => ['duplicates' => $similar]]);
    }

    /**
     * GET /api/public/jobs
     * Candidate browsing endpoint
     */
    public function publicIndex(Request $request): JsonResponse
    {
        // Cache only unfiltered first-page requests for 5 minutes
        $hasFilters = $request->filled('q') || $request->filled('city') ||
                      $request->filled('employment_type') || $request->filled('work_mode') ||
                      $request->filled('salary_min') || $request->filled('salary_max') ||
                      $request->filled('employer_id');
        $page = (int) $request->query('page', 1);
        $cacheKey = 'public_jobs_p' . $page;

        if (!$hasFilters) {
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if ($cached) return response()->json($cached);
        }

        $q = Job::query()
            ->where('status', 'published')
            ->whereNotNull('published_at');

        if ($request->filled('employer_id')) {
            $employerId = (int) $request->query('employer_id');
            if ($employerId > 0) $q->where('owner_user_id', $employerId);
        }

        if ($request->filled('q')) {
            $term = trim($request->q);
            $q->where(function ($qq) use ($term) {
                $qq->where('title', 'like', "%{$term}%")
                   ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if ($request->filled('city'))            $q->where('city', $request->city);
        if ($request->filled('employment_type')) $q->where('employment_type', $request->employment_type);
        if ($request->filled('work_mode'))       $q->where('work_mode', $request->work_mode);

        if ($request->filled('salary_min')) {
            $q->where(fn($sq) => $sq->whereNull('salary_max')->orWhere('salary_max', '>=', (float) $request->salary_min));
        }
        if ($request->filled('salary_max')) {
            $q->where(fn($sq) => $sq->whereNull('salary_min')->orWhere('salary_min', '<=', (float) $request->salary_max));
        }

        $q->orderByDesc('published_at');
        $result = $q->paginate(10);

        if (!$hasFilters) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, $result->toArray(), now()->addMinutes(5));
        }

        return response()->json($result);
    }

    /**
     * GET /api/public/jobs/{job}
     */
    public function publicShow(Request $request, Job $job): JsonResponse
    {
        if ($job->status !== 'published') {
            return response()->json(['message' => 'Not found.'], 404);
        }

        // Increment view count (throttled per IP — once per hour)
        $viewKey = 'job_view:' . $job->id . ':' . md5($request->ip());
        if (!\Illuminate\Support\Facades\Cache::has($viewKey)) {
            $job->increment('view_count');
            \Illuminate\Support\Facades\Cache::put($viewKey, 1, now()->addHour());
        }

        $job->load(['owner' => function($q) {
            $q->select('id', 'email', 'role');
        }, 'owner.employerProfile', 'owner.agencyProfile']);

        return response()->json(['data' => $job]);
    }

    private function assertCanAccess($user, Job $job): void
    {
        if (!$user) abort(401);

        if ($user->role === 'admin') return;

        $ownerType = in_array($user->role, ['employer', 'agency'], true)
            ? $user->role
            : null;

        if (!$ownerType) abort(403);

        if (
            $job->owner_user_id !== $user->id ||
            $job->owner_type !== $ownerType
        ) {
            abort(403);
        }
    }
}
