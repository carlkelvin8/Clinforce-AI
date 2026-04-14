<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs_table', function (Blueprint $table) {
            // Check if columns don't already exist (they're already in the core migration)
            if (!Schema::hasColumn('jobs_table', 'salary_min')) {
                $table->decimal('salary_min', 12, 2)->nullable()->after('salary_currency');
            }
            if (!Schema::hasColumn('jobs_table', 'salary_max')) {
                $table->decimal('salary_max', 12, 2)->nullable()->after('salary_min');
            }
            if (!Schema::hasColumn('jobs_table', 'salary_currency')) {
                $table->string('salary_currency', 10)->nullable()->after('salary_max');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs_table', function (Blueprint $table) {
            $table->dropColumn(['salary_min', 'salary_max', 'salary_currency']);
        });
    }
};
