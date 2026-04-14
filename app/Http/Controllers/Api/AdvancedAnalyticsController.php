<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdvancedAnalyticsController extends ApiController
{
    private function requireEmployer(): User
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            abort(403, 'Employer only');
        }
        return $u;
    }

    // ── Hiring Analytics Dashboard ───────────────────────────────────────
    public function hiringDashboard(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $days = (int) $request->query('days', 30);
        $startDate = now()->subDays($days);

        $query = JobApplication::query()
            ->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')
            ->where('jobs_table.owner_user_id', $u->id)
            ->where('job_applications.created_at', '>=', $startDate);

        // Time to Hire
        $timeToHire = (clone $query)
            ->whereNotNull('job_applications.hired_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, job_applications.created_at, job_applications.hired_at)) as avg_days')
            ->value('avg_days');

        // Cost per Hire
        $totalCosts = DB::table('hiring_costs')
            ->where('employer_user_id', $u->id)
            ->where('incurred_date', '>=', $startDate)
            ->sum('amount_cents');
        
        $hiredCount = (clone $query)->where('job_applications.status', 'hired')->count();
        $costPerHire = $hiredCount > 0 ? ($totalCosts / 100) / $hiredCount : 0;

        // Funnel Conversion
        $funnel = [
            'applied'      => (clone $query)->count(),
            'viewed'       => (clone $query)->whereNotNull('job_applications.viewed_at')->count(),
            'shortlisted'  => (clone $query)->whereNotNull('job_applications.shortlisted_at')->count(),
            'interviewed'  => (clone $query)->whereNotNull('job_applications.interviewed_at')->count(),
            'offered'      => (clone $query)->whereNotNull('job_applications.offered_at')->count(),
            'hired'        => $hiredCount,
        ];

        // Source Attribution
        $sources = DB::table('application_sources')
            ->join('job_applications', 'job_applications.id', '=', 'application_sources.application_id')
            ->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')
            ->where('jobs_table.owner_user_id', $u->id)
            ->where('job_applications.created_at', '>=', $startDate)
            ->selectRaw('application_sources.source_type, COUNT(*) as count, 
                SUM(CASE WHEN job_applications.status = "hired" THEN 1 ELSE 0 END) as hired_count')
            ->groupBy('application_sources.source_type')
            ->orderByDesc('count')
            ->get();

        return $this->ok([
            'time_to_hire_days'   => round((float)$timeToHire, 1),
            'cost_per_hire'       => round($costPerHire, 2),
            'funnel'              => $funnel,
            'source_attribution'  => $sources,
            'period_days'         => $days,
        ]);
    }

    // ── Time to Hire by Dimension ────────────────────────────────────────
    public function timeToHireBreakdown(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $dimension = $request->query('dimension', 'role'); // role, location, department
        $days = (int) $request->query('days', 90);
        $startDate = now()->subDays($days);

        $query = JobApplication::query()
            ->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')
            ->where('jobs_table.owner_user_id', $u->id)
            ->where('job_applications.created_at', '>=', $startDate)
            ->whereNotNull('job_applications.hired_at');

        $groupBy = match($dimension) {
            'location' => 'jobs_table.location',
            'department' => 'jobs_table.title', // Using title as department proxy
            default => 'jobs_table.title',
        };

        $results = $query
            ->selectRaw("$groupBy as dimension_value, 
                AVG(TIMESTAMPDIFF(DAY, job_applications.created_at, job_applications.hired_at)) as avg_days,
                COUNT(*) as sample_size")
            ->groupBy('dimension_value')
            ->orderByDesc('sample_size')
            ->limit(10)
            ->get();

        return $this->ok([
            'dimension' => $dimension,
            'breakdown' => $results,
        ]);
    }

    // ── Source Attribution Detail ────────────────────────────────────────
    public function sourceAttribution(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $days = (int) $request->query('days', 30);
        $startDate = now()->subDays($days);

        $sources = DB::table('application_sources')
            ->join('job_applications', 'job_applications.id', '=', 'application_sources.application_id')
            ->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')
            ->where('jobs_table.owner_user_id', $u->id)
            ->where('job_applications.created_at', '>=', $startDate)
            ->selectRaw('
                application_sources.source_type,
                application_sources.source_name,
                COUNT(*) as applications,
                SUM(CASE WHEN job_applications.status = "hired" THEN 1 ELSE 0 END) as hires,
                ROUND(SUM(CASE WHEN job_applications.status = "hired" THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as conversion_rate
            ')
            ->groupBy('application_sources.source_type', 'application_sources.source_name')
            ->orderByDesc('applications')
            ->get();

        return $this->ok($sources);
    }

    // ── Cost per Hire Breakdown ──────────────────────────────────────────
    public function costPerHire(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $days = (int) $request->query('days', 90);
        $startDate = now()->subDays($days);

        $costsByType = DB::table('hiring_costs')
            ->where('employer_user_id', $u->id)
            ->where('incurred_date', '>=', $startDate)
            ->selectRaw('cost_type, SUM(amount_cents)/100 as total_cost, COUNT(*) as count')
            ->groupBy('cost_type')
            ->orderByDesc('total_cost')
            ->get();

        $totalCost = $costsByType->sum('total_cost');
        
        $hiredCount = JobApplication::query()
            ->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')
            ->where('jobs_table.owner_user_id', $u->id)
            ->where('job_applications.status', 'hired')
            ->where('job_applications.hired_at', '>=', $startDate)
            ->count();

        return $this->ok([
            'total_cost'      => round($totalCost, 2),
            'hired_count'     => $hiredCount,
            'cost_per_hire'   => $hiredCount > 0 ? round($totalCost / $hiredCount, 2) : 0,
            'breakdown'       => $costsByType,
        ]);
    }

    // ── Market Intelligence: Salary Benchmarks ───────────────────────────
    public function salaryBenchmarks(Request $request): JsonResponse
    {
        $this->requireEmployer();
        
        $title = $request->query('title');
        $country = $request->query('country', 'Philippines');
        $state = $request->query('state');

        $query = DB::table('salary_benchmarks')
            ->where('country', $country)
            ->where('data_date', '>=', now()->subMonths(6));

        if ($title) {
            $query->where(function($q) use ($title) {
                $q->where('job_title', 'like', "%{$title}%")
                  ->orWhere('normalized_title', 'like', "%{$title}%");
            });
        }

        if ($state) $query->where('state', $state);

        $benchmarks = $query
            ->selectRaw('normalized_title, 
                AVG(salary_median_cents)/100 as median_salary,
                AVG(salary_p25_cents)/100 as p25_salary,
                AVG(salary_p75_cents)/100 as p75_salary,
                currency_code,
                SUM(sample_size) as total_samples')
            ->groupBy('normalized_title', 'currency_code')
            ->orderByDesc('total_samples')
            ->limit(20)
            ->get();

        return $this->ok($benchmarks);
    }

    // ── Market Intelligence: Supply/Demand Heatmap ───────────────────────
    public function supplyDemandHeatmap(Request $request): JsonResponse
    {
        $this->requireEmployer();
        
        $category = $request->query('category');
        $country = $request->query('country', 'Philippines');

        $query = DB::table('market_supply_demand')
            ->where('country', $country)
            ->where('snapshot_date', '>=', now()->subDays(30));

        if ($category) $query->where('job_category', $category);

        $heatmap = $query
            ->selectRaw('state, city, job_category, specialty,
                AVG(demand_supply_ratio) as avg_ratio,
                AVG(active_jobs_count) as avg_jobs,
                AVG(active_candidates_count) as avg_candidates,
                MAX(market_temperature) as temperature')
            ->groupBy('state', 'city', 'job_category', 'specialty')
            ->orderByDesc('avg_ratio')
            ->get();

        return $this->ok($heatmap);
    }

    // ── Market Intelligence: Trending Skills ─────────────────────────────
    public function trendingSkills(Request $request): JsonResponse
    {
        $this->requireEmployer();
        
        $category = $request->query('category');

        $query = DB::table('trending_skills')
            ->where('period_end', '>=', now()->subDays(90));

        if ($category) $query->where('skill_category', $category);

        $skills = $query
            ->orderByDesc('mention_count')
            ->limit(50)
            ->get();

        return $this->ok($skills);
    }

    // ── Market Intelligence: Competitor Analysis ─────────────────────────
    public function competitorAnalysis(Request $request): JsonResponse
    {
        $this->requireEmployer();
        
        $title = $request->query('title');

        $query = DB::table('competitor_jobs')
            ->where('is_active', true)
            ->where('scraped_date', '>=', now()->subDays(30));

        if ($title) {
            $query->where(function($q) use ($title) {
                $q->where('job_title', 'like', "%{$title}%")
                  ->orWhere('normalized_title', 'like', "%{$title}%");
            });
        }

        $competitors = $query
            ->selectRaw('competitor_name, normalized_title,
                COUNT(*) as job_count,
                AVG(salary_min_cents)/100 as avg_min_salary,
                AVG(salary_max_cents)/100 as avg_max_salary,
                currency_code')
            ->groupBy('competitor_name', 'normalized_title', 'currency_code')
            ->orderByDesc('job_count')
            ->limit(20)
            ->get();

        return $this->ok($competitors);
    }

    // ── Custom Reports: List ─────────────────────────────────────────────
    public function customReports(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $reports = DB::table('custom_reports')
            ->where('created_by_user_id', $u->id)
            ->orderByDesc('id')
            ->get();

        return $this->ok($reports);
    }

    // ── Custom Reports: Create ───────────────────────────────────────────
    public function createCustomReport(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $data = $request->validate([
            'report_name'        => 'required|string|max:200',
            'description'        => 'nullable|string|max:1000',
            'report_type'        => 'required|in:hiring_analytics,market_intelligence,custom',
            'config'             => 'required|array',
            'columns'            => 'required|array',
            'filters'            => 'nullable|array',
            'schedule_frequency' => 'nullable|in:daily,weekly,monthly',
            'schedule_day'       => 'nullable|string',
            'schedule_time'      => 'nullable|date_format:H:i',
            'email_recipients'   => 'nullable|array',
        ]);

        $id = DB::table('custom_reports')->insertGetId([
            'created_by_user_id'  => $u->id,
            'report_name'         => $data['report_name'],
            'description'         => $data['description'] ?? null,
            'report_type'         => $data['report_type'],
            'config'              => json_encode($data['config']),
            'columns'             => json_encode($data['columns']),
            'filters'             => isset($data['filters']) ? json_encode($data['filters']) : null,
            'schedule_frequency'  => $data['schedule_frequency'] ?? null,
            'schedule_day'        => $data['schedule_day'] ?? null,
            'schedule_time'       => $data['schedule_time'] ?? null,
            'email_recipients'    => isset($data['email_recipients']) ? json_encode($data['email_recipients']) : null,
            'is_active'           => true,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return $this->ok(['id' => $id], 'Report created', 201);
    }

    // ── Custom Reports: Execute ──────────────────────────────────────────
    public function executeCustomReport(Request $request, int $reportId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $report = DB::table('custom_reports')
            ->where('id', $reportId)
            ->where('created_by_user_id', $u->id)
            ->first();

        if (!$report) return $this->fail('Report not found', null, 404);

        $format = $request->query('format', 'json'); // json, csv, pdf, excel

        $executionId = DB::table('report_executions')->insertGetId([
            'custom_report_id'     => $reportId,
            'executed_by_user_id'  => $u->id,
            'execution_type'       => 'manual',
            'status'               => 'processing',
            'output_format'        => $format,
            'started_at'           => now(),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // TODO: Queue actual report generation job
        // For now, return execution ID
        
        return $this->ok([
            'execution_id' => $executionId,
            'status'       => 'processing',
            'message'      => 'Report generation started',
        ]);
    }

    // ── Industry Benchmarks Comparison ───────────────────────────────────
    public function industryBenchmarks(Request $request): JsonResponse
    {
        $this->requireEmployer();
        
        $metric = $request->query('metric');

        $query = DB::table('industry_benchmarks')
            ->where('industry', 'healthcare')
            ->where('period_end', '>=', now()->subMonths(6));

        if ($metric) $query->where('metric_name', $metric);

        $benchmarks = $query
            ->orderBy('metric_name')
            ->orderByDesc('period_end')
            ->get();

        return $this->ok($benchmarks);
    }
}