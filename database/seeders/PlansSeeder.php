<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate and re-seed for clean state
        Plan::query()->delete();

        $plans = [
            // ── Philippines Plans (PHP) ──────────────────────────────────────
            [
                'id'                   => 1,
                'name'                 => 'PH Starter – 3 Months',
                'stripe_price_id'      => 'price_ph_3mo',
                'duration_months'      => 3,
                'job_post_limit'       => 20,
                'ai_screening_enabled' => true,
                'analytics_enabled'    => true,
                'price_cents'          => 299999,   // ₱2,999.99
                'currency'             => 'PHP',
                'is_active'            => true,
                'features_json'        => [
                    'Up to 20 job posts',
                    'AI candidate screening',
                    'Analytics dashboard',
                    'Email & chat support',
                    '3-month access',
                ],
                'created_at'           => now(),
            ],
            [
                'id'                   => 2,
                'name'                 => 'PH Growth – 6 Months',
                'stripe_price_id'      => 'price_ph_6mo',
                'duration_months'      => 6,
                'job_post_limit'       => 50,
                'ai_screening_enabled' => true,
                'analytics_enabled'    => true,
                'price_cents'          => 499999,   // ₱4,999.99
                'currency'             => 'PHP',
                'is_active'            => true,
                'features_json'        => [
                    'Up to 50 job posts',
                    'AI candidate screening',
                    'Advanced analytics',
                    'Priority support',
                    '6-month access',
                ],
                'created_at'           => now(),
            ],
            [
                'id'                   => 3,
                'name'                 => 'PH Pro – 1 Year',
                'stripe_price_id'      => 'price_ph_1yr',
                'duration_months'      => 12,
                'job_post_limit'       => 999,
                'ai_screening_enabled' => true,
                'analytics_enabled'    => true,
                'price_cents'          => 799999,   // ₱7,999.99
                'currency'             => 'PHP',
                'is_active'            => true,
                'features_json'        => [
                    'Unlimited job posts',
                    'AI candidate screening',
                    'Advanced analytics',
                    'Dedicated support',
                    'Custom branding',
                    '1-year access',
                ],
                'created_at'           => now(),
            ],

            // ── International Plans (USD) ────────────────────────────────────
            [
                'id'                   => 4,
                'name'                 => 'International – 7 Day Trial',
                'stripe_price_id'      => 'price_intl_trial',
                'duration_months'      => 0,        // 7 days — handled by trial logic
                'job_post_limit'       => 5,
                'ai_screening_enabled' => false,
                'analytics_enabled'    => false,
                'price_cents'          => 0,        // Free trial
                'currency'             => 'USD',
                'is_active'            => true,
                'features_json'        => [
                    'Up to 5 job posts',
                    'Basic applicant tracking',
                    '7-day free access',
                    'No credit card required',
                ],
                'created_at'           => now(),
            ],
            [
                'id'                   => 5,
                'name'                 => 'International – 3 Months',
                'stripe_price_id'      => 'price_intl_3mo',
                'duration_months'      => 3,
                'job_post_limit'       => 20,
                'ai_screening_enabled' => true,
                'analytics_enabled'    => true,
                'price_cents'          => 4999,     // $49.99
                'currency'             => 'USD',
                'is_active'            => true,
                'features_json'        => [
                    'Up to 20 job posts',
                    'AI candidate screening',
                    'Analytics dashboard',
                    'Email & chat support',
                    '3-month access',
                ],
                'created_at'           => now(),
            ],
            [
                'id'                   => 6,
                'name'                 => 'International – 6 Months',
                'stripe_price_id'      => 'price_intl_6mo',
                'duration_months'      => 6,
                'job_post_limit'       => 50,
                'ai_screening_enabled' => true,
                'analytics_enabled'    => true,
                'price_cents'          => 7999,     // $79.99
                'currency'             => 'USD',
                'is_active'            => true,
                'features_json'        => [
                    'Up to 50 job posts',
                    'AI candidate screening',
                    'Advanced analytics',
                    'Priority support',
                    '6-month access',
                ],
                'created_at'           => now(),
            ],
            [
                'id'                   => 7,
                'name'                 => 'International – 1 Year',
                'stripe_price_id'      => 'price_intl_1yr',
                'duration_months'      => 12,
                'job_post_limit'       => 999,
                'ai_screening_enabled' => true,
                'analytics_enabled'    => true,
                'price_cents'          => 12999,    // $129.99
                'currency'             => 'USD',
                'is_active'            => true,
                'features_json'        => [
                    'Unlimited job posts',
                    'AI candidate screening',
                    'Advanced analytics',
                    'Dedicated support',
                    'Custom branding',
                    '1-year access',
                ],
                'created_at'           => now(),
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
