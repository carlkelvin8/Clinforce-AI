<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add profile enhancement fields
     * 
     * Adds:
     * - Video introduction URL (60-second elevator pitch)
     * - Availability date (when available to start)
     * - AI-generated resume JSON structure
     * - Profile visibility settings
     * - Professional certifications array
     * - Languages spoken
     * - Preferred work arrangements
     */
    public function up(): void
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            // Video Introduction
            $table->string('video_intro_url')->nullable()->after('portfolio_links')
                  ->comment('URL to 60-second elevator pitch video');
            $table->string('video_intro_thumbnail')->nullable()->after('video_intro_url')
                  ->comment('Thumbnail for video preview');
            $table->integer('video_intro_duration')->nullable()->after('video_intro_thumbnail')
                  ->comment('Duration in seconds (max 60)');
            
            // Availability Calendar
            $table->date('available_from')->nullable()->after('open_to_work')
                  ->comment('Date when candidate is available to start');
            $table->string('notice_period')->nullable()->after('available_from')
                  ->comment('Notice period (e.g., "immediate", "2 weeks", "1 month")');
            $table->boolean('available_for_contract')->default(true)->after('notice_period');
            $table->boolean('available_for_fulltime')->default(true)->after('available_for_contract');
            $table->boolean('available_for_parttime')->default(false)->after('available_for_fulltime');
            $table->boolean('available_for_freelance')->default(false)->after('available_for_parttime');
            
            // AI-Generated Resume
            $table->json('ai_resume')->nullable()->after('education')
                  ->comment('AI-generated structured resume data');
            $table->boolean('ai_resume_generated')->default(false)->after('ai_resume');
            $table->timestamp('ai_resume_generated_at')->nullable()->after('ai_resume_generated');
            
            // Professional Certifications (separate from education)
            $table->json('certifications')->nullable()->after('skills')
                  ->comment('Professional certifications and licenses');
            
            // Languages
            $table->json('languages')->nullable()->after('certifications')
                  ->comment('Languages spoken with proficiency levels');
            
            // Work Preferences
            $table->json('work_preferences')->nullable()->after('languages')
                  ->comment('Remote, hybrid, on-site preferences');
            
            // Profile Enhancement
            $table->integer('profile_completeness')->default(0)->after('work_preferences')
                  ->comment('Profile completeness percentage (0-100)');
            $table->json('profile_badges')->nullable()->after('profile_completeness')
                  ->comment('Achievement badges (e.g., "verified_skills", "top_responder")');
            
            // Indexes for performance
            $table->index('available_from');
            $table->index('open_to_work');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->dropIndex(['available_from']);
            $table->dropIndex(['open_to_work']);
            
            $table->dropColumn([
                'video_intro_url',
                'video_intro_thumbnail',
                'video_intro_duration',
                'available_from',
                'notice_period',
                'available_for_contract',
                'available_for_fulltime',
                'available_for_parttime',
                'available_for_freelance',
                'ai_resume',
                'ai_resume_generated',
                'ai_resume_generated_at',
                'certifications',
                'languages',
                'work_preferences',
                'profile_completeness',
                'profile_badges',
            ]);
        });
    }
};
