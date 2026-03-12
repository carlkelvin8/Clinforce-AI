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
        $tableName = null;
        if (Schema::hasTable('jobs_table')) {
            $tableName = 'jobs_table';
        } elseif (Schema::hasTable('jobs')) {
            // Ensure we are modifying the correct table
            if (Schema::hasColumn('jobs', 'title')) {
                $tableName = 'jobs';
            }
        }

        if ($tableName && Schema::hasColumn($tableName, 'country')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('country', 200)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting length is usually not necessary
    }
};
