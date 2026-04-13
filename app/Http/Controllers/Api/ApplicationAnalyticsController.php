<?php

namespace App\Http\Controllers\Api;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\ApplicationStatusHistory;
use App\Models\Interview;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicationAnalyticsController extends ApiController
{
    /**
     * GET /analytics/applications
     * Overall application analytics for employer's jobs.
     */
    public function index(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        $jobId = $request->input('job_id');
        $period = $request->input('period', '30d'); // 7d, 30d, 90d, 1y, all
        $dateFrom = $this->getDateFrom($period);

        // Scope to employer's jobs
        $jobQuery = Job::where('owner_user_id', $u->id)
            ->where('owner_type', $u->role);

        if ($jobId) {
            $jobQuery->where('id', $jobId);
        }

        $jobIds = $jobQuery->pluck('id');

        if ($jobIds->isEmpty()) {
            return $this->ok($this->emptyStats());
        }

        $appQuery = JobApplication::whereIn('job_id', $jobIds);

        if ($dateFrom) {
            $appQuery->where('created_at', '>=', $dateFrom);
        }

        $applications = $appQuery->get();
        $total = $applications->count();

        if ($total === 0) {
            return $this->ok($this->emptyStats());
        }

        // ── Funnel: status breakdown ──
        $statusBreakdown = $applications->groupBy('status')->map(function ($group) use ($total) {
            return [
                'count' => $group->count(),
                'percentage' => round(($group->count() / $total) * 100, 1),
            ];
        })->sortDesc();

        // ── View rate (how many applications lead to interviews) ──
        $interviewCount = Interview::whereIn('application_id', $applications->pluck('id'))->count();
        $viewRate = $total > 0 ? round(($interviewCount / $total) * 100, 1) : 0;

        // ── Response time: avg time from applied → shortlisted ──
        $responseTimes = ApplicationStatusHistory::whereIn('application_id', $applications->pluck('id'))
            ->where('to_status', 'shortlisted')
            ->whereNotNull('created_at')
            ->get();

        $responseTimeSeconds = [];
        foreach ($responseTimes as $rt) {
            $app = $applications->firstWhere('id', $rt->application_id);
            if ($app) {
                $seconds = $rt->created_at->diffInSeconds($app->created_at);
                $responseTimeSeconds[] = $seconds;
            }
        }

        $avgResponseTime = !empty($responseTimeSeconds)
            ? round(array_sum($responseTimeSeconds) / count($responseTimeSeconds))
            : null;

        // ── Success rate (hired / total) ──
        $hiredCount = $applications->where('status', 'hired')->count();
        $successRate = $total > 0 ? round(($hiredCount / $total) * 100, 1) : 0;

        // ── Rejection rate ──
        $rejectedCount = $applications->whereIn('status', ['rejected', 'withdrawn'])->count();
        $rejectionRate = $total > 0 ? round(($rejectedCount / $total) * 100, 1) : 0;

        // ── Applications over time (daily) ──
        $applicationsOverTime = $applications
            ->groupBy(fn($app) => $app->created_at->format('Y-m-d'))
            ->map(fn($group) => $group->count())
            ->sortKeys();

        // ── Per-job breakdown ──
        $perJob = $applications->groupBy('job_id')->map(function ($group) {
            $jobTotal = $group->count();
            $jobHired = $group->where('status', 'hired')->count();
            $jobInterviews = Interview::whereIn('application_id', $group->pluck('id'))->count();

            return [
                'total' => $jobTotal,
                'hired' => $jobHired,
                'interviews' => $jobInterviews,
                'success_rate' => $jobTotal > 0 ? round(($jobHired / $jobTotal) * 100, 1) : 0,
                'view_rate' => $jobTotal > 0 ? round(($jobInterviews / $jobTotal) * 100, 1) : 0,
            ];
        });

        // Add job titles
        $jobs = Job::whereIn('id', $perJob->keys())->get()->keyBy('id');
        $perJob = $perJob->map(function ($stats, $jobId) use ($jobs) {
            $job = $jobs->get($jobId);
            return array_merge($stats, [
                'job_id' => $jobId,
                'job_title' => $job?->title ?? "Job #{$jobId}",
            ]);
        });

        // ── Avg time to hire (from applied → hired) ──
        $hireTimes = ApplicationStatusHistory::whereIn('application_id', $applications->pluck('id'))
            ->where('to_status', 'hired')
            ->whereNotNull('created_at')
            ->get();

        $hireTimeSeconds = [];
        foreach ($hireTimes as $ht) {
            $app = $applications->firstWhere('id', $ht->application_id);
            if ($app) {
                $seconds = $ht->created_at->diffInSeconds($app->created_at);
                $hireTimeSeconds[] = $seconds;
            }
        }

        $avgTimeToHire = !empty($hireTimeSeconds)
            ? round(array_sum($hireTimeSeconds) / count($hireTimeSeconds))
            : null;

        // ── Pipeline velocity (applications per week) ──
        $oldestApp = $applications->min('created_at');
        $newestApp = $applications->max('created_at');
        $weeksSpan = $oldestApp && $newestApp
            ? max(1, Carbon::parse($oldestApp)->diffInWeeks($newestApp))
            : 1;
        $pipelineVelocity = round($total / $weeksSpan, 1);

        return $this->ok([
            'period' => $period,
            'date_from' => $dateFrom?->toIso8601String(),
            'total_applications' => $total,
            'funnel' => $statusBreakdown,
            'view_rate' => $viewRate,
            'success_rate' => $successRate,
            'rejection_rate' => $rejectionRate,
            'avg_response_time_seconds' => $avgResponseTime,
            'avg_response_time_formatted' => $avgResponseTime ? $this->formatDuration($avgResponseTime) : 'N/A',
            'avg_time_to_hire_seconds' => $avgTimeToHire,
            'avg_time_to_hire_formatted' => $avgTimeToHire ? $this->formatDuration($avgTimeToHire) : 'N/A',
            'pipeline_velocity_per_week' => $pipelineVelocity,
            'applications_over_time' => $applicationsOverTime,
            'per_job' => $perJob->values(),
            'interview_count' => $interviewCount,
            'hired_count' => $hiredCount,
            'rejected_count' => $rejectedCount,
        ]);
    }

    /**
     * GET /analytics/applications/time-to-hire
     * Detailed time-to-hire breakdown per job.
     */
    public function timeToHire(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        $jobQuery = Job::where('owner_user_id', $u->id)
            ->where('owner_type', $u->role);

        if ($request->job_id) {
            $jobQuery->where('id', $request->job_id);
        }

        $jobs = $jobQuery->withCount(['applications'])->get();

        $breakdown = [];
        foreach ($jobs as $job) {
            $hiredApps = JobApplication::where('job_id', $job->id)
                ->where('status', 'hired')
                ->get();

            $times = [];
            foreach ($hiredApps as $app) {
                $hiredAt = ApplicationStatusHistory::where('application_id', $app->id)
                    ->where('to_status', 'hired')
                    ->orderByDesc('created_at')
                    ->value('created_at');

                if ($hiredAt) {
                    $times[] = $hiredAt->diffInDays($app->created_at);
                }
            }

            $breakdown[] = [
                'job_id' => $job->id,
                'job_title' => $job->title,
                'total_applications' => $job->applications_count,
                'hired' => $hiredApps->count(),
                'avg_days_to_hire' => !empty($times) ? round(array_sum($times) / count($times), 1) : null,
                'min_days_to_hire' => !empty($times) ? min($times) : null,
                'max_days_to_hire' => !empty($times) ? max($times) : null,
            ];
        }

        return $this->ok($breakdown);
    }

    /**
     * GET /analytics/applications/source
     * Application source breakdown (if tracked).
     */
    public function sourceBreakdown(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        $jobIds = Job::where('owner_user_id', $u->id)
            ->where('owner_type', $u->role)
            ->pluck('id');

        if ($jobIds->isEmpty()) {
            return $this->ok([]);
        }

        // TODO: Add source tracking to applications (e.g. organic, referral, social, paid)
        // For now, return a placeholder
        return $this->ok([
            'message' => 'Source tracking not yet implemented',
            'sources' => [],
        ]);
    }

    // ── Private helpers ──

    private function getDateFrom(string $period): ?Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            default => null,
        };
    }

    private function emptyStats(): array
    {
        return [
            'period' => '30d',
            'total_applications' => 0,
            'funnel' => [],
            'view_rate' => 0,
            'success_rate' => 0,
            'rejection_rate' => 0,
            'avg_response_time_seconds' => null,
            'avg_response_time_formatted' => 'N/A',
            'avg_time_to_hire_seconds' => null,
            'avg_time_to_hire_formatted' => 'N/A',
            'pipeline_velocity_per_week' => 0,
            'applications_over_time' => [],
            'per_job' => [],
            'interview_count' => 0,
            'hired_count' => 0,
            'rejected_count' => 0,
        ];
    }

    private function formatDuration(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);

        if ($days > 0) {
            return "{$days}d {$hours}h";
        }
        if ($hours > 0) {
            return "{$hours}h";
        }

        $mins = intdiv($seconds % 3600, 60);
        return "{$mins}m";
    }
}
