<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Integrated Learning & Development Platform Migration
     * 
     * Features:
     * - Skill Gap Analysis: Identify and address skill gaps
     * - Learning Recommendations: Suggest courses based on career goals
     * - Certification Tracking: Track professional certifications
     * - Mentorship Matching: Connect mentors with mentees
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // SKILLS & COMPETENCIES
        // ═══════════════════════════════════════════════════════════

        // Skills Catalog (master list of healthcare skills)
        if (!Schema::hasTable('skills_catalog')) {
            Schema::create('skills_catalog', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category')->comment('clinical, technical, soft, leadership, compliance');
                $table->string('specialty')->nullable()->comment('nursing, physician, therapy, etc.');
                $table->text('description');
                $table->enum('proficiency_levels', ['beginner', 'intermediate', 'advanced', 'expert'])->default('beginner');
                $table->json('required_for_roles')->nullable()->comment('Job roles that require this skill');
                $table->integer('importance_score')->default(50)->comment('1-100 importance rating');
                $table->boolean('requires_certification')->default(false);
                $table->string('certification_body')->nullable();
                $table->integer('renewal_months')->nullable()->comment('Months until renewal required');
                $table->json('learning_resources')->nullable()->comment('Links to courses, materials');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['category', 'specialty', 'is_active'], 'skills_cat_spec_active_idx');
                $table->index('importance_score', 'skills_importance_idx');
            });
        }

        // User Skills (skills possessed by users)
        if (!Schema::hasTable('user_skills')) {
            Schema::create('user_skills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('skill_id')->constrained('skills_catalog')->onDelete('cascade');
                $table->enum('proficiency_level', ['beginner', 'intermediate', 'advanced', 'expert']);
                $table->enum('verification_status', ['self_reported', 'employer_verified', 'certified', 'assessed'])->default('self_reported');
                $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('acquired_date')->nullable();
                $table->date('last_used_date')->nullable();
                $table->integer('years_experience')->nullable();
                $table->text('notes')->nullable();
                $table->json('evidence')->nullable()->comment('Certificates, assessments, etc.');
                $table->boolean('is_featured')->default(false)->comment('Show on profile prominently');
                $table->timestamps();

                $table->unique(['user_id', 'skill_id'], 'user_skills_unique');
                $table->index(['user_id', 'proficiency_level'], 'user_skills_user_prof_idx');
                $table->index('verification_status', 'user_skills_verification_idx');
            });
        }

        // Skill Gap Analysis (identified gaps for users)
        if (!Schema::hasTable('skill_gap_analysis')) {
            Schema::create('skill_gap_analysis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('target_role_id')->nullable()->constrained('jobs_table')->nullOnDelete();
                $table->string('target_role_title')->nullable();
                $table->foreignId('skill_id')->constrained('skills_catalog')->onDelete('cascade');
                $table->enum('current_level', ['none', 'beginner', 'intermediate', 'advanced', 'expert'])->default('none');
                $table->enum('required_level', ['beginner', 'intermediate', 'advanced', 'expert']);
                $table->integer('gap_score')->comment('1-100, higher = bigger gap');
                $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
                $table->date('target_completion_date')->nullable();
                $table->enum('status', ['identified', 'in_progress', 'completed', 'deferred'])->default('identified');
                $table->json('recommended_actions')->nullable()->comment('Courses, mentoring, etc.');
                $table->text('notes')->nullable();
                $table->timestamp('analyzed_at')->useCurrent();
                $table->timestamps();

                $table->index(['user_id', 'status', 'priority'], 'skill_gaps_user_status_priority_idx');
                $table->index(['target_role_id', 'gap_score'], 'skill_gaps_role_score_idx');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // LEARNING RESOURCES & COURSES
        // ═══════════════════════════════════════════════════════════

        // Learning Providers (external course providers)
        if (!Schema::hasTable('learning_providers')) {
            Schema::create('learning_providers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->comment('university, online_platform, certification_body, internal');
                $table->text('description')->nullable();
                $table->string('website_url')->nullable();
                $table->string('api_endpoint')->nullable();
                $table->json('api_credentials')->nullable();
                $table->string('logo_url')->nullable();
                $table->decimal('average_rating', 3, 2)->nullable();
                $table->boolean('is_accredited')->default(false);
                $table->json('accreditation_bodies')->nullable();
                $table->boolean('offers_certificates')->default(false);
                $table->boolean('offers_ceu')->default(false)->comment('Continuing Education Units');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['type', 'is_active'], 'providers_type_active_idx');
                $table->index('average_rating', 'providers_rating_idx');
            });
        }

        // Learning Courses
        if (!Schema::hasTable('learning_courses')) {
            Schema::create('learning_courses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('provider_id')->constrained('learning_providers')->onDelete('cascade');
                $table->string('external_id')->nullable()->comment('Provider\'s course ID');
                $table->string('title');
                $table->text('description');
                $table->string('category')->comment('clinical, technical, soft_skills, leadership, compliance');
                $table->string('specialty')->nullable();
                $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced', 'expert']);
                $table->integer('duration_hours')->nullable();
                $table->integer('duration_weeks')->nullable();
                $table->decimal('price', 8, 2)->nullable();
                $table->string('currency', 3)->default('USD');
                $table->string('format')->comment('online, in_person, hybrid, self_paced');
                $table->string('language', 5)->default('en');
                $table->json('prerequisites')->nullable();
                $table->json('learning_outcomes')->nullable();
                $table->json('target_skills')->nullable()->comment('Skills this course develops');
                $table->boolean('offers_certificate')->default(false);
                $table->boolean('offers_ceu')->default(false);
                $table->decimal('ceu_hours', 4, 1)->nullable();
                $table->string('accreditation')->nullable();
                $table->decimal('rating', 3, 2)->nullable();
                $table->integer('review_count')->default(0);
                $table->string('enrollment_url')->nullable();
                $table->date('next_start_date')->nullable();
                $table->json('schedule')->nullable()->comment('Class times, dates');
                $table->string('instructor_name')->nullable();
                $table->text('instructor_bio')->nullable();
                $table->string('thumbnail_url')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->index(['category', 'specialty', 'is_active'], 'courses_cat_spec_active_idx');
                $table->index(['difficulty_level', 'rating'], 'courses_level_rating_idx');
                $table->index(['provider_id', 'external_id'], 'courses_provider_external_idx');
            });
        }

        // User Course Enrollments
        if (!Schema::hasTable('course_enrollments')) {
            Schema::create('course_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('course_id')->constrained('learning_courses')->onDelete('cascade');
                $table->enum('status', ['enrolled', 'in_progress', 'completed', 'dropped', 'failed'])->default('enrolled');
                $table->date('enrolled_date');
                $table->date('start_date')->nullable();
                $table->date('completion_date')->nullable();
                $table->date('certificate_issued_date')->nullable();
                $table->string('certificate_url')->nullable();
                $table->decimal('progress_percentage', 5, 2)->default(0);
                $table->integer('hours_completed')->default(0);
                $table->decimal('final_score', 5, 2)->nullable();
                $table->decimal('passing_score', 5, 2)->nullable();
                $table->integer('attempts')->default(1);
                $table->decimal('user_rating', 3, 2)->nullable();
                $table->text('user_review')->nullable();
                $table->json('completion_evidence')->nullable();
                $table->decimal('ceu_earned', 4, 1)->nullable();
                $table->enum('payment_status', ['free', 'paid', 'employer_sponsored', 'pending'])->default('free');
                $table->foreignId('sponsored_by_employer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status'], 'enrollments_user_status_idx');
                $table->index(['course_id', 'status'], 'enrollments_course_status_idx');
                $table->index('completion_date', 'enrollments_completion_idx');
            });
        }

        // Learning Recommendations
        if (!Schema::hasTable('learning_recommendations')) {
            Schema::create('learning_recommendations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('course_id')->nullable()->constrained('learning_courses')->nullOnDelete();
                $table->foreignId('skill_id')->nullable()->constrained('skills_catalog')->nullOnDelete();
                $table->string('recommendation_type')->comment('skill_gap, career_goal, trending, peer_recommended');
                $table->string('reason')->comment('Why this was recommended');
                $table->integer('relevance_score')->comment('1-100 relevance rating');
                $table->integer('priority_score')->comment('1-100 priority rating');
                $table->json('personalization_factors')->nullable()->comment('Factors used in recommendation');
                $table->enum('status', ['pending', 'viewed', 'enrolled', 'dismissed', 'completed'])->default('pending');
                $table->timestamp('recommended_at')->useCurrent();
                $table->timestamp('viewed_at')->nullable();
                $table->timestamp('acted_on_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status', 'priority_score'], 'recommendations_user_status_priority_idx');
                $table->index(['recommendation_type', 'relevance_score'], 'recommendations_type_relevance_idx');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // CERTIFICATIONS & CREDENTIALS
        // ═══════════════════════════════════════════════════════════

        // Certification Types
        if (!Schema::hasTable('certification_types')) {
            Schema::create('certification_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('abbreviation')->nullable();
                $table->string('category')->comment('license, certification, specialty, continuing_education');
                $table->string('specialty')->nullable();
                $table->text('description');
                $table->string('issuing_organization');
                $table->string('organization_website')->nullable();
                $table->json('requirements')->nullable()->comment('Education, experience, exam requirements');
                $table->integer('validity_months')->nullable()->comment('How long certification is valid');
                $table->boolean('requires_renewal')->default(false);
                $table->integer('renewal_months')->nullable();
                $table->json('renewal_requirements')->nullable();
                $table->decimal('renewal_cost', 8, 2)->nullable();
                $table->string('renewal_currency', 3)->default('USD');
                $table->boolean('requires_ceu')->default(false);
                $table->integer('required_ceu_hours')->nullable();
                $table->string('verification_url')->nullable()->comment('URL to verify certification');
                $table->boolean('is_mandatory')->default(false)->comment('Required for certain roles');
                $table->json('mandatory_for_roles')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['category', 'specialty', 'is_active'], 'cert_types_cat_spec_active_idx');
                $table->index('issuing_organization', 'cert_types_org_idx');
            });
        }

        // User Certifications
        if (!Schema::hasTable('user_certifications')) {
            Schema::create('user_certifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('certification_type_id')->constrained('certification_types')->onDelete('cascade');
                $table->string('certification_number')->nullable();
                $table->date('issued_date');
                $table->date('expiration_date')->nullable();
                $table->enum('status', ['active', 'expired', 'suspended', 'revoked', 'pending_renewal'])->default('active');
                $table->string('issuing_authority')->nullable();
                $table->string('verification_url')->nullable();
                $table->string('certificate_file_path')->nullable();
                $table->json('verification_data')->nullable()->comment('API verification response');
                $table->timestamp('last_verified_at')->nullable();
                $table->boolean('auto_verify')->default(true);
                $table->date('renewal_reminder_sent_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status'], 'user_certs_user_status_idx');
                $table->index(['certification_type_id', 'status'], 'user_certs_type_status_idx');
                $table->index('expiration_date', 'user_certs_expiration_idx');
            });
        }

        // Certification Renewal Tracking
        if (!Schema::hasTable('certification_renewals')) {
            Schema::create('certification_renewals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_certification_id')->constrained('user_certifications')->onDelete('cascade');
                $table->date('renewal_due_date');
                $table->enum('status', ['upcoming', 'overdue', 'in_progress', 'completed', 'failed'])->default('upcoming');
                $table->json('requirements_checklist')->nullable();
                $table->json('completed_requirements')->nullable();
                $table->decimal('completion_percentage', 5, 2)->default(0);
                $table->decimal('ceu_required', 4, 1)->nullable();
                $table->decimal('ceu_completed', 4, 1)->default(0);
                $table->date('renewal_submitted_date')->nullable();
                $table->date('renewal_approved_date')->nullable();
                $table->string('renewal_confirmation_number')->nullable();
                $table->decimal('renewal_fee_paid', 8, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['renewal_due_date', 'status'], 'renewals_due_status_idx');
                $table->index('user_certification_id', 'renewals_cert_idx');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // MENTORSHIP PROGRAM
        // ═══════════════════════════════════════════════════════════

        // Mentor Profiles
        if (!Schema::hasTable('mentor_profiles')) {
            Schema::create('mentor_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->boolean('is_available')->default(true);
                $table->integer('years_experience');
                $table->json('specialties')->comment('Areas of expertise');
                $table->json('mentoring_areas')->comment('What they can mentor in');
                $table->text('bio');
                $table->text('mentoring_philosophy')->nullable();
                $table->enum('mentoring_style', ['hands_on', 'advisory', 'coaching', 'mixed'])->default('mixed');
                $table->integer('max_mentees')->default(3);
                $table->integer('current_mentees')->default(0);
                $table->json('preferred_communication')->comment('email, video, in_person, etc.');
                $table->json('availability_schedule')->nullable();
                $table->integer('session_duration_minutes')->default(60);
                $table->enum('commitment_level', ['casual', 'regular', 'intensive'])->default('regular');
                $table->decimal('rating', 3, 2)->nullable();
                $table->integer('total_mentees')->default(0);
                $table->integer('successful_relationships')->default(0);
                $table->json('achievements')->nullable();
                $table->boolean('background_checked')->default(false);
                $table->date('background_check_date')->nullable();
                $table->timestamps();

                $table->index(['is_available', 'rating'], 'mentors_available_rating_idx');
                $table->index('years_experience', 'mentors_experience_idx');
            });
        }

        // Mentee Profiles
        if (!Schema::hasTable('mentee_profiles')) {
            Schema::create('mentee_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->boolean('seeking_mentor')->default(true);
                $table->json('career_goals');
                $table->json('areas_for_development');
                $table->json('preferred_mentor_qualities');
                $table->enum('experience_level', ['student', 'new_grad', 'early_career', 'mid_career', 'career_change'])->default('new_grad');
                $table->text('background_summary');
                $table->text('what_seeking')->comment('What they want from mentorship');
                $table->json('preferred_communication');
                $table->json('availability_schedule')->nullable();
                $table->enum('commitment_level', ['casual', 'regular', 'intensive'])->default('regular');
                $table->boolean('has_had_mentor_before')->default(false);
                $table->text('previous_mentoring_experience')->nullable();
                $table->timestamps();

                $table->index(['seeking_mentor', 'experience_level'], 'mentees_seeking_level_idx');
            });
        }

        // Mentorship Relationships
        if (!Schema::hasTable('mentorship_relationships')) {
            Schema::create('mentorship_relationships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('mentee_id')->constrained('users')->onDelete('cascade');
                $table->enum('status', ['pending', 'active', 'paused', 'completed', 'terminated'])->default('pending');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->date('expected_end_date')->nullable();
                $table->json('goals')->nullable()->comment('Relationship goals');
                $table->json('success_metrics')->nullable();
                $table->text('mentor_notes')->nullable();
                $table->text('mentee_notes')->nullable();
                $table->integer('total_sessions')->default(0);
                $table->integer('total_hours')->default(0);
                $table->decimal('mentor_rating', 3, 2)->nullable();
                $table->decimal('mentee_rating', 3, 2)->nullable();
                $table->text('mentor_feedback')->nullable();
                $table->text('mentee_feedback')->nullable();
                $table->enum('termination_reason', ['goals_achieved', 'mutual_agreement', 'mentor_unavailable', 'mentee_unavailable', 'mismatch', 'other'])->nullable();
                $table->text('termination_notes')->nullable();
                $table->timestamps();

                $table->index(['mentor_id', 'status'], 'relationships_mentor_status_idx');
                $table->index(['mentee_id', 'status'], 'relationships_mentee_status_idx');
                $table->index(['status', 'start_date'], 'relationships_status_start_idx');
            });
        }

        // Mentorship Sessions
        if (!Schema::hasTable('mentorship_sessions')) {
            Schema::create('mentorship_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('relationship_id')->constrained('mentorship_relationships')->onDelete('cascade');
                $table->datetime('scheduled_at');
                $table->integer('duration_minutes')->default(60);
                $table->enum('type', ['video', 'phone', 'in_person', 'email', 'chat'])->default('video');
                $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
                $table->text('agenda')->nullable();
                $table->text('mentor_notes')->nullable();
                $table->text('mentee_notes')->nullable();
                $table->json('topics_discussed')->nullable();
                $table->json('action_items')->nullable();
                $table->json('resources_shared')->nullable();
                $table->decimal('mentor_rating', 3, 2)->nullable();
                $table->decimal('mentee_rating', 3, 2)->nullable();
                $table->text('mentor_feedback')->nullable();
                $table->text('mentee_feedback')->nullable();
                $table->string('meeting_link')->nullable();
                $table->datetime('actual_start_time')->nullable();
                $table->datetime('actual_end_time')->nullable();
                $table->timestamps();

                $table->index(['relationship_id', 'scheduled_at'], 'sessions_relationship_scheduled_idx');
                $table->index(['status', 'scheduled_at'], 'sessions_status_scheduled_idx');
            });
        }

        // Mentorship Matching Algorithm Data
        if (!Schema::hasTable('mentorship_matches')) {
            Schema::create('mentorship_matches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('mentee_id')->constrained('users')->onDelete('cascade');
                $table->decimal('compatibility_score', 5, 2)->comment('0-100 compatibility rating');
                $table->json('matching_factors')->comment('Factors that contributed to match');
                $table->json('compatibility_breakdown')->comment('Detailed scoring breakdown');
                $table->enum('status', ['suggested', 'viewed', 'contacted', 'accepted', 'declined', 'expired'])->default('suggested');
                $table->timestamp('suggested_at')->useCurrent();
                $table->timestamp('viewed_at')->nullable();
                $table->timestamp('contacted_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->text('decline_reason')->nullable();
                $table->timestamps();

                $table->index(['mentor_id', 'status', 'compatibility_score'], 'matches_mentor_status_score_idx');
                $table->index(['mentee_id', 'status', 'compatibility_score'], 'matches_mentee_status_score_idx');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // CAREER DEVELOPMENT & PLANNING
        // ═══════════════════════════════════════════════════════════

        // Career Paths (predefined career progression paths)
        if (!Schema::hasTable('career_paths')) {
            Schema::create('career_paths', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('specialty')->comment('nursing, physician, therapy, admin, etc.');
                $table->text('description');
                $table->json('progression_levels')->comment('Ordered list of career levels');
                $table->json('typical_timeline')->comment('Expected years at each level');
                $table->json('required_skills_by_level')->comment('Skills needed for each level');
                $table->json('required_certifications_by_level')->nullable();
                $table->json('salary_ranges_by_level')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['specialty', 'is_active'], 'career_paths_specialty_active_idx');
            });
        }

        // User Career Plans
        if (!Schema::hasTable('user_career_plans')) {
            Schema::create('user_career_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('career_path_id')->nullable()->constrained('career_paths')->nullOnDelete();
                $table->string('current_level');
                $table->string('target_level');
                $table->date('target_achievement_date')->nullable();
                $table->json('custom_goals')->nullable()->comment('User-defined goals');
                $table->json('milestones')->nullable()->comment('Planned milestones');
                $table->json('completed_milestones')->nullable();
                $table->decimal('progress_percentage', 5, 2)->default(0);
                $table->enum('status', ['active', 'paused', 'completed', 'abandoned'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamp('last_reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status'], 'career_plans_user_status_idx');
                $table->index('target_achievement_date', 'career_plans_target_date_idx');
            });
        }

        // Learning Analytics (track learning behavior and outcomes)
        if (!Schema::hasTable('learning_analytics')) {
            Schema::create('learning_analytics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->date('analytics_date');
                $table->integer('courses_enrolled')->default(0);
                $table->integer('courses_completed')->default(0);
                $table->integer('courses_dropped')->default(0);
                $table->integer('hours_learned')->default(0);
                $table->integer('skills_acquired')->default(0);
                $table->integer('certifications_earned')->default(0);
                $table->decimal('average_course_rating', 3, 2)->nullable();
                $table->integer('mentorship_sessions')->default(0);
                $table->decimal('learning_streak_days', 5, 1)->default(0);
                $table->json('learning_preferences')->nullable();
                $table->json('performance_metrics')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'analytics_date'], 'learning_analytics_user_date_unique');
                $table->index('analytics_date', 'learning_analytics_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_analytics');
        Schema::dropIfExists('user_career_plans');
        Schema::dropIfExists('career_paths');
        Schema::dropIfExists('mentorship_matches');
        Schema::dropIfExists('mentorship_sessions');
        Schema::dropIfExists('mentorship_relationships');
        Schema::dropIfExists('mentee_profiles');
        Schema::dropIfExists('mentor_profiles');
        Schema::dropIfExists('certification_renewals');
        Schema::dropIfExists('user_certifications');
        Schema::dropIfExists('certification_types');
        Schema::dropIfExists('learning_recommendations');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('learning_courses');
        Schema::dropIfExists('learning_providers');
        Schema::dropIfExists('skill_gap_analysis');
        Schema::dropIfExists('user_skills');
        Schema::dropIfExists('skills_catalog');
    }
};