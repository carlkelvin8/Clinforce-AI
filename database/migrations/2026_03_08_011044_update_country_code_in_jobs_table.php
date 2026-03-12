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
        // Check for 'jobs_table' first as per create_core_tables
        $tableName = null;
        if (Schema::hasTable('jobs_table')) {
            $tableName = 'jobs_table';
        } elseif (Schema::hasTable('jobs')) {
            // Be careful not to modify Laravel's queue table unless it's actually the app table
            // We can check if it has 'title' column which queue table doesn't have
            if (Schema::hasColumn('jobs', 'title')) {
                $tableName = 'jobs';
            }
        }

        if ($tableName && Schema::hasColumn($tableName, 'country_code')) {
            Schema::table($tableName, function (Blueprint $table) {
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
        $tableName = null;
        if (Schema::hasTable('jobs_table')) {
            $tableName = 'jobs_table';
        } elseif (Schema::hasTable('jobs') && Schema::hasColumn('jobs', 'title')) {
            $tableName = 'jobs';
        }

        if ($tableName && Schema::hasColumn($tableName, 'country')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('country', 'country_code');
                $table->string('country_code', 2)->change();
            });
        }
    }
};
