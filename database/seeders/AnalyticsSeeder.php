<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSalaryBenchmarks();
        $this->seedSupplyDemandData();
        $this->seedTrendingSkills();
        $this->seedCompetitorJobs();
        $this->seedIndustryBenchmarks();
        $this->seedSampleApplicationSources();
        $this->seedSampleHiringCosts();
    }

    private function seedSalaryBenchmarks(): void
    {
        $benchmarks = [
            // Philippines - Nursing Roles
            [
                'job_title' => 'Registered Nurse',
                'normalized_title' => 'Registered Nurse',
                'country' => 'Philippines',
                'state' => 'NCR',
                'city' => 'Manila',
                'employment_type' => 'full_time',
                'years_experience_min' => 0,
                'years_experience_max' => 2,
                'salary_min_cents' => 3500000, // PHP 35,000
                'salary_max_cents' => 5000000, // PHP 50,000
                'salary_median_cents' => 4200000, // PHP 42,000
                'salary_p25_cents' => 3800000,
                'salary_p75_cents' => 4600000,
                'currency_code' => 'PHP',
                'sample_size' => 150,
                'data_date' => now()->subDays(7),
            ],
            [
                'job_title' => 'Staff Nurse',
                'normalized_title' => 'Staff Nurse',
                'country' => 'Philippines',
                'state' => 'Cebu',
                'city' => 'Cebu City',
                'employment_type' => 'full_time',
                'years_experience_min' => 1,
                'years_experience_max' => 5,
                'salary_min_cents' => 3200000,
                'salary_max_cents' => 4500000,
                'salary_median_cents' => 3800000,
                'salary_p25_cents' => 3400000,
                'salary_p75_cents' => 4200000,
                'currency_code' => 'PHP',
                'sample_size' => 89,
                'data_date' => now()->subDays(7),
            ],
            [
                'job_title' => 'ICU Nurse',
                'normalized_title' => 'ICU Nurse',
                'country' => 'Philippines',
                'state' => 'NCR',
                'city' => 'Quezon City',
                'employment_type' => 'full_time',
                'years_experience_min' => 2,
                'years_experience_max' => 8,
                'salary_min_cents' => 4500000,
                'salary_max_cents' => 7000000,
                'salary_median_cents' => 5500000,
                'salary_p25_cents' => 5000000,
                'salary_p75_cents' => 6200000,
                'currency_code' => 'PHP',
                'sample_size' => 67,
                'data_date' => now()->subDays(7),
            ],
            [
                'job_title' => 'Emergency Room Nurse',
                'normalized_title' => 'ER Nurse',
                'country' => 'Philippines',
                'state' => 'NCR',
                'city' => 'Makati',
                'employment_type' => 'full_time',
                'years_experience_min' => 1,
                'years_experience_max' => 6,
                'salary_min_cents' => 4200000,
                'salary_max_cents' => 6500000,
                'salary_median_cents' => 5200000,
                'salary_p25_cents' => 4600000,
                'salary_p75_cents' => 5800000,
                'currency_code' => 'PHP',
                'sample_size' => 45,
                'data_date' => now()->subDays(7),
            ],
            [
                'job_title' => 'Pediatric Nurse',
                'normalized_title' => 'Pediatric Nurse',
                'country' => 'Philippines',
                'state' => 'Davao',
                'city' => 'Davao City',
                'employment_type' => 'full_time',
                'years_experience_min' => 1,
                'years_experience_max' => 5,
                'salary_min_cents' => 3800000,
                'salary_max_cents' => 5500000,
                'salary_median_cents' => 4500000,
                'salary_p25_cents' => 4100000,
                'salary_p75_cents' => 5000000,
                'currency_code' => 'PHP',
                'sample_size' => 32,
                'data_date' => now()->subDays(7),
            ],
        ];

        foreach ($benchmarks as $benchmark) {
            $benchmark['created_at'] = now();
            $benchmark['updated_at'] = now();
            DB::table('salary_benchmarks')->insert($benchmark);
        }
    }

    private function seedSupplyDemandData(): void
    {
        $supplyDemand = [
            [
                'job_category' => 'Nurse',
                'specialty' => 'General',
                'country' => 'Philippines',
                'state' => 'NCR',
                'city' => 'Manila',
                'active_jobs_count' => 245,
                'active_candidates_count' => 89,
                'demand_supply_ratio' => 2.75,
                'market_temperature' => 'hot',
                'avg_time_to_fill_days' => 18.5,
                'snapshot_date' => now()->subDays(1),
            ],
            [
                'job_category' => 'Nurse',
                'specialty' => 'ICU',
                'country' => 'Philippines',
                'state' => 'NCR',
                'city' => 'Quezon City',
                'active_jobs_count' => 78,
                'active_candidates_count' => 23,
                'demand_supply_ratio' => 3.39,
                'market_temperature' => 'very_hot',
                'avg_time_to_fill_days' => 25.2,
                'snapshot_date' => now()->subDays(1),
            ],
            [
                'job_category' => 'Nurse',
                'specialty' => 'General',
                'country' => 'Philippines',
                'state' => 'Cebu',
                'city' => 'Cebu City',
                'active_jobs_count' => 156,
                'active_candidates_count' => 134,
                'demand_supply_ratio' => 1.16,
                'market_temperature' => 'warm',
                'avg_time_to_fill_days' => 12.8,
                'snapshot_date' => now()->subDays(1),
            ],
            [
                'job_category' => 'Doctor',
                'specialty' => 'General Practice',
                'country' => 'Philippines',
                'state' => 'NCR',
                'city' => 'Makati',
                'active_jobs_count' => 34,
                'active_candidates_count' => 12,
                'demand_supply_ratio' => 2.83,
                'market_temperature' => 'hot',
                'avg_time_to_fill_days' => 35.6,
                'snapshot_date' => now()->subDays(1),
            ],
            [
                'job_category' => 'Allied Health',
                'specialty' => 'Physical Therapy',
                'country' => 'Philippines',
                'state' => 'Davao',
                'city' => 'Davao City',
                'active_jobs_count' => 23,
                'active_candidates_count' => 45,
                'demand_supply_ratio' => 0.51,
                'market_temperature' => 'cool',
                'avg_time_to_fill_days' => 8.3,
                'snapshot_date' => now()->subDays(1),
            ],
        ];

        foreach ($supplyDemand as $data) {
            $data['created_at'] = now();
            $data['updated_at'] = now();
            DB::table('market_supply_demand')->insert($data);
        }
    }

    private function seedTrendingSkills(): void
    {
        $skills = [
            [
                'skill_name' => 'BLS Certification',
                'skill_category' => 'certification',
                'mention_count' => 456,
                'demand_growth_pct' => 15,
                'trend_direction' => 'rising',
                'period_start' => now()->subDays(90),
                'period_end' => now(),
            ],
            [
                'skill_name' => 'ICU Experience',
                'skill_category' => 'clinical',
                'mention_count' => 389,
                'demand_growth_pct' => 8,
                'trend_direction' => 'rising',
                'period_start' => now()->subDays(90),
                'period_end' => now(),
            ],
            [
                'skill_name' => 'ACLS Certification',
                'skill_category' => 'certification',
                'mention_count' => 234,
                'demand_growth_pct' => 22,
                'trend_direction' => 'rising',
                'period_start' => now()->subDays(90),
                'period_end' => now(),
            ],
            [
                'skill_name' => 'Pediatric Care',
                'skill_category' => 'clinical',
                'mention_count' => 198,
                'demand_growth_pct' => 5,
                'trend_direction' => 'stable',
                'period_start' => now()->subDays(90),
                'period_end' => now(),
            ],
            [
                'skill_name' => 'Emergency Medicine',
                'skill_category' => 'clinical',
                'mention_count' => 167,
                'demand_growth_pct' => 12,
                'trend_direction' => 'rising',
                'period_start' => now()->subDays(90),
                'period_end' => now(),
            ],
            [
                'skill_name' => 'PALS Certification',
                'skill_category' => 'certification',
                'mention_count' => 145,
                'demand_growth_pct' => 18,
                'trend_direction' => 'rising',
                'period_start' => now()->subDays(90),
                'period_end' => now(),
            ],
            [
                'skill_name' => 'Wound Care',
                'skill_category' => 'clinical',
                'mention_count' => 123,
                'demand_growth_pct' => -3,
                'trend_direction' => 'declining',
                'period_start' => now()->subDays(90),
                'period_end' => now(),
            ],
            [
                'skill_name' => 'Medication Administration',
                'skill_category' => 'clinical',
                'mention_count' => 298,
                'demand_growth_pct' => 2,
                'trend_direction' => 'stable',
                'period_start' => now()->subDays(90),
                'period_end' => now(),
            ],
        ];

        foreach ($skills as $skill) {
            $skill['created_at'] = now();
            $skill['updated_at'] = now();
            DB::table('trending_skills')->insert($skill);
        }
    }

    private function seedCompetitorJobs(): void
    {
        $competitors = [
            [
                'competitor_name' => 'HealthJobs PH',
                'job_title' => 'Registered Nurse - ICU',
                'normalized_title' => 'ICU Nurse',
                'location' => 'Manila, NCR',
                'description_snippet' => 'Seeking experienced ICU nurse for tertiary hospital...',
                'salary_min_cents' => 4500000,
                'salary_max_cents' => 6000000,
                'currency_code' => 'PHP',
                'source_url' => 'https://healthjobs.ph/job/12345',
                'posted_date' => now()->subDays(3),
                'scraped_date' => now()->subDays(1),
                'is_active' => true,
            ],
            [
                'competitor_name' => 'JobStreet',
                'job_title' => 'Staff Nurse',
                'normalized_title' => 'Staff Nurse',
                'location' => 'Cebu City, Cebu',
                'description_snippet' => 'Join our growing healthcare team as a Staff Nurse...',
                'salary_min_cents' => 3200000,
                'salary_max_cents' => 4200000,
                'currency_code' => 'PHP',
                'source_url' => 'https://jobstreet.com.ph/job/67890',
                'posted_date' => now()->subDays(5),
                'scraped_date' => now()->subDays(1),
                'is_active' => true,
            ],
            [
                'competitor_name' => 'Indeed Philippines',
                'job_title' => 'Emergency Room Nurse',
                'normalized_title' => 'ER Nurse',
                'location' => 'Makati, NCR',
                'description_snippet' => 'Fast-paced ER environment seeking dedicated nurses...',
                'salary_min_cents' => 4000000,
                'salary_max_cents' => 5500000,
                'currency_code' => 'PHP',
                'source_url' => 'https://ph.indeed.com/job/54321',
                'posted_date' => now()->subDays(2),
                'scraped_date' => now()->subDays(1),
                'is_active' => true,
            ],
            [
                'competitor_name' => 'Kalibrr',
                'job_title' => 'Pediatric Nurse',
                'normalized_title' => 'Pediatric Nurse',
                'location' => 'Davao City, Davao',
                'description_snippet' => 'Children\'s hospital seeking compassionate pediatric nurses...',
                'salary_min_cents' => 3800000,
                'salary_max_cents' => 5000000,
                'currency_code' => 'PHP',
                'source_url' => 'https://kalibrr.com/job/98765',
                'posted_date' => now()->subDays(4),
                'scraped_date' => now()->subDays(1),
                'is_active' => true,
            ],
        ];

        foreach ($competitors as $competitor) {
            $competitor['created_at'] = now();
            $competitor['updated_at'] = now();
            DB::table('competitor_jobs')->insert($competitor);
        }
    }

    private function seedIndustryBenchmarks(): void
    {
        $benchmarks = [
            [
                'metric_name' => 'avg_time_to_hire',
                'industry' => 'healthcare',
                'region' => 'Philippines',
                'company_size' => 'medium',
                'benchmark_value' => 21.5,
                'unit' => 'days',
                'period_start' => now()->subMonths(6),
                'period_end' => now(),
                'data_source' => 'Industry Survey 2026',
            ],
            [
                'metric_name' => 'avg_cost_per_hire',
                'industry' => 'healthcare',
                'region' => 'Philippines',
                'company_size' => 'medium',
                'benchmark_value' => 15000.00,
                'unit' => 'PHP',
                'period_start' => now()->subMonths(6),
                'period_end' => now(),
                'data_source' => 'Industry Survey 2026',
            ],
            [
                'metric_name' => 'offer_acceptance_rate',
                'industry' => 'healthcare',
                'region' => 'Philippines',
                'company_size' => 'medium',
                'benchmark_value' => 78.5,
                'unit' => 'percentage',
                'period_start' => now()->subMonths(6),
                'period_end' => now(),
                'data_source' => 'Industry Survey 2026',
            ],
            [
                'metric_name' => 'application_to_interview_rate',
                'industry' => 'healthcare',
                'region' => 'Philippines',
                'company_size' => 'medium',
                'benchmark_value' => 12.3,
                'unit' => 'percentage',
                'period_start' => now()->subMonths(6),
                'period_end' => now(),
                'data_source' => 'Industry Survey 2026',
            ],
        ];

        foreach ($benchmarks as $benchmark) {
            $benchmark['created_at'] = now();
            $benchmark['updated_at'] = now();
            DB::table('industry_benchmarks')->insert($benchmark);
        }
    }

    private function seedSampleApplicationSources(): void
    {
        // This would typically be populated when applications are created
        // For now, we'll create some sample data
        $sources = [
            [
                'application_id' => 1, // Assuming some applications exist
                'source_type' => 'direct',
                'source_name' => 'Company Website',
                'utm_source' => null,
                'utm_medium' => null,
                'utm_campaign' => null,
                'referrer_url' => 'https://aiclinforce.com/jobs',
                'ip_address' => '192.168.1.1',
            ],
            [
                'application_id' => 2,
                'source_type' => 'job_board',
                'source_name' => 'JobStreet',
                'utm_source' => 'jobstreet',
                'utm_medium' => 'job_board',
                'utm_campaign' => 'nursing_jobs',
                'referrer_url' => 'https://jobstreet.com.ph',
                'ip_address' => '192.168.1.2',
            ],
        ];

        foreach ($sources as $source) {
            $source['created_at'] = now();
            $source['updated_at'] = now();
            // Only insert if application exists
            if (DB::table('job_applications')->where('id', $source['application_id'])->exists()) {
                DB::table('application_sources')->insert($source);
            }
        }
    }

    private function seedSampleHiringCosts(): void
    {
        // Sample hiring costs - would be populated by employers
        $costs = [
            [
                'employer_user_id' => 1, // Assuming some employer users exist
                'job_id' => null,
                'application_id' => null,
                'cost_type' => 'job_board_fee',
                'amount_cents' => 500000, // PHP 5,000
                'currency_code' => 'PHP',
                'description' => 'JobStreet premium posting fee',
                'incurred_date' => now()->subDays(15),
            ],
            [
                'employer_user_id' => 1,
                'job_id' => null,
                'application_id' => null,
                'cost_type' => 'advertising',
                'amount_cents' => 300000, // PHP 3,000
                'currency_code' => 'PHP',
                'description' => 'Facebook job ad campaign',
                'incurred_date' => now()->subDays(10),
            ],
        ];

        foreach ($costs as $cost) {
            $cost['created_at'] = now();
            $cost['updated_at'] = now();
            // Only insert if employer user exists
            if (DB::table('users')->where('id', $cost['employer_user_id'])->where('role', 'employer')->exists()) {
                DB::table('hiring_costs')->insert($cost);
            }
        }
    }
}