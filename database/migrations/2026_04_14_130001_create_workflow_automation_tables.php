<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workflow Automation Features Migration
     * 
     * Covers:
     * - Hiring Workflow Automation (custom pipelines, auto-advance, approvals, SLA tracking)
     * - Automated Email Sequences (welcome, nurture, re-engagement, follow-up)
     * - Document Generation (offer letters, contracts, references, onboarding)
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // HIRING WORKFLOW AUTOMATION
        // ═══════════════════════════════════════════════════════════

        // Hiring Workflows (custom pipelines per job/department)
        if (!Schema::hasTable('hiring_workflows')) {
            Schema::create('hiring_workflows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('scope')->comment('job, department, global');
                $table->foreignId('job_id')->nullable()->constrained('jobs_table')->nullOnDelete();
                $table->string('department')->nullable();
                $table->json('stages')->comment('Array of workflow stages with conditions');
                $table->json('auto_advance_rules')->nullable()->comment('Conditions for auto-advancing candidates');
                $table->json('approval_rules')->nullable()->comment('Approval requirements per stage');
                $table->json('sla_settings')->nullable()->comment('SLA time limits per stage');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->index(['employer_user_id', 'is_active']);
                $table->index(['scope', 'department']);
            });
        }

        // Workflow Stage Transitions (tracking candidate movement)
        if (!Schema::hasTable('workflow_stage_transitions')) {
            Schema::create('workflow_stage_transitions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('job_applications')->onDelete('cascade');
                $table->foreignId('workflow_id')->constrained('hiring_workflows')->onDelete('cascade');
                $table->string('from_stage')->nullable();
                $table->string('to_stage');
                $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('trigger_type', ['manual', 'auto_advance', 'approval', 'sla_breach'])->default('manual');
                $table->json('conditions_met')->nullable()->comment('Which conditions triggered the transition');
                $table->text('notes')->nullable();
                $table->timestamp('entered_at')->useCurrent();
                $table->timestamp('exited_at')->nullable();
                $table->integer('time_in_stage_hours')->nullable();
                $table->timestamps();

                $table->index(['application_id', 'entered_at']);
                $table->index(['workflow_id', 'to_stage']);
                $table->index('trigger_type');
            });
        }

        // Approval Requests (hire requisition → approval → offer)
        if (!Schema::hasTable('approval_requests')) {
            Schema::create('approval_requests', function (Blueprint $table) {
                $table->id();
                $table->string('approvable_type')->comment('Job, Application, Offer, etc.');
                $table->unsignedBigInteger('approvable_id');
                $table->foreignId('requester_user_id')->constrained('users')->onDelete('cascade');
                $table->string('approval_type')->comment('hire_requisition, job_posting, offer_approval, budget_approval');
                $table->text('request_details')->nullable();
                $table->json('approval_chain')->comment('Array of required approvers in order');
                $table->integer('current_step')->default(0);
                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
                $table->foreignId('current_approver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('submitted_at')->useCurrent();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->index(['approvable_type', 'approvable_id']);
                $table->index(['status', 'current_approver_id']);
                $table->index('approval_type');
            });
        }

        // Approval Actions (individual approver responses)
        if (!Schema::hasTable('approval_actions')) {
            Schema::create('approval_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
                $table->foreignId('approver_user_id')->constrained('users')->onDelete('cascade');
                $table->integer('step_number');
                $table->enum('action', ['approved', 'rejected', 'delegated'])->nullable();
                $table->text('comments')->nullable();
                $table->foreignId('delegated_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('acted_at')->nullable();
                $table->timestamps();

                $table->unique(['approval_request_id', 'approver_user_id', 'step_number'], 'unique_approval_action');
                $table->index('approver_user_id');
            });
        }

        // SLA Tracking (time in each stage)
        if (!Schema::hasTable('sla_violations')) {
            Schema::create('sla_violations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('job_applications')->onDelete('cascade');
                $table->foreignId('workflow_id')->constrained('hiring_workflows')->onDelete('cascade');
                $table->string('stage_name');
                $table->integer('sla_hours');
                $table->integer('actual_hours');
                $table->integer('breach_hours');
                $table->enum('severity', ['minor', 'major', 'critical'])->default('minor');
                $table->boolean('is_resolved')->default(false);
                $table->text('resolution_notes')->nullable();
                $table->timestamp('breached_at');
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['application_id', 'is_resolved']);
                $table->index(['severity', 'breached_at']);
            });
        }

        // ═══════════════════════════════════════════════════════════
        // AUTOMATED EMAIL SEQUENCES
        // ═══════════════════════════════════════════════════════════

        // Email Sequences (welcome, nurture, re-engagement, follow-up)
        if (!Schema::hasTable('email_sequences')) {
            Schema::create('email_sequences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('type', ['welcome', 'nurture', 're_engagement', 'follow_up', 'onboarding', 'rejection'])->default('welcome');
                $table->enum('trigger_event', ['application_submitted', 'profile_created', 'interview_completed', 'offer_sent', 'hired', 'inactive_period', 'custom'])->default('application_submitted');
                $table->json('trigger_conditions')->nullable()->comment('Conditions that must be met to start sequence');
                $table->json('target_audience')->nullable()->comment('Who receives this sequence');
                $table->boolean('is_active')->default(true);
                $table->integer('total_emails')->default(0);
                $table->timestamps();

                $table->index(['employer_user_id', 'type', 'is_active']);
                $table->index('trigger_event');
            });
        }

        // Email Sequence Steps (individual emails in sequence)
        if (!Schema::hasTable('email_sequence_steps')) {
            Schema::create('email_sequence_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sequence_id')->constrained('email_sequences')->onDelete('cascade');
                $table->integer('step_number');
                $table->string('subject');
                $table->text('body_template')->comment('Email template with placeholders');
                $table->integer('delay_hours')->default(0)->comment('Hours to wait before sending');
                $table->json('send_conditions')->nullable()->comment('Additional conditions to check before sending');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['sequence_id', 'step_number']);
                $table->index(['sequence_id', 'is_active']);
            });
        }

        // Email Sequence Enrollments (users enrolled in sequences)
        if (!Schema::hasTable('email_sequence_enrollments')) {
            Schema::create('email_sequence_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sequence_id')->constrained('email_sequences')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('application_id')->nullable()->constrained('job_applications')->nullOnDelete();
                $table->enum('status', ['active', 'completed', 'paused', 'cancelled'])->default('active');
                $table->integer('current_step')->default(0);
                $table->timestamp('enrolled_at')->useCurrent();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('next_email_at')->nullable();
                $table->json('context_data')->nullable()->comment('Data for email personalization');
                $table->timestamps();

                $table->unique(['sequence_id', 'user_id', 'application_id'], 'unique_enrollment');
                $table->index(['status', 'next_email_at']);
                $table->index('user_id');
            });
        }

        // Email Sequence Sends (tracking individual email sends)
        if (!Schema::hasTable('email_sequence_sends')) {
            Schema::create('email_sequence_sends', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enrollment_id')->constrained('email_sequence_enrollments')->onDelete('cascade');
                $table->foreignId('step_id')->constrained('email_sequence_steps')->onDelete('cascade');
                $table->string('recipient_email');
                $table->string('subject');
                $table->text('body_html');
                $table->enum('status', ['queued', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed'])->default('queued');
                $table->string('provider_message_id')->nullable();
                $table->json('provider_response')->nullable();
                $table->timestamp('queued_at')->useCurrent();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('clicked_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['enrollment_id', 'status']);
                $table->index(['recipient_email', 'sent_at']);
                $table->index('provider_message_id');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // DOCUMENT GENERATION
        // ═══════════════════════════════════════════════════════════

        // Document Templates (offer letters, contracts, references, onboarding)
        if (!Schema::hasTable('document_templates')) {
            Schema::create('document_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('type', ['offer_letter', 'employment_contract', 'reference_letter', 'onboarding_packet', 'termination_letter', 'custom'])->default('offer_letter');
                $table->text('template_content')->comment('Template with placeholders like {{candidate_name}}');
                $table->json('required_fields')->nullable()->comment('Fields that must be filled');
                $table->json('optional_fields')->nullable()->comment('Optional fields available');
                $table->string('file_format')->default('pdf')->comment('pdf, docx, html');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->string('letterhead_url')->nullable()->comment('Company letterhead image');
                $table->json('styling_options')->nullable()->comment('Font, colors, layout options');
                $table->timestamps();

                $table->index(['employer_user_id', 'type', 'is_active']);
                $table->index('is_default');
            });
        }

        // Generated Documents (instances of generated documents)
        if (!Schema::hasTable('generated_documents')) {
            Schema::create('generated_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('document_templates')->onDelete('cascade');
                $table->foreignId('application_id')->nullable()->constrained('job_applications')->nullOnDelete();
                $table->foreignId('generated_by_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('document_name');
                $table->enum('status', ['draft', 'generated', 'sent', 'signed', 'archived'])->default('draft');
                $table->json('field_values')->comment('Values used to fill template placeholders');
                $table->string('file_path')->nullable()->comment('Path to generated document file');
                $table->string('file_url')->nullable()->comment('Public URL to document');
                $table->string('file_format');
                $table->integer('file_size_bytes')->nullable();
                $table->string('version')->default('1.0');
                $table->timestamp('generated_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->json('signature_data')->nullable()->comment('Digital signature information');
                $table->timestamps();

                $table->index(['application_id', 'status']);
                $table->index(['generated_by_user_id', 'generated_at']);
                $table->index(['recipient_user_id', 'status']);
            });
        }

        // Document Signatures (tracking document signing)
        if (!Schema::hasTable('document_signatures')) {
            Schema::create('document_signatures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('generated_documents')->onDelete('cascade');
                $table->foreignId('signer_user_id')->constrained('users')->onDelete('cascade');
                $table->string('signer_name');
                $table->string('signer_email');
                $table->enum('signature_type', ['electronic', 'digital', 'wet_signature'])->default('electronic');
                $table->text('signature_data')->nullable()->comment('Base64 signature image or digital signature');
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamp('signed_at');
                $table->boolean('is_valid')->default(true);
                $table->timestamps();

                $table->index(['document_id', 'signed_at']);
                $table->index('signer_user_id');
            });
        }

        // Document Access Logs (tracking who accessed documents)
        if (!Schema::hasTable('document_access_logs')) {
            Schema::create('document_access_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('generated_documents')->onDelete('cascade');
                $table->foreignId('accessed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('access_type')->comment('view, download, print, share');
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamp('accessed_at')->useCurrent();
                $table->timestamps();

                $table->index(['document_id', 'accessed_at']);
                $table->index('accessed_by_user_id');
            });
        }

        // Workflow Automation Logs (general automation tracking)
        if (!Schema::hasTable('workflow_automation_logs')) {
            Schema::create('workflow_automation_logs', function (Blueprint $table) {
                $table->id();
                $table->string('automation_type')->comment('workflow_advance, email_sequence, document_generation, approval_request');
                $table->string('entity_type')->nullable()->comment('Application, User, Job, etc.');
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action_taken');
                $table->json('conditions_checked')->nullable();
                $table->json('results')->nullable();
                $table->enum('status', ['success', 'failed', 'skipped'])->default('success');
                $table->text('error_message')->nullable();
                $table->timestamp('executed_at')->useCurrent();
                $table->timestamps();

                $table->index(['automation_type', 'executed_at']);
                $table->index(['entity_type', 'entity_id']);
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_automation_logs');
        Schema::dropIfExists('document_access_logs');
        Schema::dropIfExists('document_signatures');
        Schema::dropIfExists('generated_documents');
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('email_sequence_sends');
        Schema::dropIfExists('email_sequence_enrollments');
        Schema::dropIfExists('email_sequence_steps');
        Schema::dropIfExists('email_sequences');
        Schema::dropIfExists('sla_violations');
        Schema::dropIfExists('approval_actions');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('workflow_stage_transitions');
        Schema::dropIfExists('hiring_workflows');
    }
};