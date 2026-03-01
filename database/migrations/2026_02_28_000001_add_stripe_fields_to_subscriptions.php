<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'stripe_customer_id')) {
                $table->string('stripe_customer_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('subscriptions', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable()->unique()->after('stripe_customer_id');
            }
            if (!Schema::hasColumn('subscriptions', 'stripe_price_id')) {
                $table->string('stripe_price_id')->nullable()->after('stripe_subscription_id');
            }
            if (!Schema::hasColumn('subscriptions', 'current_period_end')) {
                $table->timestamp('current_period_end')->nullable()->after('end_at');
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'stripe_price_id')) {
                $table->string('stripe_price_id')->nullable()->after('name');
            }
            if (!Schema::hasColumn('plans', 'features_json')) {
                $table->json('features_json')->nullable()->after('analytics_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['stripe_customer_id', 'stripe_subscription_id', 'stripe_price_id', 'current_period_end']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['stripe_price_id', 'features_json']);
        });
    }
};
