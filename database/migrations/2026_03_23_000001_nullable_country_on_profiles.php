<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['applicant_profiles', 'employer_profiles', 'agency_profiles'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'country')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->string('country', 200)->nullable()->default(null)->change();
                });
            }
        }
    }

    public function down(): void
    {
        // intentionally left blank — reverting NOT NULL on profiles is destructive
    }
};
