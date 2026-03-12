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

        if (Schema::hasColumn('employer_profiles', 'country_code')) {
            DB::table('employer_profiles')
                ->where('country_code', 'PH')
                ->update(['billing_currency_code' => 'PHP']);
        } elseif (Schema::hasColumn('employer_profiles', 'country')) {
            DB::table('employer_profiles')
                ->where('country', 'Philippines')
                ->orWhere('country', 'PH')
                ->update(['billing_currency_code' => 'PHP']);
        }

        // Make column NOT NULL - use database-agnostic approach
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE employer_profiles MODIFY billing_currency_code VARCHAR(10) NOT NULL DEFAULT 'USD'");
        }
        // SQLite doesn't support MODIFY, column is already created with NOT NULL if needed
    }

    public function down(): void
    {
        if (!Schema::hasTable('employer_profiles') || !Schema::hasColumn('employer_profiles', 'billing_currency_code')) {
            return;
        }

        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->dropColumn('billing_currency_code');
        });
    }
};
