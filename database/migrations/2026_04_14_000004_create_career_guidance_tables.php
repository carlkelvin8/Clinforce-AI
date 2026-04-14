<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Career paths and guidance
        Schema::create('career_paths', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200); // e.g., "RN to NP", "CNA to RN"
            $table->string('category', 100)->index(); // nursing, allied_health, physician
            $table->string('from_role', 100); // Starting role
            $table->string('to_role', 100); // Target role
            $table->text('description')->nullable();
            $table->integer('typical_duration_months')->nullable(); // How long it takes
            $table->decimal('avg_salary_increase', 10, 2)->nullable(); // Expected salary increase
            $table->json('required_steps')->nullable(); // Steps to complete
            $table->json('required_certifications')->nullable(); // Certifications needed
            $table->json('resources')->nullable(); // Learning resources
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // User career goals and progress
        Schema::create('user_career_goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('career_path_id')->nullable();
            $table->string('target_role', 100);
            $table->json('goals')->nullable(); // Specific goals
            $table->json('completed_steps')->nullable(); // Completed steps
            $table->dateTime('target_completion_date')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('career_path_id')->references('id')->on('career_paths')->onDelete('set null');
        });

        // Salary data by role, location, experience
        Schema::create('salary_data', function (Blueprint $table) {
            $table->id();
            $table->string('role_type', 100)->index();
            $table->string('country', 100)->nullable()->index();
            $table->string('state', 100)->nullable()->index();
            $table->string('city', 100)->nullable();
            $table->string('experience_level', 50)->nullable(); // entry, mid, senior, expert
            $table->integer('min_years_experience')->nullable();
            $table->decimal('salary_min', 10, 2);
            $table->decimal('salary_max', 10, 2);
            $table->decimal('salary_median', 10, 2)->nullable();
            $table->decimal('salary_average', 10, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('salary_type', 20)->default('annual'); // annual, hourly
            $table->dateTime('data_date')->nullable(); // When this data was collected
            $table->json('source')->nullable(); // Data source info
            $table->timestamps();
            
            $table->index(['role_type', 'country', 'state']);
        });

        // Skills gap analysis
        Schema::create('user_skills_analysis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('target_role', 100);
            $table->json('current_skills')->nullable(); // Skills user has
            $table->json('required_skills')->nullable(); // Skills needed for target role
            $table->json('missing_skills')->nullable(); // Skills gap
            $table->json('learning_resources')->nullable(); // Recommended resources
            $table->decimal('readiness_score', 5, 2)->nullable(); // 0-100 readiness
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_skills_analysis');
        Schema::dropIfExists('salary_data');
        Schema::dropIfExists('user_career_goals');
        Schema::dropIfExists('career_paths');
    }
};
