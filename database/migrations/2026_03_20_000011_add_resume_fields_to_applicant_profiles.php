<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->json('skills')->nullable()->after('avatar');
            $table->json('work_experience')->nullable()->after('skills');
            $table->json('education')->nullable()->after('work_experience');
        });
    }

    public function down(): void
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->dropColumn(['skills', 'work_experience', 'education']);
        });
    }
};
