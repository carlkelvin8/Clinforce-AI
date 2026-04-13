<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 2.2 Smart Screening & Assessments — All Tables
     */
    public function up(): void
    {
        // ── 1. Screening Questions (per-job knockout questions) ──
        Schema::create('screening_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->string('question');
            $table->enum('type', ['text', 'yes_no', 'multiple_choice', 'number'])->default('text');
            $table->json('options')->nullable()->comment('For multiple_choice: array of strings');
            $table->boolean('is_knockout')->default(false)->comment('If failed, auto-reject application');
            $table->string('knockout_value')->nullable()->comment('Value that triggers knockout (e.g. "no", "<2")');
            $table->integer('order')->default(0);
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->index(['job_id', 'is_knockout']);
            $table->unique(['job_id', 'question'], 'job_question_unique');
        });

        // ── 2. Screening Answers (candidate responses) ──
        Schema::create('screening_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('screening_questions')->onDelete('cascade');
            $table->foreignId('application_id')->constrained('job_applications')->onDelete('cascade');
            $table->text('answer')->nullable();
            $table->boolean('knockout_triggered')->default(false);
            $table->timestamps();

            $table->unique(['question_id', 'application_id']);
            $table->index('knockout_triggered');
        });

        // ── 3. Async Video Interviews (employer-defined Q&A sessions) ──
        Schema::create('async_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('questions')->comment('Array of {id, question, time_limit_sec, max_duration_sec, allow_retries}');
            $table->integer('max_duration_minutes')->default(15)->comment('Total session time limit');
            $table->boolean('allow_retries')->default(false)->comment('Allow re-recording individual answers');
            $table->dateTime('expires_at')->nullable()->comment('Deadline for candidates to complete');
            $table->boolean('is_active')->default(true);
            $table->integer('total_responses')->default(0);
            $table->timestamps();

            $table->index(['job_id', 'is_active']);
        });

        // ── 4. Async Video Responses (candidate recorded answers) ──
        Schema::create('async_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('async_interview_id')->constrained('async_interviews')->onDelete('cascade');
            $table->foreignId('application_id')->constrained('job_applications')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->json('answers')->comment('Array of {question_id, video_url, thumbnail_url, duration_sec, transcript, retry_count, created_at}');
            $table->enum('status', ['in_progress', 'completed', 'expired'])->default('in_progress');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->unique(['async_interview_id', 'application_id']);
            $table->index(['user_id', 'status']);
        });

        // ── 5. Reference Checks ──
        Schema::create('reference_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('job_applications')->onDelete('cascade');
            $table->foreignId('requested_by_user_id')->constrained('users')->onDelete('restrict');
            $table->string('referee_name');
            $table->string('referee_email');
            $table->string('referee_phone')->nullable();
            $table->string('referee_relationship')->nullable()->comment('e.g. "Former Supervisor", "Colleague"');
            $table->string('referee_title')->nullable()->comment('Job title of referee');
            $table->string('referee_company')->nullable();
            $table->json('questions')->nullable()->comment('Custom questions array');
            $table->json('responses')->nullable()->comment('Referee answers array');
            $table->enum('status', ['pending', 'sent', 'completed', 'expired', 'bounced'])->default('pending');
            $table->text('comments')->nullable()->comment('Free-form comments from referee');
            $table->float('rating')->nullable()->comment('Overall rating 1-5');
            $table->boolean('would_rehire')->nullable()->comment('Would they rehire this candidate?');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('token', 64)->unique()->comment('Secure token for referee response link');
            $table->integer('reminder_count')->default(0);
            $table->timestamp('last_reminder_at')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'status']);
            $table->index('referee_email');
            $table->index('token');
        });

        // ── 6. Background Checks ──
        Schema::create('background_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('job_applications')->onDelete('cascade');
            $table->foreignId('requested_by_user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('User responsible for review');
            $table->string('provider')->nullable()->comment('e.g. "checkr", "goodhire", "manual"');
            $table->string('provider_reference_id')->nullable()->comment('External provider case/reference ID');
            $table->enum('type', ['criminal', 'employment', 'education', 'drug', 'credit', 'comprehensive', 'manual'])->default('manual');
            $table->enum('status', ['pending', 'initiated', 'in_progress', 'completed', 'flagged', 'cancelled'])->default('pending');
            $table->enum('result', ['clear', 'flagged', 'inconclusive', 'failed'])->nullable();
            $table->json('report_data')->nullable()->comment('Full report from provider or manual findings');
            $table->text('summary')->nullable()->comment('Human-readable summary');
            $table->text('notes')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->comment('Report validity expiry');
            $table->boolean('candidate_consent')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'status']);
            $table->index(['provider', 'status']);
        });

        // ── 7. Credential / License Verifications ──
        Schema::create('credential_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('credential_type')->comment('e.g. "PRC License", "BLS", "ACLS", "RN License"');
            $table->string('license_number');
            $table->string('issuing_authority')->nullable()->comment('e.g. "Professional Regulation Commission"');
            $table->string('country')->nullable();
            $table->string('state_province')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('verification_url')->nullable()->comment('URL to verify on issuing authority website');
            $table->enum('method', ['manual', 'api', 'document_review'])->default('manual');
            $table->enum('status', ['pending', 'verified', 'expired', 'invalid', 'mismatch'])->default('pending');
            $table->string('document_url')->nullable()->comment('Uploaded license/certificate file');
            $table->json('verification_data')->nullable()->comment('API response or verification details');
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->index(['applicant_user_id', 'status']);
            $table->index(['credential_type', 'status']);
            $table->index('license_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_verifications');
        Schema::dropIfExists('background_checks');
        Schema::dropIfExists('reference_checks');
        Schema::dropIfExists('async_responses');
        Schema::dropIfExists('async_interviews');
        Schema::dropIfExists('screening_answers');
        Schema::dropIfExists('screening_questions');
    }
};
