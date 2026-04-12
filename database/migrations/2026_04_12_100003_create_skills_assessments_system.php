<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Create skills_assessments table
     * 
     * Skills assessment system:
     * - Pre-built assessments for common skills (nursing, CRA, etc.)
     * - Time-based quizzes with scoring
     * - Verified skill badges
     * - Progress tracking
     */
    public function up(): void
    {
        // Assessment Templates (pre-built tests)
        Schema::create('assessment_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('category')->index()->comment('e.g., "Nursing", "Clinical Research", "Soft Skills"');
            $table->string('skill_name')->index()->comment('Skill being assessed');
            
            // Assessment Configuration
            $table->integer('duration_minutes')->default(30)->comment('Time limit');
            $table->integer('passing_score')->default(70)->comment('Minimum % to pass');
            $table->integer('total_questions')->default(0);
            $table->integer('max_attempts')->default(3)->comment('Maximum attempts allowed');
            $table->integer('cooldown_hours')->default(24)->comment('Hours between attempts');
            
            // Difficulty & Metadata
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->json('questions')->comment('Array of question objects');
            $table->boolean('is_active')->default(true);
            $table->integer('completions')->default(0)->comment('Total completions');
            $table->float('average_score', 5, 2)->nullable()->comment('Average score across all attempts');
            
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index(['skill_name', 'difficulty']);
        });

        // User Assessment Attempts
        Schema::create('skills_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('assessment_templates')->onDelete('cascade');
            
            // Attempt Details
            $table->integer('attempt_number')->default(1);
            $table->integer('score')->comment('Score percentage (0-100)');
            $table->integer('correct_answers')->default(0);
            $table->integer('total_questions')->default(0);
            $table->integer('time_taken_seconds')->nullable()->comment('Actual time taken');
            
            // Results
            $table->boolean('passed')->comment('Whether candidate passed');
            $table->json('answers')->nullable()->comment('Candidate answers');
            $table->json('feedback')->nullable()->comment('AI-generated feedback on performance');
            $table->text('weak_areas')->nullable()->comment('Areas needing improvement');
            
            // Verification
            $table->boolean('is_verified')->default(false)->comment('Verified by admin/system');
            $table->string('verification_badge_url')->nullable();
            
            // Timing
            $table->timestamp('started_at');
            $table->timestamp('completed_at');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['user_id', 'template_id', 'attempt_number']);
            $table->index(['user_id', 'passed']);
            $table->index('passed');
        });

        // Verified Skills (skills confirmed through assessment)
        Schema::create('verified_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('skill_name')->index();
            $table->foreignId('assessment_id')->nullable()->constrained('skills_assessments')->onDelete('set null')
                  ->comment('Assessment that verified this skill');
            $table->integer('proficiency_level')->default(0)->comment('1-100 scale');
            $table->string('badge_url')->nullable()->comment('Badge image URL');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable()->comment('Some certifications expire');
            $table->json('metadata')->nullable()->comment('Additional verification details');
            
            $table->timestamps();
            
            $table->unique(['user_id', 'skill_name']);
            $table->index(['user_id', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verified_skills');
        Schema::dropIfExists('skills_assessments');
        Schema::dropIfExists('assessment_templates');
    }
};
