<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Plan;

class SubscriptionsSeeder extends Seeder
{
    public function run(): void
    {
        $employers = User::where('role', 'employer')->get();
        $plans = Plan::all();

        if ($employers->isEmpty() || $plans->isEmpty()) return;

        foreach ($employers as $i => $employer) {
            $plan = $plans[$i % $plans->count()];
            Subscription::firstOrCreate(
                ['user_id' => $employer->id],
                [
                    'plan_id' => $plan->id,
                    'stripe_customer_id' => 'cus_demo_' . $employer->id,
                    'stripe_subscription_id' => 'sub_demo_' . $employer->id,
                    'stripe_price_id' => $plan->stripe_price_id,
                    'currency_code' => 'USD',
                    'amount_cents' => $plan->price_cents,
                    'status' => 'active',
                    'start_at' => now()->subMonth(),
                    'end_at' => now()->addYear(),
                    'current_period_end' => now()->addMonth(),
                ]
            );
        }
    }
}
