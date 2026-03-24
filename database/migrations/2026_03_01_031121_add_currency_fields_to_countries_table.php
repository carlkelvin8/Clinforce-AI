<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            if (!Schema::hasColumn('countries', 'currency_code')) {
                $table->string('currency_code', 3)->nullable()->after('country_name');
            }
            if (!Schema::hasColumn('countries', 'currency_symbol')) {
                $table->string('currency_symbol', 10)->nullable()->after('currency_code');
            }
            if (!Schema::hasColumn('countries', 'currency_decimals')) {
                $table->integer('currency_decimals')->default(2)->after('currency_symbol');
            }
        });

        // Update Philippines
        DB::table('countries')->where('country_code', 'PH')->update([
            'currency_code' => 'PHP',
            'currency_symbol' => '₱',
            'currency_decimals' => 2,
        ]);
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'currency_symbol', 'currency_decimals']);
        });
    }
};
