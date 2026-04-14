<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Advanced Analytics & Market Intelligence Migration
     * 
     * Covers:
     * - Hiring Analytics (time-to-hire, source attribution, cost-per-hire, funnel)
     * - Market Intelligence (salary benchmarks, supply/demand, competitor analysis)
     * - Custom Report Builder
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // HIRING ANALYTICS
        // ═══════════════════════════════════════════════════════════

        // Hiring Metrics Cache (daily aggregates)
        if (!Schema::hasTable('hiring_metrics')) {
            Schema::create('hiring_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->date('metric_date');
                $table->string('metric_type')->comment('time_to_hire, cost_per_hire, source_attribution, funnel_conversion');
                $table->string('dimension')->nullable()->comment('role, department, location, source_channel');
                $table->string('dimension_value')->nullable();
                $table->decimal('metric_value', 12, 2);
                $table->json('breakdown')->nullable()->comment('Detailed breakdown data');
                $table->integer('sample_size')->default(0)->comment('Number of data points');
                $table->timestamps();

                $table->index(['employer_user_id', 'metric_date']);
                $table->index(['metric_type', 'dimension']);
                $table->unique(['employer_user_id', 'metric_date', 'metric_type', 'dimension', 'dimension_value'], 'unique_metric');
            });
        }

        // Application Source Tracking
        if (!Schema::hasTable('application_sources')) {
            Schema::create('application_sources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('job_applications')->onDelete('cascade');
                $table->string('source_type')->comment('direct, referral, job_board, social_media, email_campaign, organic_search, paid_ad');
                $table->string('source_name')->nullable()->comment('LinkedIn, Indeed, Facebook, etc.');
                $table->string('campaign_id')->nullable()->comment('Marketing campaign identifier');
                $table->string('utm_source')->nullable();
                $table->string('utm_medium')->nullable();
                $table->string('utm_campaign')->nullable();
                $table->string('referrer_url')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->timestamps();

                $table->unique('application_id');
                $table->index(['source_type', 'source_name']);
            });
        }

        // Hiring Cost Tracking
        if (!Schema::hasTable('hiring_costs')) {
            Schema::create('hiring_costs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('job_id')->nullable()->constrained('jobs_table')->nullOnDelete();
                $table->foreignId('application_id')->nullable()->constrained('job_applications')->nullOnDelete();
                $table->string('cost_type')->comment('job_board_fee, advertising, recruiter_fee, background_check, assessment, other');
                $table->decimal('amount_cents', 12, 0);
                $table->string('currency_code', 3)->default('USD');
                $table->text('description')->nullable();
                $table->date('incurred_date');
                $table->timestamps();

                $table->index(['employer_user_id', 'incurred_date']);
                $table->index(['job_id', 'cost_type']);
            });
        }

        // Funnel Stage Timestamps (for conversion analysis)
        if (!Schema::hasColumn('job_applications', 'viewed_at')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->timestamp('viewed_at')->nullable()->after('status')->comment('When employer first viewed');
                $table->timestamp('shortlisted_at')->nullable()->after('viewed_at');
                $table->timestamp('interviewed_at')->nullable()->after('shortlisted_at');
                $table->timestamp('offered_at')->nullable()->after('interviewed_at');
                $table->timestamp('hired_at')->nullable()->after('offered_at');
                $table->timestamp('rejected_at')->nullable()->after('hired_at');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // MARKET INTELLIGENCE
        // ═══════════════════════════════════════════════════════════

        // Salary Benchmarks
        if (!Schema::hasTable('salary_benchmarks')) {
            Schema::create('salary_benchmarks', function (Blueprint $table) {
                $table->id();
                $table->string('job_title');
                $table->string('normalized_title')->comment('Standardized title for grouping');
                $table->string('country');
                $table->string('state')->nullable();
                $table->string('city')->nullable();
                $table->string('employment_type')->default('full_time');
                $table->integer('years_experience_min')->default(0);
                $table->integer('years_experience_max')->default(99);
                $table->decimal('salary_min_cents', 12, 0);
                $table->decimal('salary_max_cents', 12, 0);
                $table->decimal('salary_median_cents', 12, 0);
                $table->decimal('salary_p25_cents', 12, 0)->comment('25th percentile');
                $table->decimal('salary_p75_cents', 12, 0)->comment('75th percentile');
                $table->string('currency_code', 3)->default('USD');
                $table->integer('sample_size')->default(0);
                $table->date('data_date')->comment('When this benchmark was calculated');
                $table->timestamps();

                $table->index(['normalized_title', 'country', 'state']);
                $table->index(['country', 'city']);
                $table->index('data_date');
            });
        }

        // Supply/Demand Heatmap Data
        if (!Schema::hasTable('market_supply_demand')) {
            Schema::create('market_supply_demand', function (Blueprint $table) {
                $table->id();
                $table->string('job_category')->comment('Nurse, Doctor, Allied Health, etc.');
                $table->string('specialty')->nullable()->comment('ICU, ER, Pediatrics, etc.');
                $table->string('country');
                $table->string('state')->nullable();
                $table->string('city')->nullable();
                $table->integer('active_jobs_count')->default(0);
                $table->integer('active_candidates_count')->default(0);
                $table->decimal('demand_supply_ratio', 8, 2)->default(0)->comment('Jobs per candidate');
                $table->enum('market_temperature', ['cold', 'cool', 'warm', 'hot', 'very_hot'])->default('warm');
                $table->decimal('avg_time_to_fill_days', 8, 2)->nullable();
                $table->date('snapshot_date');
                $table->timestamps();

                $table->index(['job_category', 'country', 'state']);
                $table->index(['snapshot_date', 'market_temperature']);
            });
        }

        // Competitor Job Postings (scraped or manually tracked)
        if (!Schema::hasTable('competitor_jobs')) {
            Schema::create('competitor_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('competitor_name');
                $table->string('job_title');
                $table->string('normalized_title');
                $table->string('location')->nullable();
                $table->text('description_snippet')->nullable();
                $table->decimal('salary_min_cents', 12, 0)->nullable();
                $table->decimal('salary_max_cents', 12, 0)->nullable();
                $table->string('currency_code', 3)->default('USD');
                $table->string('source_url')->nullable();
                $table->date('posted_date')->nullable();
                $table->date('scraped_date');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['competitor_name', 'is_active']);
                $table->index(['normalized_title', 'scraped_date']);
            });
        }

        // Trending Skills & Certifications
        if (!Schema::hasTable('trending_skills')) {
            Schema::create('trending_skills', function (Blueprint $table) {
                $table->id();
                $table->string('skill_name');
                $table->string('skill_category')->comment('technical, clinical, soft_skill, certification');
                $table->integer('mention_count')->default(0)->comment('How many job posts mention this');
                $table->integer('demand_growth_pct')->default(0)->comment('% change vs last period');
                $table->enum('trend_direction', ['rising', 'stable', 'declining'])->default('stable');
                $table->date('period_start');
                $table->date('period_end');
                $table->timestamps();

                $table->index(['skill_category', 'trend_direction']);
                $table->index(['period_end', 'mention_count']);
            });
        }

        // ═══════════════════════════════════════════════════════════
        // CUSTOM REPORT BUILDER
        // ═══════════════════════════════════════════════════════════

        // Saved Custom Reports
        if (!Schema::hasTable('custom_reports')) {
            Schema::create('custom_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
                $table->string('report_name');
                $table->text('description')->nullable();
                $table->string('report_type')->comment('hiring_analytics, market_intelligence, custom');
                $table->json('config')->comment('Report configuration: metrics, dimensions, filters, date range');
                $table->json('columns')->comment('Selected columns and their order');
                $table->json('filters')->nullable()->comment('Applied filters');
                $table->string('schedule_frequency')->nullable()->comment('daily, weekly, monthly, null for manual');
                $table->string('schedule_day')->nullable()->comment('monday, tuesday, etc. or day of month');
                $table->time('schedule_time')->nullable();
                $table->json('email_recipients')->nullable()->comment('Array of email addresses');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_run_at')->nullable();
                $table->timestamps();

                $table->index(['created_by_user_id', 'is_active']);
                $table->index('schedule_frequency');
            });
        }

        // Report Execution History
        if (!Schema::hasTable('report_executions')) {
            Schema::create('report_executions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('custom_report_id')->constrained('custom_reports')->onDelete('cascade');
                $table->foreignId('executed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('execution_type', ['manual', 'scheduled'])->default('manual');
                $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
                $table->string('output_format')->comment('pdf, excel, csv, json');
                $table->string('file_path')->nullable()->comment('Path to generated file');
                $table->integer('row_count')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['custom_report_id', 'created_at']);
                $table->index(['status', 'execution_type']);
            });
        }

        // Industry Benchmarks (for comparison)
        if (!Schema::hasTable('industry_benchmarks')) {
            Schema::create('industry_benchmarks', function (Blueprint $table) {
                $table->id();
                $table->string('metric_name')->comment('avg_time_to_hire, avg_cost_per_hire, offer_acceptance_rate, etc.');
                $table->string('industry')->default('healthcare');
                $table->string('region')->nullable();
                $table->string('company_size')->nullable()->comment('small, medium, large, enterprise');
                $table->decimal('benchmark_value', 12, 2);
                $table->string('unit')->nullable()->comment('days, dollars, percentage');
                $table->date('period_start');
                $table->date('period_end');
                $table->string('data_source')->nullable()->comment('Where this benchmark came from');
                $table->timestamps();

                $table->index(['metric_name', 'industry', 'region']);
                $table->index('period_end');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_benchmarks');
        Schema::dropIfExists('report_executions');
        Schema::dropIfExists('custom_reports');
        Schema::dropIfExists('trending_skills');
        Schema::dropIfExists('competitor_jobs');
        Schema::dropIfExists('market_supply_demand');
        Schema::dropIfExists('salary_benchmarks');
        
        if (Schema::hasColumn('job_applications', 'viewed_at')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->dropColumn(['viewed_at', 'shortlisted_at', 'interviewed_at', 'offered_at', 'hired_at', 'rejected_at']);
            });
        }
        
        Schema::dropIfExists('hiring_costs');
        Schema::dropIfExists('application_sources');
        Schema::dropIfExists('hiring_metrics');
    }
};
