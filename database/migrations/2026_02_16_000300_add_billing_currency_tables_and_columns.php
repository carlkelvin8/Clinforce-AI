<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id();
                $table->string('country_code', 2)->unique();
                $table->string('country_name');
                $table->string('currency_code', 3);
                $table->string('currency_symbol', 8)->nullable();
                $table->unsignedTinyInteger('currency_decimals')->default(2);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('exchange_rates')) {
            Schema::create('exchange_rates', function (Blueprint $table) {
                $table->id();
                $table->string('base_currency', 3);
                $table->string('quote_currency', 3);
                $table->decimal('rate', 18, 8);
                $table->timestamps();
                $table->unique(['base_currency', 'quote_currency']);
            });
        }

        if (Schema::hasTable('employer_profiles') && !Schema::hasColumn('employer_profiles', 'billing_currency_code')) {
            Schema::table('employer_profiles', function (Blueprint $table) {
                $table->string('billing_currency_code', 3)->nullable()->after('country_code');
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('subscriptions', 'currency_code')) {
                    $table->string('currency_code', 3)->nullable()->after('plan_id');
                }
                if (!Schema::hasColumn('subscriptions', 'amount_cents')) {
                    $table->integer('amount_cents')->nullable()->after('currency_code');
                }
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('invoices', 'currency_code')) {
                    $table->string('currency_code', 3)->nullable()->after('amount_cents');
                }
                if (!Schema::hasColumn('invoices', 'amount_cents')) {
                    $table->integer('amount_cents')->nullable()->after('subscription_id');
                }
            });
        } else {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users');
                $table->foreignId('subscription_id')->nullable()->constrained('subscriptions');
                $table->integer('amount_cents');
                $table->string('currency_code', 3);
                $table->string('status')->default('pending');
                $table->string('provider')->nullable();
                $table->string('provider_ref')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::dropIfExists('invoices');
        }

        if (Schema::hasTable('exchange_rates')) {
            Schema::dropIfExists('exchange_rates');
        }

        if (Schema::hasTable('countries')) {
            Schema::dropIfExists('countries');
        }

        if (Schema::hasTable('employer_profiles') && Schema::hasColumn('employer_profiles', 'billing_currency_code')) {
            Schema::table('employer_profiles', function (Blueprint $table) {
                $table->dropColumn('billing_currency_code');
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (Schema::hasColumn('subscriptions', 'amount_cents')) {
                    $table->dropColumn('amount_cents');
                }
                if (Schema::hasColumn('subscriptions', 'currency_code')) {
                    $table->dropColumn('currency_code');
                }
            });
        }
    }
};
