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
        if (Schema::hasTable('applicant_profiles')) {
            Schema::table('applicant_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('applicant_profiles', 'avatar')) {
                    $table->string('avatar')->nullable()->after('last_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('applicant_profiles')) {
            Schema::table('applicant_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('applicant_profiles', 'avatar')) {
                    $table->dropColumn('avatar');
                }
            });
        }
    }
};
