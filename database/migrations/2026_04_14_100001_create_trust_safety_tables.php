<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trust & Safety Features Migration
     * 
     * Covers:
     * - Identity Verification (ID documents, video selfies, fraud detection)
     * - Employer Verification & Trust Score
     * - Background Check enhancements
     * - Report & Moderation System
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // IDENTITY VERIFICATION SYSTEM
        // ═══════════════════════════════════════════════════════════

        // Identity Verifications - stores ID document verification results
        if (!Schema::hasTable('identity_verifications')) {
            Schema::create('identity_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('document_type')->comment('passport, drivers_license, national_id');
                $table->string('document_number')->nullable();
                $table->string('document_front_url');
                $table->string('document_back_url')->nullable();
                $table->string('video_selfie_url')->nullable()->comment('Video selfie for facial matching');
                $table->string('extracted_name')->nullable()->comment('Name extracted from ID via AI');
                $table->string('extracted_dob')->nullable()->comment('Date of birth from ID');
                $table->string('extracted_expiry')->nullable()->comment('ID expiry date');
                $table->enum('verification_status', ['pending', 'processing', 'verified', 'rejected', 'expired'])->default('pending');
                $table->json('ai_verification_data')->nullable()->comment('AI analysis results, confidence scores');
                $table->float('confidence_score')->nullable()->comment('Overall confidence 0-1');
                $table->text('rejection_reason')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('expires_at')->nullable()->comment('When verification expires');
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'verification_status']);
                $table->index('verification_status');
                $table->index('document_number');
            });
        }

        // Fraud Detection Logs
        if (!Schema::hasTable('fraud_detection_logs')) {
            Schema::create('fraud_detection_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('fraud_type')->comment('duplicate_account, fake_credentials, stolen_identity, suspicious_activity');
                $table->string('severity')->default('medium')->comment('low, medium, high, critical');
                $table->text('description');
                $table->json('evidence_data')->nullable()->comment('Evidence supporting fraud claim');
                $table->string('related_entity_type')->nullable()->comment('User, Job, Document, etc.');
                $table->unsignedBigInteger('related_entity_id')->nullable();
                $table->enum('status', ['pending', 'investigating', 'confirmed', 'false_positive', 'resolved'])->default('pending');
                $table->foreignId('investigated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('investigation_notes')->nullable();
                $table->timestamp('investigated_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['fraud_type', 'status']);
                $table->index('severity');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // EMPLOYER VERIFICATION & TRUST SCORE
        // ═══════════════════════════════════════════════════════════

        // Employer Business License Documents
        if (!Schema::hasTable('employer_business_licenses')) {
            Schema::create('employer_business_licenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->string('license_type')->comment('business_registration, tax_certificate, industry_license');
                $table->string('license_number');
                $table->string('document_url');
                $table->string('issuing_authority')->nullable();
                $table->date('issued_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->enum('verification_status', ['pending', 'verified', 'expired', 'rejected'])->default('pending');
                $table->json('verification_data')->nullable()->comment('AI/manual verification results');
                $table->text('rejection_reason')->nullable();
                $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index(['employer_user_id', 'verification_status'], 'emp_licenses_user_status_idx');
                $table->index('license_number');
            });
        }

        // Employer Trust Scores
        if (!Schema::hasTable('employer_trust_scores')) {
            Schema::create('employer_trust_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->decimal('overall_score', 5, 2)->default(0)->comment('0-100 trust score');
                $table->decimal('identity_score', 5, 2)->default(0)->comment('Identity verification component');
                $table->decimal('business_score', 5, 2)->default(0)->comment('Business verification component');
                $table->decimal('rating_score', 5, 2)->default(0)->comment('Candidate ratings component');
                $table->decimal('activity_score', 5, 2)->default(0)->comment('Platform activity component');
                $table->integer('total_reviews')->default(0);
                $table->decimal('average_rating', 3, 2)->default(0)->comment('Average candidate rating 1-5');
                $table->json('score_breakdown')->nullable()->comment('Detailed scoring breakdown');
                $table->json('badges')->nullable()->comment('Earned badges: verified_employer, top_rated, etc.');
                $table->json('red_flags')->nullable()->comment('Active red flags');
                $table->timestamp('last_calculated_at')->nullable();
                $table->timestamps();

                $table->unique('employer_user_id');
                $table->index('overall_score');
                $table->index('average_rating');
            });
        }

        // Employer Interview Reviews (candidates rate interview experience)
        if (!Schema::hasTable('employer_interview_reviews')) {
            Schema::create('employer_interview_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('applicant_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('job_id')->nullable()->constrained('jobs_table')->nullOnDelete();
                $table->foreignId('interview_id')->nullable()->constrained('interviews')->nullOnDelete();
                $table->decimal('overall_rating', 3, 2)->comment('1-5 rating');
                $table->decimal('professionalism_rating', 3, 2)->nullable()->comment('1-5');
                $table->decimal('communication_rating', 3, 2)->nullable()->comment('1-5');
                $table->decimal('transparency_rating', 3, 2)->nullable()->comment('1-5');
                $table->text('comments')->nullable();
                $table->json('tags')->nullable()->comment('Positive/negative tags: organized, respectful, ghosted, etc.');
                $table->boolean('would_recommend')->default(true);
                $table->boolean('is_anonymous')->default(false);
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('Moderation status');
                $table->ipAddress('ip_address')->nullable();
                $table->timestamps();

                $table->index(['employer_user_id', 'status']);
                $table->index(['applicant_user_id', 'employer_user_id'], 'emp_reviews_users_idx');
                $table->unique(['applicant_user_id', 'interview_id'], 'unique_applicant_interview_review');
            });
        }

        // Employer Red Flags
        if (!Schema::hasTable('employer_red_flags')) {
            Schema::create('employer_red_flags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->string('flag_type')->comment('fake_job, scam, harassment, discrimination, no_show, payment_issue');
                $table->text('description');
                $table->json('evidence')->nullable();
                $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('investigated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
                $table->enum('status', ['reported', 'under_review', 'confirmed', 'dismissed'])->default('reported');
                $table->text('resolution_notes')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['employer_user_id', 'status']);
                $table->index(['flag_type', 'status']);
                $table->index('severity');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // BACKGROUND CHECK ENHANCEMENTS
        // ═══════════════════════════════════════════════════════════

        // Background Check Providers Configuration
        if (!Schema::hasTable('background_check_providers')) {
            Schema::create('background_check_providers', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique()->comment('checkr, goodhire, sterling, etc.');
                $table->string('display_name');
                $table->text('description')->nullable();
                $table->json('config')->nullable()->comment('API configuration (encrypted)');
                $table->boolean('is_active')->default(false);
                $table->json('supported_types')->nullable()->comment('Array of supported check types');
                $table->json('pricing')->nullable()->comment('Pricing per check type');
                $table->string('webhook_url')->nullable();
                $table->timestamps();

                $table->index('is_active');
            });
        }

        // Background Check Access Logs (audit trail)
        if (!Schema::hasTable('background_check_access_logs')) {
            Schema::create('background_check_access_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('background_check_id')->constrained('background_checks')->onDelete('cascade');
                $table->foreignId('accessed_by_user_id')->constrained('users');
                $table->string('action')->comment('viewed, downloaded, shared');
                $table->ipAddress('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();

                $table->index('background_check_id');
                $table->index(['accessed_by_user_id', 'created_at'], 'bg_access_user_date_idx');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // REPORT & MODERATION SYSTEM
        // ═══════════════════════════════════════════════════════════

        // Content Reports (jobs, messages, profiles, etc.)
        if (!Schema::hasTable('content_reports')) {
            Schema::create('content_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reported_by_user_id')->constrained('users')->onDelete('restrict');
                $table->string('reportable_type')->comment('App\\Models\\Job, App\\Models\\User, App\\Models\\Message, etc.');
                $table->unsignedBigInteger('reportable_id');
                $table->foreignId('reported_user_id')->nullable()->constrained('users')->nullOnDelete()->comment('User being reported');
                $table->string('reason')->comment('inappropriate, spam, scam, harassment, discrimination, fake, offensive, other');
                $table->text('description')->nullable()->comment('Detailed explanation from reporter');
                $table->json('evidence')->nullable()->comment('Screenshots, message links, etc.');
                $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
                $table->enum('status', ['pending', 'under_review', 'action_taken', 'dismissed', 'escalated'])->default('pending');
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete()->comment('Moderator assigned');
                $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('resolution_notes')->nullable();
                $table->json('action_taken')->nullable()->comment('Actions: warning, suspension, content_removal, ban');
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['reportable_type', 'reportable_id']);
                $table->index(['reported_user_id', 'status']);
                $table->index(['status', 'created_at']);
                $table->index('reason');
            });
        }

        // Moderation Queue
        if (!Schema::hasTable('moderation_queue')) {
            Schema::create('moderation_queue', function (Blueprint $table) {
                $table->id();
                $table->morphs('moderable'); // This creates moderable_type and moderable_id
                $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('flagged_by_system')->nullable()->comment('System user ID for auto-flagging');
                $table->string('flag_reason')->comment('auto_flagged, user_reported, ai_suspicious, keyword_match');
                $table->text('description')->nullable();
                $table->json('context_data')->nullable()->comment('Additional context for moderators');
                $table->json('ai_analysis')->nullable()->comment('AI moderation suggestions');
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                $table->enum('status', ['queued', 'in_review', 'approved', 'rejected', 'escalated'])->default('queued');
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('moderator_notes')->nullable();
                $table->json('action_taken')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('action_taken_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'priority']);
                $table->index('assigned_to_user_id');
            });
        }

        // Moderation Actions Log
        if (!Schema::hasTable('moderation_actions')) {
            Schema::create('moderation_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('moderation_queue_id')->nullable()->constrained('moderation_queue')->nullOnDelete();
                $table->foreignId('taken_by_user_id')->constrained('users');
                $table->string('action_type')->comment('warning, content_removal, temporary_suspension, permanent_ban, account_restriction');
                $table->string('target_type');
                $table->unsignedBigInteger('target_id');
                $table->json('action_data')->nullable()->comment('Details of action taken');
                $table->text('reason');
                $table->timestamp('expires_at')->nullable()->comment('For temporary actions');
                $table->timestamps();

                $table->index(['target_type', 'target_id']);
                $table->index('action_type');
            });
        }

        // Trust & Safety Dashboard Metrics Cache
        if (!Schema::hasTable('trust_safety_metrics')) {
            Schema::create('trust_safety_metrics', function (Blueprint $table) {
                $table->id();
                $table->date('metric_date');
                $table->string('metric_type')->comment('reports_received, reports-resolved, fraud_detected, verifications_completed, etc.');
                $table->integer('count')->default(0);
                $table->json('breakdown')->nullable()->comment('Breakdown by category, severity, etc.');
                $table->timestamps();

                $table->unique(['metric_date', 'metric_type']);
                $table->index('metric_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trust_safety_metrics');
        Schema::dropIfExists('moderation_actions');
        Schema::dropIfExists('moderation_queue');
        Schema::dropIfExists('content_reports');
        Schema::dropIfExists('background_check_access_logs');
        Schema::dropIfExists('background_check_providers');
        Schema::dropIfExists('employer_red_flags');
        Schema::dropIfExists('employer_interview_reviews');
        Schema::dropIfExists('employer_trust_scores');
        Schema::dropIfExists('employer_business_licenses');
        Schema::dropIfExists('fraud_detection_logs');
        Schema::dropIfExists('identity_verifications');
    }
};
