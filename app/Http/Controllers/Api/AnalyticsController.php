<?php

namespace App\Http\Controllers\Api;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\ApplicationStatusHistory;
use App\Models\Interview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends ApiController
{
    public function dashboard(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $days = (int) $request->query('days', 30);
        $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
        $since = now()->subDays($days);

        // Base job query for this employer
        $jobQuery = Job::query();
        if ($u->role !== 'admin') {
            $jobQuery->where('owner_user_id', $u->id)->where('owner_type', $u->role);
        }
        $jobIds = $jobQuery->pluck('id');

        // Applications per job
        $appsPerJob = JobApplication::query()
            ->whereIn('job_id', $jobIds)
            ->where('created_at', '>=', $since)
            ->select('job_id', DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status='hired' THEN 1 ELSE 0 END) as hired"),
                DB::raw("SUM(CASE WHEN status='shortlisted' THEN 1 ELSE 0 END) as shortlisted"),
                DB::raw("SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected"),
                DB::raw("SUM(CASE WHEN status='interview' THEN 1 ELSE 0 END) as interview")
            )
            ->groupBy('job_id')
            ->with('job:id,title')
            ->get();

        // Time-to-hire: avg days from submitted to hired
        $timeToHire = ApplicationStatusHistory::query()
            ->whereIn('application_id', function ($q) use ($jobIds) {
                $q->select('id')->from('job_applications')->whereIn('job_id', $jobIds);
            })
            ->where('to_status', 'hired')
            ->where('created_at', '>=', $since)
            ->join('job_applications', 'application_status_histories.application_id', '=', 'job_applications.id')
            ->select(DB::raw('AVG(DATEDIFF(application_status_histories.created_at, job_applications.created_at)) as avg_days'))
            ->value('avg_days');

        // Pipeline totals
        $pipeline = JobApplication::query()
            ->whereIn('job_id', $jobIds)
            ->where('created_at', '>=', $since)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $total = $pipeline->sum();
        $hired = $pipeline->get('hired', 0);
        $conversionRate = $total > 0 ? round(($hired / $total) * 100, 1) : 0;

        // Interviews scheduled
        $interviewCount = Interview::query()
            ->whereHas('application', fn($q) => $q->whereIn('job_id', $jobIds))
            ->where('created_at', '>=', $since)
            ->count();

        // Daily applications trend
        $trend = JobApplication::query()
            ->whereIn('job_id', $jobIds)
            ->where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return $this->ok([
            'days' => $days,
            'kpis' => [
                'total_applications'   => $total,
                'hired'                => $hired,
                'conversion_rate'      => $conversionRate,
                'avg_time_to_hire_days' => $timeToHire ? round((float)$timeToHire, 1) : null,
                'interviews_scheduled' => $interviewCount,
                'active_jobs'          => $jobIds->count(),
            ],
            'applications_per_job' => $appsPerJob->map(fn($item) => [
                'job_id'          => $item->job_id,
                'title'           => $item->job?->title ?? "Job #{$item->job_id}",
                'count'           => (int) $item->total,
                'hired'           => (int) $item->hired,
                'shortlisted'     => (int) $item->shortlisted,
                'rejected'        => (int) $item->rejected,
                'conversion_rate' => $item->total > 0 ? round(($item->hired / $item->total) * 100, 1) : 0,
            ]),
            'trend' => $trend->map(fn($t) => ['date' => $t->day, 'count' => (int) $t->count]),
            'pipeline' => $pipeline,
        ]);
    }
}
