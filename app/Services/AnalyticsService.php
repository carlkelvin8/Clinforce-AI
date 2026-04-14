<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Calculate and cache hiring metrics for an employer
     */
    public function calculateHiringMetrics(int $employerUserId, \DateTime $startDate, \DateTime $endDate): array
    {
        $applications = JobApplication::query()
            ->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')
            ->where('jobs_table.owner_user_id', $employerUserId)
            ->whereBetween('job_applications.created_at', [$startDate, $endDate])
            ->get();

        $metrics = [];

        // Time to Hire
        $hiredApps = $applications->whereNotNull('hired_at');
        if ($hiredApps->count() > 0) {
            $avgTimeToHire = $hiredApps->avg(function ($app) {
                return $app->created_at->diffInDays($app->hired_at);
            });
            $metrics['time_to_hire'] = round($avgTimeToHire, 2);
        }

        // Cost per Hire
        $totalCosts = DB::table('hiring_costs')
            ->where('employer_user_id', $employerUserId)
            ->whereBetween('incurred_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->sum('amount_cents');
        
        $hiredCount = $hiredApps->count();
        $metrics['cost_per_hire'] = $hiredCount > 0 ? ($totalCosts / 100) / $hiredCount : 0;

        // Funnel Conversion Rates
        $totalApps = $applications->count();
        $metrics['funnel'] = [
            'applied' => $totalApps,
            'viewed' => $applications->whereNotNull('viewed_at')->count(),
            'shortlisted' => $applications->whereNotNull('shortlisted_at')->count(),
            'interviewed' => $applications->whereNotNull('interviewed_at')->count(),
            'offered' => $applications->whereNotNull('offered_at')->count(),
            'hired' => $hiredCount,
        ];

        return $metrics;
    }

    /**
     * Track application source for attribution
     */
    public function trackApplicationSource(int $applicationId, array $sourceData): void
    {
        DB::table('application_sources')->updateOrInsert(
            ['application_id' => $applicationId],
            [
                'source_type' => $sourceData['source_type'] ?? 'direct',
                'source_name' => $sourceData['source_name'] ?? null,
                'campaign_id' => $sourceData['campaign_id'] ?? null,
                'utm_source' => $sourceData['utm_source'] ?? null,
                'utm_medium' => $sourceData['utm_medium'] ?? null,
                'utm_campaign' => $sourceData['utm_campaign'] ?? null,
                'referrer_url' => $sourceData['referrer_url'] ?? null,
                'ip_address' => $sourceData['ip_address'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Update application funnel timestamps
     */
    public function updateFunnelTimestamp(int $applicationId, string $stage): void
    {
        $column = match($stage) {
            'viewed' => 'viewed_at',
            'shortlisted' => 'shortlisted_at',
            'interviewed' => 'interviewed_at',
            'offered' => 'offered_at',
            'hired' => 'hired_at',
            'rejected' => 'rejected_at',
            default => null,
        };

        if ($column) {
            JobApplication::where('id', $applicationId)
                ->whereNull($column)
                ->update([$column => now()]);
        }
    }

    /**
     * Calculate market supply/demand ratio
     */
    public function calculateSupplyDemand(string $jobCategory, string $country, string $state = null): array
    {
        $jobsQuery = DB::table('jobs_table')->where('status', 'published');
        $candidatesQuery = DB::table('applicant_profiles')
            ->join('users', 'users.id', '=', 'applicant_profiles.user_id')
            ->where('users.status', 'active');

        // Apply location filters
        if ($country) {
            $jobsQuery->where('country', $country);
            $candidatesQuery->where('applicant_profiles.country', $country);
        }
        if ($state) {
            $jobsQuery->where('state', $state);
            $candidatesQuery->where('applicant_profiles.state', $state);
        }

        // Apply category filter (simplified - would need better job categorization)
        if ($jobCategory) {
            $jobsQuery->where('title', 'like', "%{$jobCategory}%");
            $candidatesQuery->where('applicant_profiles.headline', 'like', "%{$jobCategory}%");
        }

        $jobsCount = $jobsQuery->count();
        $candidatesCount = $candidatesQuery->count();
        
        $ratio = $candidatesCount > 0 ? $jobsCount / $candidatesCount : 0;
        
        $temperature = match(true) {
            $ratio >= 3.0 => 'very_hot',
            $ratio >= 2.0 => 'hot',
            $ratio >= 1.0 => 'warm',
            $ratio >= 0.5 => 'cool',
            default => 'cold',
        };

        return [
            'active_jobs_count' => $jobsCount,
            'active_candidates_count' => $candidatesCount,
            'demand_supply_ratio' => round($ratio, 2),
            'market_temperature' => $temperature,
        ];
    }

    /**
     * Generate salary benchmark from job postings
     */
    public function generateSalaryBenchmark(string $normalizedTitle, string $country, string $state = null): ?array
    {
        $query = DB::table('jobs_table')->where('status', 'published')
            ->where('country', $country)
            ->whereNotNull('salary_min')
            ->whereNotNull('salary_max')
            ->where(function($q) use ($normalizedTitle) {
                $q->where('title', 'like', "%{$normalizedTitle}%");
            });

        if ($state) $query->where('state', $state);

        $jobs = $query->get(['salary_min', 'salary_max']);
        
        if ($jobs->count() < 3) return null; // Need minimum sample size

        $salaries = $jobs->flatMap(function($job) {
            return [$job->salary_min, $job->salary_max];
        })->sort()->values();

        $count = $salaries->count();
        $median = $salaries[$count / 2];
        $p25 = $salaries[$count * 0.25];
        $p75 = $salaries[$count * 0.75];

        return [
            'normalized_title' => $normalizedTitle,
            'country' => $country,
            'state' => $state,
            'salary_min_cents' => $salaries->min() * 100,
            'salary_max_cents' => $salaries->max() * 100,
            'salary_median_cents' => $median * 100,
            'salary_p25_cents' => $p25 * 100,
            'salary_p75_cents' => $p75 * 100,
            'sample_size' => $jobs->count(),
            'data_date' => now()->toDateString(),
        ];
    }
}