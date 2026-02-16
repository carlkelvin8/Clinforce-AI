<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employer_profiles')) {
            return;
        }

        Schema::table('employer_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('employer_profiles', 'billing_currency_code')) {
                $table->string('billing_currency_code', 10)->default('USD')->after('country_code');
            }
        });

        DB::table('employer_profiles')
            ->whereNull('billing_currency_code')
            ->orWhere('billing_currency_code', '')
            ->update(['billing_currency_code' => 'USD']);

        DB::table('employer_profiles')
            ->where('country_code', 'PH')
            ->update(['billing_currency_code' => 'PHP']);

        DB::statement("ALTER TABLE employer_profiles MODIFY billing_currency_code VARCHAR(10) NOT NULL DEFAULT 'USD'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('employer_profiles') || !Schema::hasColumn('employer_profiles', 'billing_currency_code')) {
            return;
        }

        DB::statement("ALTER TABLE employer_profiles MODIFY billing_currency_code VARCHAR(3) NULL");
    }
};

