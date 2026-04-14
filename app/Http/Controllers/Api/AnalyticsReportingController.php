<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsReportingController extends ApiController
{
    private function requireEmployer(): User
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            abort(403, 'Employer only');
        }
        return $u;
    }

    // ── Advanced Hiring Analytics Dashboard ──────────────────────────────
    public function hiringAnalyticsDashboard(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $dateRange = $this->getDateRange($request);
        $cacheKey = "hiring_analytics_{$u->id}_{$dateRange['start']}_{$dateRange['end']}";
        
        $data = Cache::remember($cacheKey, 3600, function () use ($u, $dateRange) {
            return [
                'overview' => $this->getHiringOverview($u->id, $dateRange),
                'time_to_hire' => $this->getTimeToHireMetrics($u->id, $dateRange),
                'source_attribution' => $this->getSourceAttribution($u->id, $dateRange),
                'cost_per_hire' => $this->getCostPerHire($u->id, $dateRange),
                'funnel_analysis' => $this->getFunnelAnalysis($u->id, $dateRange),
                'trends' => $this->getHiringTrends($u->id, $dateRange),
            ];
        });

        return $this->ok($data);
    }

    public function timeToHireBreakdown(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'group_by' => 'in:role,department,location,source',
        ]);

        $groupBy = $data['group_by'] ?? 'role';
        
        $results = DB::table('hiring_metrics')
            ->where('employer_user_id', $u->id)
            ->where('metric_type', 'time_to_hire')
            ->whereBetween('period_start', [$data['start_date'], $data['end_date']])
            ->when($groupBy !== 'overall', function ($query) use ($groupBy) {
                return $query->where('dimension', $groupBy);
            })
            ->select([
                'dimension_value as category',
                DB::raw('AVG(metric_value) as avg_days'),
                DB::raw('MIN(metric_value) as min_days'),
                DB::raw('MAX(metric_value) as max_days'),
                DB::raw('COUNT(*) as sample_size')
            ])
            ->groupBy('dimension_value')
            ->orderBy('avg_days', 'desc')
            ->get();

        return $this->ok($results);
    }

    public function sourceAttribution(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $dateRange = $this->getDateRange($request);
        
        $sources = DB::table('source_attributions')
            ->join('job_applications', 'job_applications.id', '=', 'source_attributions.application_id')
            ->where('source_attributions.employer_user_id', $u->id)
            ->whereBetween('source_attributions.attributed_at', [$dateRange['start'], $dateRange['end']])
            ->select([
                'source_attributions.source_type',
                'source_attributions.source_name',
                DB::raw('COUNT(*) as applications_count'),
                DB::raw('SUM(CASE WHEN job_applications.status = "hired" THEN 1 ELSE 0 END) as hires_count'),
                DB::raw('SUM(source_attributions.cost) as total_cost'),
                DB::raw('AVG(source_attributions.cost) as avg_cost'),
                DB::raw('ROUND((SUM(CASE WHEN job_applications.status = "hired" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as conversion_rate')
            ])
            ->groupBy('source_attributions.source_type', 'source_attributions.source_name')
            ->orderBy('applications_count', 'desc')
            ->get();

        return $this->ok($sources);
    }

    public function costPerHire(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $dateRange = $this->getDateRange($request);
        
        $costs = DB::table('hiring_metrics')
            ->where('employer_user_id', $u->id)
            ->where('metric_type', 'cost_per_hire')
            ->whereBetween('period_start', [$dateRange['start'], $dateRange['end']])
            ->select([
                'dimension_value as category',
                DB::raw('AVG(metric_value) as avg_cost'),
                DB::raw('SUM(JSON_EXTRACT(metadata, "$.total_hires")) as total_hires'),
                DB::raw('SUM(JSON_EXTRACT(metadata, "$.total_cost")) as total_cost')
            ])
            ->groupBy('dimension_value')
            ->orderBy('avg_cost', 'desc')
            ->get();

        return $this->ok($costs);
    }

    public function funnelAnalysis(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $dateRange = $this->getDateRange($request);
        
        $funnel = DB::table('hiring_funnel_events')
            ->where('employer_user_id', $u->id)
            ->whereBetween('event_timestamp', [$dateRange['start'], $dateRange['end']])
            ->select([
                'stage',
                DB::raw('COUNT(DISTINCT application_id) as count'),
                DB::raw('AVG(days_from_posting) as avg_days_from_posting'),
                DB::raw('AVG(days_from_application) as avg_days_from_application')
            ])
            ->groupBy('stage')
            ->orderByRaw("FIELD(stage, 'posted', 'viewed', 'applied', 'screened', 'interviewed', 'offered', 'hired')")
            ->get();

        // Calculate conversion rates
        $totalPosted = $funnel->where('stage', 'posted')->first()->count ?? 1;
        
        $funnelWithRates = $funnel->map(function ($stage) use ($totalPosted) {
            $stage->conversion_rate = round(($stage->count / $totalPosted) * 100, 2);
            return $stage;
        });

        return $this->ok($funnelWithRates);
    }

    // ── Market Intelligence Reports ──────────────────────────────────────
    public function salaryBenchmarks(Request $request): JsonResponse
    {
        $data = $request->validate([
            'job_title' => 'nullable|string',
            'location' => 'nullable|string',
            'experience_level' => 'nullable|in:entry,mid,senior,executive',
        ]);

        $query = DB::table('salary_benchmarks')
            ->where('data_date', '>=', now()->subMonths(6))
            ->when($data['job_title'] ?? null, function ($q, $title) {
                return $q->where('normalized_title', 'like', "%{$title}%");
            })
            ->when($data['location'] ?? null, function ($q, $location) {
                return $q->where('location_value', 'like', "%{$location}%");
            })
            ->when($data['experience_level'] ?? null, function ($q, $level) {
                return $q->where('experience_level', $level);
            });

        $benchmarks = $query
            ->select([
                'normalized_title',
                'location_value',
                'experience_level',
                'employment_type',
                DB::raw('AVG(salary_median) as median_salary'),
                DB::raw('AVG(salary_min) as min_salary'),
                DB::raw('AVG(salary_max) as max_salary'),
                DB::raw('SUM(sample_size) as total_samples'),
                'currency'
            ])
            ->groupBy('normalized_title', 'location_value', 'experience_level', 'employment_type', 'currency')
            ->orderBy('median_salary', 'desc')
            ->get();

        return $this->ok($benchmarks);
    }

    public function supplyDemandHeatmap(Request $request): JsonResponse
    {
        $data = $request->validate([
            'job_category' => 'nullable|string',
            'period_months' => 'integer|min:1|max:12',
        ]);

        $months = $data['period_months'] ?? 3;
        $startDate = now()->subMonths($months)->startOfMonth();

        $heatmapData = DB::table('supply_demand_metrics')
            ->where('period_month', '>=', $startDate)
            ->when($data['job_category'] ?? null, function ($q, $category) {
                return $q->where('job_category', $category);
            })
            ->select([
                'job_category',
                'specialty',
                'location_value',
                DB::raw('AVG(supply_demand_ratio) as avg_supply_demand'),
                DB::raw('AVG(avg_time_to_fill) as avg_time_to_fill'),
                DB::raw('AVG(competition_index) as competition_level'),
                DB::raw('SUM(job_postings_count) as total_jobs'),
                DB::raw('SUM(applications_count) as total_applications')
            ])
            ->groupBy('job_category', 'specialty', 'location_value')
            ->orderBy('competition_level', 'desc')
            ->get();

        return $this->ok($heatmapData);
    }

    public function competitorAnalysis(Request $request): JsonResponse
    {
        $data = $request->validate([
            'location' => 'nullable|string',
            'job_category' => 'nullable|string',
            'days_back' => 'integer|min:1|max:90',
        ]);

        $daysBack = $data['days_back'] ?? 30;
        $startDate = now()->subDays($daysBack);

        $competitors = DB::table('competitor_intelligence')
            ->where('posted_at', '>=', $startDate)
            ->where('is_active', true)
            ->when($data['location'] ?? null, function ($q, $location) {
                return $q->where('location', 'like', "%{$location}%");
            })
            ->when($data['job_category'] ?? null, function ($q, $category) {
                return $q->where('normalized_title', 'like', "%{$category}%");
            })
            ->select([
                'competitor_name',
                DB::raw('COUNT(*) as job_postings_count'),
                DB::raw('AVG(salary_min) as avg_salary_min'),
                DB::raw('AVG(salary_max) as avg_salary_max'),
                DB::raw('COUNT(DISTINCT normalized_title) as unique_roles'),
                DB::raw('COUNT(DISTINCT location) as locations_count')
            ])
            ->groupBy('competitor_name')
            ->orderBy('job_postings_count', 'desc')
            ->limit(20)
            ->get();

        return $this->ok($competitors);
    }

    public function trendingSkills(Request $request): JsonResponse
    {
        $data = $request->validate([
            'job_category' => 'nullable|string',
            'location' => 'nullable|string',
            'months_back' => 'integer|min:1|max:12',
        ]);

        $monthsBack = $data['months_back'] ?? 6;
        $startDate = now()->subMonths($monthsBack)->startOfMonth();

        $skills = DB::table('trending_skills')
            ->where('period_month', '>=', $startDate)
            ->when($data['job_category'] ?? null, function ($q, $category) {
                return $q->where('job_category', $category);
            })
            ->when($data['location'] ?? null, function ($q, $location) {
                return $q->where('location', 'like', "%{$location}%");
            })
            ->select([
                'skill_name',
                'skill_category',
                DB::raw('AVG(demand_score) as avg_demand_score'),
                DB::raw('AVG(growth_rate) as avg_growth_rate'),
                DB::raw('SUM(mention_count) as total_mentions'),
                DB::raw('AVG(avg_salary_premium) as salary_premium')
            ])
            ->groupBy('skill_name', 'skill_category')
            ->orderBy('avg_demand_score', 'desc')
            ->limit(50)
            ->get();

        return $this->ok($skills);
    }

    // ── Custom Report Builder ────────────────────────────────────────────
    public function getCustomReports(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $reports = DB::table('custom_reports')
            ->where('employer_user_id', $u->id)
            ->orWhere('is_public', true)
            ->select([
                'id', 'name', 'description', 'report_type', 'schedule_frequency',
                'is_active', 'is_public', 'last_generated_at', 'created_at'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->ok($reports);
    }

    public function createCustomReport(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'report_type' => 'required|in:hiring_analytics,market_intelligence,custom_query',
            'data_sources' => 'required|array',
            'filters' => 'nullable|array',
            'grouping' => 'nullable|array',
            'metrics' => 'required|array',
            'visualization_config' => 'nullable|array',
            'schedule_frequency' => 'nullable|in:daily,weekly,monthly,quarterly',
            'schedule_config' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        $id = DB::table('custom_reports')->insertGetId([
            'employer_user_id' => $u->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'report_type' => $data['report_type'],
            'data_sources' => json_encode($data['data_sources']),
            'filters' => isset($data['filters']) ? json_encode($data['filters']) : null,
            'grouping' => isset($data['grouping']) ? json_encode($data['grouping']) : null,
            'metrics' => json_encode($data['metrics']),
            'visualization_config' => isset($data['visualization_config']) ? json_encode($data['visualization_config']) : null,
            'schedule_frequency' => $data['schedule_frequency'] ?? null,
            'schedule_config' => isset($data['schedule_config']) ? json_encode($data['schedule_config']) : null,
            'is_active' => true,
            'is_public' => $data['is_public'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(['id' => $id], 'Custom report created', 201);
    }

    public function executeCustomReport(Request $request, int $reportId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $report = DB::table('custom_reports')
            ->where('id', $reportId)
            ->where(function ($query) use ($u) {
                $query->where('employer_user_id', $u->id)
                      ->orWhere('is_public', true);
            })
            ->first();

        if (!$report) return $this->fail('Report not found', null, 404);

        $executionId = DB::table('report_executions')->insertGetId([
            'custom_report_id' => $reportId,
            'executed_by_user_id' => $u->id,
            'trigger_type' => 'manual',
            'status' => 'running',
            'parameters' => json_encode($request->all()),
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $startTime = microtime(true);
            
            // Execute the report based on its configuration
            $results = $this->executeReportQuery($report, $request->all());
            
            $executionTime = (microtime(true) - $startTime) * 1000;

            DB::table('report_executions')
                ->where('id', $executionId)
                ->update([
                    'status' => 'completed',
                    'results' => json_encode($results),
                    'execution_time_ms' => $executionTime,
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            return $this->ok([
                'execution_id' => $executionId,
                'results' => $results,
                'execution_time_ms' => $executionTime,
            ]);

        } catch (\Exception $e) {
            DB::table('report_executions')
                ->where('id', $executionId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            return $this->fail('Report execution failed: ' . $e->getMessage(), null, 500);
        }
    }

    public function exportReport(Request $request, int $reportId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $data = $request->validate([
            'format' => 'required|in:pdf,excel,csv',
            'execution_id' => 'nullable|exists:report_executions,id',
        ]);

        // Implementation would generate file and return download URL
        // For now, return success with placeholder
        
        return $this->ok([
            'download_url' => '/api/reports/' . $reportId . '/download/' . uniqid(),
            'format' => $data['format'],
            'expires_at' => now()->addHours(24)->toISOString(),
        ]);
    }

    public function getIndustryBenchmarks(Request $request): JsonResponse
    {
        $data = $request->validate([
            'industry' => 'nullable|string',
            'metric_name' => 'nullable|string',
            'organization_size' => 'nullable|in:small,medium,large,enterprise',
        ]);

        $benchmarks = DB::table('industry_benchmarks')
            ->where('period_end', '>=', now()->subYear())
            ->when($data['industry'] ?? null, function ($q, $industry) {
                return $q->where('industry', $industry);
            })
            ->when($data['metric_name'] ?? null, function ($q, $metric) {
                return $q->where('metric_name', $metric);
            })
            ->when($data['organization_size'] ?? null, function ($q, $size) {
                return $q->where('organization_size', $size);
            })
            ->select([
                'industry', 'metric_name', 'organization_size', 'location_value',
                'benchmark_value', 'percentile_25', 'percentile_50', 'percentile_75',
                'unit', 'sample_size', 'period_start', 'period_end'
            ])
            ->orderBy('metric_name')
            ->orderBy('industry')
            ->get();

        return $this->ok($benchmarks);
    }

    // ── Helper Methods ───────────────────────────────────────────────────
    private function getDateRange(Request $request): array
    {
        $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        
        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    private function getHiringOverview(int $employerId, array $dateRange): array
    {
        return [
            'total_jobs_posted' => DB::table('jobs_table')->where('owner_user_id', $employerId)->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'total_applications' => DB::table('job_applications')->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')->where('jobs_table.owner_user_id', $employerId)->whereBetween('job_applications.created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'total_hires' => DB::table('job_applications')->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')->where('jobs_table.owner_user_id', $employerId)->where('job_applications.status', 'hired')->whereBetween('job_applications.updated_at', [$dateRange['start'], $dateRange['end']])->count(),
            'avg_time_to_hire' => DB::table('hiring_metrics')->where('employer_user_id', $employerId)->where('metric_type', 'time_to_hire')->whereBetween('period_start', [$dateRange['start'], $dateRange['end']])->avg('metric_value'),
        ];
    }

    private function getTimeToHireMetrics(int $employerId, array $dateRange): array
    {
        return DB::table('hiring_metrics')
            ->where('employer_user_id', $employerId)
            ->where('metric_type', 'time_to_hire')
            ->whereBetween('period_start', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('
                AVG(metric_value) as average,
                MIN(metric_value) as minimum,
                MAX(metric_value) as maximum,
                COUNT(*) as sample_size
            ')
            ->first() ?: [];
    }

    private function getSourceAttribution(int $employerId, array $dateRange): array
    {
        return DB::table('source_attributions')
            ->where('employer_user_id', $employerId)
            ->whereBetween('attributed_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('
                source_type,
                COUNT(*) as count,
                SUM(cost) as total_cost
            ')
            ->groupBy('source_type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    private function getCostPerHire(int $employerId, array $dateRange): array
    {
        return DB::table('hiring_metrics')
            ->where('employer_user_id', $employerId)
            ->where('metric_type', 'cost_per_hire')
            ->whereBetween('period_start', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('AVG(metric_value) as average_cost')
            ->first() ?: [];
    }

    private function getFunnelAnalysis(int $employerId, array $dateRange): array
    {
        return DB::table('hiring_funnel_events')
            ->where('employer_user_id', $employerId)
            ->whereBetween('event_timestamp', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('stage, COUNT(DISTINCT application_id) as count')
            ->groupBy('stage')
            ->orderByRaw("FIELD(stage, 'posted', 'viewed', 'applied', 'screened', 'interviewed', 'offered', 'hired')")
            ->get()
            ->toArray();
    }

    private function getHiringTrends(int $employerId, array $dateRange): array
    {
        // Weekly trends for the date range
        return DB::table('hiring_funnel_events')
            ->where('employer_user_id', $employerId)
            ->whereBetween('event_timestamp', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('
                WEEK(event_timestamp) as week,
                stage,
                COUNT(*) as count
            ')
            ->groupBy('week', 'stage')
            ->orderBy('week')
            ->get()
            ->groupBy('week')
            ->toArray();
    }

    private function executeReportQuery(object $report, array $parameters): array
    {
        // This would contain the logic to execute different types of reports
        // based on the report configuration. For now, return sample data.
        
        return [
            'data' => [
                ['metric' => 'Time to Hire', 'value' => 14.5, 'unit' => 'days'],
                ['metric' => 'Cost per Hire', 'value' => 3250, 'unit' => 'USD'],
                ['metric' => 'Applications per Job', 'value' => 23, 'unit' => 'count'],
            ],
            'generated_at' => now()->toISOString(),
            'parameters' => $parameters,
        ];
    }
}