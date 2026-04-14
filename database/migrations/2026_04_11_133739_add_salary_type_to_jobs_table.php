<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs_table', function (Blueprint $table) {
            $table->string('salary_type', 50)->default('annually')->after('salary_currency');
        });
    }

    public function down(): void
    {
        Schema::table('jobs_table', function (Blueprint $table) {
            $table->dropColumn('salary_type');
        });
    }
};
