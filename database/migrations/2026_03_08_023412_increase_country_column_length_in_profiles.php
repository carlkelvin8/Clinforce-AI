<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Increase length for Applicant Profiles
        if (Schema::hasTable('applicant_profiles') && Schema::hasColumn('applicant_profiles', 'country')) {
            Schema::table('applicant_profiles', function (Blueprint $table) {
                $table->string('country', 200)->change();
            });
        }

        // Increase length for Employer Profiles
        if (Schema::hasTable('employer_profiles') && Schema::hasColumn('employer_profiles', 'country')) {
            Schema::table('employer_profiles', function (Blueprint $table) {
                $table->string('country', 200)->change();
            });
        }

        // Increase length for Agency Profiles
        if (Schema::hasTable('agency_profiles') && Schema::hasColumn('agency_profiles', 'country')) {
            Schema::table('agency_profiles', function (Blueprint $table) {
                $table->string('country', 200)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting length changes is usually not necessary or safe if data exceeds old limit
        // but we can define it for completeness if needed.
    }
};
