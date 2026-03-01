<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'duration_months' => 1,
                'job_post_limit' => 5,
                'ai_screening_enabled' => false,
                'analytics_enabled' => false,
                'price_cents' => 4900, // $49.00
                'currency' => 'USD',
                'is_active' => true,
                'stripe_price_id' => null, // Set this after creating in Stripe
                'features_json' => json_encode([
                    'job_posts' => 5,
                    'applicant_access' => true,
                    'resume_downloads' => true,
                    'messaging' => true,
                    'support' => 'email',
                ]),
            ],
            [
                'name' => 'Professional',
                'duration_months' => 1,
                'job_post_limit' => 20,
                'ai_screening_enabled' => true,
                'analytics_enabled' => true,
                'price_cents' => 9900, // $99.00
                'currency' => 'USD',
                'is_active' => true,
                'stripe_price_id' => null,
                'features_json' => json_encode([
                    'job_posts' => 20,
                    'applicant_access' => true,
                    'resume_downloads' => true,
                    'messaging' => true,
                    'ai_screening' => true,
                    'analytics' => true,
                    'support' => 'priority',
                ]),
            ],
            [
                'name' => 'Enterprise',
                'duration_months' => 1,
                'job_post_limit' => -1, // Unlimited
                'ai_screening_enabled' => true,
                'analytics_enabled' => true,
                'price_cents' => 19900, // $199.00
                'currency' => 'USD',
                'is_active' => true,
                'stripe_price_id' => null,
                'features_json' => json_encode([
                    'job_posts' => 'unlimited',
                    'applicant_access' => true,
                    'resume_downloads' => true,
                    'messaging' => true,
                    'ai_screening' => true,
                    'analytics' => true,
                    'advanced_analytics' => true,
                    'api_access' => true,
                    'dedicated_support' => true,
                    'support' => '24/7',
                ]),
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->updateOrInsert(
                ['name' => $plan['name']],
                array_merge($plan, [
                    'created_at' => now(),
                ])
            );
        }

        $this->command->info('Plans seeded successfully!');
        $this->command->warn('Remember to update stripe_price_id after creating products in Stripe Dashboard');
    }
}
