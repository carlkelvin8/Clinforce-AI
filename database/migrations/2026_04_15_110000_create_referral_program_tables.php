<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Referral programs configuration
        Schema::create('referral_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('bonus_amount', 10, 2)->default(0);
            $table->string('bonus_currency', 3)->default('USD');
            $table->string('bonus_type')->default('fixed'); // fixed, percentage, tiered
            $table->json('bonus_tiers')->nullable(); // For tiered bonuses
            $table->integer('days_until_eligible')->default(90); // Days new hire must stay
            $table->boolean('allow_external_referrals')->default(true);
            $table->integer('max_referrals_per_employee')->nullable();
            $table->json('eligible_job_types')->nullable(); // Which jobs qualify
            $table->timestamp('program_start_date')->nullable();
            $table->timestamp('program_end_date')->nullable();
            $table->timestamps();
            
            $table->index(['employer_id', 'is_active']);
        });

        // Employee referrals
        Schema::create('employee_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_program_id')->constrained()->onDelete('cascade');
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade'); // Employee making referral
            $table->foreignId('job_id')->nullable()->constrained('jobs_table')->onDelete('set null');
            
            // Candidate information
            $table->string('candidate_first_name');
            $table->string('candidate_last_name');
            $table->string('candidate_email');
            $table->string('candidate_phone')->nullable();
            $table->text('candidate_resume_path')->nullable();
            $table->string('candidate_linkedin')->nullable();
            
            // Referral details
            $table->text('relationship_description')->nullable(); // How they know the candidate
            $table->text('why_good_fit')->nullable();
            $table->integer('years_known')->nullable();
            $table->string('referral_source')->default('employee'); // employee, external
            
            // Status tracking
            $table->string('status')->default('submitted'); // submitted, reviewed, interviewing, hired, rejected, withdrawn
            $table->foreignId('application_id')->nullable()->constrained('job_applications')->onDelete('set null');
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('hired_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Bonus tracking
            $table->decimal('bonus_amount', 10, 2)->nullable();
            $table->string('bonus_status')->default('pending'); // pending, eligible, approved, paid, forfeited
            $table->timestamp('bonus_eligible_date')->nullable();
            $table->timestamp('bonus_approved_at')->nullable();
            $table->timestamp('bonus_paid_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            $table->index(['referrer_id', 'status']);
            $table->index(['status', 'bonus_status']);
            $table->index('submitted_at');
        });

        // Referral status history
        Schema::create('referral_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_referral_id')->constrained()->onDelete('cascade');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('employee_referral_id');
        });

        // Referral bonuses/payments
        Schema::create('referral_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_referral_id')->constrained()->onDelete('cascade');
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method')->nullable(); // payroll, check, direct_deposit, gift_card
            $table->string('status')->default('pending'); // pending, processing, paid, failed
            $table->timestamp('scheduled_payment_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['referrer_id', 'status']);
            $table->index('scheduled_payment_date');
        });

        // Referral leaderboard (cached/materialized view)
        Schema::create('referral_leaderboard', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->string('period')->default('all_time'); // all_time, year, quarter, month
            $table->integer('year')->nullable();
            $table->integer('quarter')->nullable();
            $table->integer('month')->nullable();
            
            // Metrics
            $table->integer('total_referrals')->default(0);
            $table->integer('successful_hires')->default(0);
            $table->integer('pending_referrals')->default(0);
            $table->decimal('total_bonuses_earned', 10, 2)->default(0);
            $table->decimal('total_bonuses_paid', 10, 2)->default(0);
            $table->decimal('success_rate', 5, 2)->default(0); // Percentage
            $table->integer('rank')->nullable();
            
            $table->timestamp('last_updated_at');
            $table->timestamps();
            
            $table->unique(['employer_id', 'referrer_id', 'period', 'year', 'quarter', 'month']);
            $table->index(['employer_id', 'period', 'rank']);
        });

        // Referral notifications/reminders
        Schema::create('referral_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_referral_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('notification_type'); // status_update, bonus_eligible, bonus_paid, reminder
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
        });

        // Referral program settings/rules
        Schema::create('referral_program_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_program_id')->constrained()->onDelete('cascade');
            $table->string('rule_type'); // eligibility, bonus_calculation, notification
            $table->string('rule_name');
            $table->json('rule_config');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('referral_program_id');
        });

        // Referral analytics/metrics
        Schema::create('referral_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referral_program_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('metric_date');
            
            // Daily metrics
            $table->integer('referrals_submitted')->default(0);
            $table->integer('referrals_reviewed')->default(0);
            $table->integer('referrals_hired')->default(0);
            $table->integer('referrals_rejected')->default(0);
            $table->decimal('total_bonuses_approved', 10, 2)->default(0);
            $table->decimal('total_bonuses_paid', 10, 2)->default(0);
            $table->decimal('average_time_to_hire', 8, 2)->default(0); // Days
            $table->decimal('conversion_rate', 5, 2)->default(0); // Percentage
            
            $table->timestamps();
            
            $table->unique(['employer_id', 'referral_program_id', 'metric_date']);
            $table->index('metric_date');
        });

        // Referral feedback/reviews
        Schema::create('referral_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_referral_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->integer('quality_rating')->nullable(); // 1-5
            $table->text('feedback_notes')->nullable();
            $table->boolean('would_interview_again')->nullable();
            $table->timestamps();
            
            $table->index('employee_referral_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_feedback');
        Schema::dropIfExists('referral_analytics');
        Schema::dropIfExists('referral_program_rules');
        Schema::dropIfExists('referral_notifications');
        Schema::dropIfExists('referral_leaderboard');
        Schema::dropIfExists('referral_bonuses');
        Schema::dropIfExists('referral_status_history');
        Schema::dropIfExists('employee_referrals');
        Schema::dropIfExists('referral_programs');
    }
};
