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
        // Update Applicant Profiles
        if (Schema::hasTable('applicant_profiles') && Schema::hasColumn('applicant_profiles', 'country_code')) {
            Schema::table('applicant_profiles', function (Blueprint $table) {
                $table->string('country_code', 200)->change();
                $table->renameColumn('country_code', 'country');
            });
        }

        // Update Employer Profiles
        if (Schema::hasTable('employer_profiles') && Schema::hasColumn('employer_profiles', 'country_code')) {
            Schema::table('employer_profiles', function (Blueprint $table) {
                $table->string('country_code', 200)->change();
                $table->renameColumn('country_code', 'country');
            });
        }

        // Update Agency Profiles
        if (Schema::hasTable('agency_profiles') && Schema::hasColumn('agency_profiles', 'country_code')) {
            Schema::table('agency_profiles', function (Blueprint $table) {
                $table->string('country_code', 200)->change();
                $table->renameColumn('country_code', 'country');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert Applicant Profiles
        if (Schema::hasTable('applicant_profiles') && Schema::hasColumn('applicant_profiles', 'country')) {
            Schema::table('applicant_profiles', function (Blueprint $table) {
                $table->renameColumn('country', 'country_code');
                $table->string('country_code', 2)->change();
            });
        }

        // Revert Employer Profiles
        if (Schema::hasTable('employer_profiles') && Schema::hasColumn('employer_profiles', 'country')) {
            Schema::table('employer_profiles', function (Blueprint $table) {
                $table->renameColumn('country', 'country_code');
                $table->string('country_code', 2)->change();
            });
        }

        // Revert Agency Profiles
        if (Schema::hasTable('agency_profiles') && Schema::hasColumn('agency_profiles', 'country')) {
            Schema::table('agency_profiles', function (Blueprint $table) {
                $table->renameColumn('country', 'country_code');
                $table->string('country_code', 2)->change();
            });
        }
    }
};
