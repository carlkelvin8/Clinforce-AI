<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plans
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->unsignedSmallInteger('duration_months')->default(1);
                $table->integer('job_post_limit')->default(10);
                $table->boolean('ai_screening_enabled')->default(false);
                $table->boolean('analytics_enabled')->default(false);
                $table->integer('price_cents');
                $table->string('currency', 3)->default('USD');
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Subscriptions
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('plan_id')->constrained('plans');
                $table->string('status', 50)->default('active');
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
            });
        }

        // Jobs
        if (!Schema::hasTable('jobs_table')) {
            Schema::create('jobs_table', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_user_id')->constrained('users');
                $table->string('owner_type', 20);
                $table->string('title', 200);
                $table->text('description')->nullable();
                $table->string('location', 200)->nullable();
                $table->string('employment_type', 50)->nullable();
                $table->string('experience_level', 50)->nullable();
                $table->integer('salary_min')->nullable();
                $table->integer('salary_max')->nullable();
                $table->string('salary_currency', 3)->nullable();
                $table->string('status', 50)->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        // Job Applications
        if (!Schema::hasTable('job_applications')) {
            Schema::create('job_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_id')->constrained('jobs_table')->onDelete('cascade');
                $table->foreignId('applicant_user_id')->constrained('users');
                $table->string('status', 50)->default('submitted');
                $table->text('cover_letter')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
            });
        }

        // Documents
        if (!Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('doc_type', 50);
                $table->string('file_url', 500);
                $table->string('file_name', 255);
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('file_size_bytes')->nullable();
                $table->string('status', 50)->default('active');
                $table->timestamps();
            });
        }

        // Application Status History
        if (!Schema::hasTable('application_status_history')) {
            Schema::create('application_status_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('job_applications')->onDelete('cascade');
                $table->string('from_status', 50)->nullable();
                $table->string('to_status', 50);
                $table->foreignId('changed_by_user_id')->constrained('users');
                $table->text('note')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Interviews
        if (!Schema::hasTable('interviews')) {
            Schema::create('interviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('job_applications')->onDelete('cascade');
                $table->string('interview_type', 50);
                $table->timestamp('scheduled_at');
                $table->unsignedSmallInteger('duration_minutes')->default(60);
                $table->string('location', 255)->nullable();
                $table->string('meeting_link', 500)->nullable();
                $table->text('notes')->nullable();
                $table->string('status', 50)->default('scheduled');
                $table->timestamps();
            });
        }

        // Payments
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users');
                $table->foreignId('subscription_id')->nullable()->constrained('subscriptions');
                $table->integer('amount_cents');
                $table->string('currency_code', 3);
                $table->string('status', 50)->default('pending');
                $table->string('provider', 50)->nullable();
                $table->string('provider_ref', 255)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        // Invitations
        if (!Schema::hasTable('invitations')) {
            Schema::create('invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sender_user_id')->constrained('users');
                $table->string('recipient_email', 255);
                $table->string('recipient_role', 20);
                $table->string('token', 64)->unique();
                $table->string('status', 50)->default('pending');
                $table->timestamp('expires_at');
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
            });
        }

        // Verification Requests
        if (!Schema::hasTable('verification_requests')) {
            Schema::create('verification_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users');
                $table->string('request_type', 50);
                $table->json('documents')->nullable();
                $table->string('status', 50)->default('pending');
                $table->text('admin_notes')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users');
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        // AI Screenings
        if (!Schema::hasTable('ai_screenings')) {
            Schema::create('ai_screenings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('job_applications')->onDelete('cascade');
                $table->decimal('overall_score', 5, 2)->nullable();
                $table->json('scores')->nullable();
                $table->text('summary')->nullable();
                $table->text('strengths')->nullable();
                $table->text('concerns')->nullable();
                $table->timestamps();
            });
        }

        // Audit Logs
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_user_id')->nullable()->constrained('users');
                $table->string('action', 100);
                $table->string('entity_type', 100)->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('metadata')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Conversations
        if (!Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table) {
                $table->id();
                $table->string('subject', 255)->nullable();
                $table->timestamps();
            });
        }

        // Conversation Participants
        if (!Schema::hasTable('conversation_participants')) {
            Schema::create('conversation_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('last_read_at')->nullable();
                $table->timestamps();
                
                $table->unique(['conversation_id', 'user_id']);
            });
        }

        // Messages
        if (!Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
                $table->foreignId('sender_user_id')->constrained('users');
                $table->text('body');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('ai_screenings');
        Schema::dropIfExists('verification_requests');
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('application_status_history');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('jobs_table');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
