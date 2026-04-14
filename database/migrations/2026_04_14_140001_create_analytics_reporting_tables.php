<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Analytics & Reporting Features Migration
     * 
     * Covers:
     * - Advanced Hiring Analytics Dashboard (time-to-hire, source attribution, cost-per-hire, funnel analysis)
     * - Market Intelligence Reports (salary benchmarking, supply/demand, competitor analysis, trending skills)
     * - Custom Report Builder (drag-and-drop, scheduled reports, exports, benchmarks)
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // ADVANCED HIRING ANALYTICS
        // ═══════════════════════════════════════════════════════════

        // Hiring Metrics (aggregated analytics data)
        if (!Schema::hasTable('hiring_metrics')) {
            Schema::create('hiring_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('job_id')->nullable()->constrained('jobs_table')->nullOnDelete();
                $table->string('metric_type')->comment('time_to_hire, cost_per_hire, source_attribution, funnel_conversion');
                $table->string('dimension')->nullable()->comment('role, department, location, source, etc.');
                $table->string('dimension_value')->nullable();
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('metric_value', 10, 2);
                $table->json('metadata')->nullable()->comment('Additional metric details');
                $table->timestamps();

                $table->index(['employer_user_id', 'metric_type', 'period_start'], 'hiring_metrics_employer_type_period_idx');
                $table->index(['job_id', 'metric_type'], 'hiring_metrics_job_type_idx');
                $table->index(['dimension', 'dimension_value'], 'hiring_metrics_dimension_idx');
            });
        }

        // Source Attribution (tracking where candidates come from)
        if (!Schema::hasTable('source_attributions')) {
            Schema::create('source_attributions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('job_applications')->onDelete('cascade');
                $table->foreignId('job_id')->constrained('jobs_table')->onDelete('cascade');
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->string('source_type')->comment('organic, referral, job_board, social_media, direct, agency');
                $table->string('source_name')->nullable()->comment('Indeed, LinkedIn, Facebook, etc.');
                $table->string('campaign_id')->nullable()->comment('UTM campaign tracking');
                $table->string('medium')->nullable()->comment('UTM medium');
                $table->string('content')->nullable()->comment('UTM content');
                $table->decimal('cost', 8, 2)->nullable()->comment('Cost associated with this source');
                $table->string('referrer_url')->nullable();
                $table->json('utm_parameters')->nullable();
                $table->timestamp('attributed_at')->useCurrent();
                $table->timestamps();

                $table->index(['employer_user_id', 'source_type'], 'source_attr_employer_type_idx');
                $table->index(['job_id', 'source_type'], 'source_attr_job_type_idx');
                $table->index('campaign_id', 'source_attr_campaign_idx');
            });
        }

        // Hiring Funnel Stages (tracking conversion through hiring process)
        if (!Schema::hasTable('hiring_funnel_events')) {
            Schema::create('hiring_funnel_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('job_applications')->onDelete('cascade');
                $table->foreignId('job_id')->constrained('jobs_table')->onDelete('cascade');
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->string('stage')->comment('posted, viewed, applied, screened, interviewed, offered, hired, rejected');
                $table->timestamp('event_timestamp');
                $table->integer('days_from_posting')->nullable();
                $table->integer('days_from_application')->nullable();
                $table->json('event_metadata')->nullable();
                $table->timestamps();

                $table->index(['employer_user_id', 'stage', 'event_timestamp'], 'funnel_events_employer_stage_time_idx');
                $table->index(['job_id', 'stage'], 'funnel_events_job_stage_idx');
                $table->index('event_timestamp', 'funnel_events_timestamp_idx');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // MARKET INTELLIGENCE
        // ═══════════════════════════════════════════════════════════

        // Salary Benchmarks (market salary data)
        if (!Schema::hasTable('salary_benchmarks')) {
            Schema::create('salary_benchmarks', function (Blueprint $table) {
                $table->id();
                $table->string('job_title');
                $table->string('normalized_title')->comment('Standardized job title for comparison');
                $table->string('location_type')->comment('city, state, country, remote');
                $table->string('location_value');
                $table->string('experience_level')->comment('entry, mid, senior, executive');
                $table->string('employment_type')->comment('full_time, part_time, contract, per_diem');
                $table->decimal('salary_min', 10, 2);
                $table->decimal('salary_max', 10, 2);
                $table->decimal('salary_median', 10, 2);
                $table->decimal('salary_avg', 10, 2);
                $table->string('currency', 3)->default('USD');
                $table->integer('sample_size')->comment('Number of data points');
                $table->string('data_source')->comment('internal, glassdoor, payscale, bls, etc.');
                $table->date('data_date');
                $table->json('additional_data')->nullable()->comment('Benefits, bonuses, etc.');
                $table->timestamps();

                $table->index(['normalized_title', 'location_value', 'experience_level'], 'salary_bench_title_loc_exp_idx');
                $table->index(['location_type', 'location_value'], 'salary_bench_location_idx');
                $table->index('data_date', 'salary_bench_date_idx');
            });
        }

        // Supply Demand Analysis (market supply/demand data)
        if (!Schema::hasTable('supply_demand_metrics')) {
            Schema::create('supply_demand_metrics', function (Blueprint $table) {
                $table->id();
                $table->string('job_category')->comment('nursing, physician, therapist, etc.');
                $table->string('specialty')->nullable()->comment('ICU, ER, pediatric, etc.');
                $table->string('location_type')->comment('city, state, country');
                $table->string('location_value');
                $table->date('period_month');
                $table->integer('job_postings_count');
                $table->integer('applications_count');
                $table->integer('active_candidates_count');
                $table->decimal('supply_demand_ratio', 5, 2)->comment('candidates per job posting');
                $table->decimal('avg_time_to_fill', 5, 1)->comment('days');
                $table->decimal('competition_index', 5, 2)->comment('relative competition level');
                $table->json('trending_skills')->nullable();
                $table->timestamps();

                $table->index(['job_category', 'location_value', 'period_month'], 'supply_demand_cat_loc_period_idx');
                $table->index(['location_type', 'location_value'], 'supply_demand_location_idx');
                $table->index('period_month', 'supply_demand_period_idx');
            });
        }

        // Competitor Analysis (tracking competitor job postings)
        if (!Schema::hasTable('competitor_intelligence')) {
            Schema::create('competitor_intelligence', function (Blueprint $table) {
                $table->id();
                $table->string('competitor_name');
                $table->string('competitor_domain')->nullable();
                $table->string('job_title');
                $table->string('normalized_title');
                $table->string('location');
                $table->text('job_description')->nullable();
                $table->json('requirements')->nullable();
                $table->json('benefits')->nullable();
                $table->decimal('salary_min', 10, 2)->nullable();
                $table->decimal('salary_max', 10, 2)->nullable();
                $table->string('employment_type');
                $table->string('source_platform')->comment('indeed, linkedin, glassdoor, etc.');
                $table->string('external_job_id')->nullable();
                $table->timestamp('posted_at');
                $table->timestamp('scraped_at');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['competitor_name', 'normalized_title', 'location'], 'competitor_name_title_loc_idx');
                $table->index(['posted_at', 'is_active'], 'competitor_posted_active_idx');
                $table->index('source_platform', 'competitor_source_idx');
            });
        }

        // Trending Skills (tracking skill demand over time)
        if (!Schema::hasTable('trending_skills')) {
            Schema::create('trending_skills', function (Blueprint $table) {
                $table->id();
                $table->string('skill_name');
                $table->string('skill_category')->comment('technical, soft, certification, etc.');
                $table->string('job_category')->comment('nursing, physician, etc.');
                $table->string('location')->nullable();
                $table->date('period_month');
                $table->integer('mention_count')->comment('times mentioned in job postings');
                $table->integer('job_postings_count')->comment('total jobs mentioning this skill');
                $table->decimal('growth_rate', 5, 2)->nullable()->comment('month-over-month growth %');
                $table->decimal('demand_score', 5, 2)->comment('relative demand score');
                $table->integer('avg_salary_premium', 8)->nullable()->comment('salary premium for this skill');
                $table->timestamps();

                $table->index(['skill_name', 'job_category', 'period_month'], 'trending_skills_name_cat_period_idx');
                $table->index(['job_category', 'period_month', 'demand_score'], 'trending_skills_cat_period_score_idx');
                $table->index('period_month', 'trending_skills_period_idx');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // CUSTOM REPORT BUILDER
        // ═══════════════════════════════════════════════════════════

        // Custom Reports (user-defined reports)
        if (!Schema::hasTable('custom_reports')) {
            Schema::create('custom_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('report_type')->comment('hiring_analytics, market_intelligence, custom_query');
                $table->json('data_sources')->comment('Tables/metrics to include');
                $table->json('filters')->nullable()->comment('Date ranges, departments, etc.');
                $table->json('grouping')->nullable()->comment('Group by dimensions');
                $table->json('metrics')->comment('Metrics to calculate');
                $table->json('visualization_config')->nullable()->comment('Chart types, colors, etc.');
                $table->string('schedule_frequency')->nullable()->comment('daily, weekly, monthly, quarterly');
                $table->json('schedule_config')->nullable()->comment('Day of week, time, recipients');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_public')->default(false)->comment('Shareable with team');
                $table->timestamp('last_generated_at')->nullable();
                $table->timestamps();

                $table->index(['employer_user_id', 'report_type', 'is_active'], 'custom_reports_employer_type_active_idx');
                $table->index('schedule_frequency', 'custom_reports_schedule_idx');
            });
        }

        // Report Executions (tracking report runs)
        if (!Schema::hasTable('report_executions')) {
            Schema::create('report_executions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('custom_report_id')->constrained('custom_reports')->onDelete('cascade');
                $table->foreignId('executed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('trigger_type', ['manual', 'scheduled', 'api'])->default('manual');
                $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
                $table->json('parameters')->nullable()->comment('Runtime parameters');
                $table->json('results')->nullable()->comment('Report data/results');
                $table->string('export_format')->nullable()->comment('pdf, excel, csv');
                $table->string('file_path')->nullable()->comment('Path to generated file');
                $table->integer('execution_time_ms')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['custom_report_id', 'status', 'created_at'], 'report_exec_report_status_created_idx');
                $table->index(['trigger_type', 'status'], 'report_exec_trigger_status_idx');
            });
        }

        // Report Shares (sharing reports with team members)
        if (!Schema::hasTable('report_shares')) {
            Schema::create('report_shares', function (Blueprint $table) {
                $table->id();
                $table->foreignId('custom_report_id')->constrained('custom_reports')->onDelete('cascade');
                $table->foreignId('shared_by_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('shared_with_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('share_token')->unique()->nullable()->comment('Public share token');
                $table->enum('permission_level', ['view', 'edit', 'admin'])->default('view');
                $table->boolean('can_export')->default(true);
                $table->boolean('can_schedule')->default(false);
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['custom_report_id', 'shared_with_user_id'], 'report_shares_report_user_idx');
                $table->index('share_token', 'report_shares_token_idx');
            });
        }

        // Industry Benchmarks (external benchmark data)
        if (!Schema::hasTable('industry_benchmarks')) {
            Schema::create('industry_benchmarks', function (Blueprint $table) {
                $table->id();
                $table->string('industry')->comment('healthcare, hospital, clinic, etc.');
                $table->string('metric_name')->comment('time_to_hire, cost_per_hire, turnover_rate, etc.');
                $table->string('job_category')->nullable();
                $table->string('organization_size')->comment('small, medium, large, enterprise');
                $table->string('location_type')->comment('national, regional, state, city');
                $table->string('location_value')->nullable();
                $table->decimal('benchmark_value', 10, 2);
                $table->decimal('percentile_25', 10, 2)->nullable();
                $table->decimal('percentile_50', 10, 2)->nullable();
                $table->decimal('percentile_75', 10, 2)->nullable();
                $table->decimal('percentile_90', 10, 2)->nullable();
                $table->string('unit')->comment('days, dollars, percentage, etc.');
                $table->integer('sample_size');
                $table->string('data_source');
                $table->date('period_start');
                $table->date('period_end');
                $table->timestamps();

                $table->index(['industry', 'metric_name', 'organization_size'], 'industry_bench_ind_metric_size_idx');
                $table->index(['job_category', 'location_type', 'location_value'], 'industry_bench_job_loc_idx');
                $table->index(['period_start', 'period_end'], 'industry_bench_period_idx');
            });
        }

        // Report Templates (pre-built report templates)
        if (!Schema::hasTable('report_templates')) {
            Schema::create('report_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description');
                $table->string('category')->comment('hiring, market, performance, compliance');
                $table->json('template_config')->comment('Pre-configured report structure');
                $table->json('required_data')->comment('Data requirements');
                $table->string('preview_image')->nullable();
                $table->boolean('is_premium')->default(false);
                $table->integer('usage_count')->default(0);
                $table->decimal('rating', 3, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['category', 'is_active'], 'report_templates_category_active_idx');
                $table->index('is_premium', 'report_templates_premium_idx');
            });
        }

        // Analytics Cache (caching expensive analytics queries)
        if (!Schema::hasTable('analytics_cache')) {
            Schema::create('analytics_cache', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_user_id')->constrained('users')->onDelete('cascade');
                $table->string('cache_key')->unique();
                $table->string('query_type')->comment('dashboard, report, benchmark');
                $table->json('query_parameters');
                $table->longText('cached_data');
                $table->timestamp('expires_at');
                $table->integer('hit_count')->default(0);
                $table->timestamps();

                $table->index(['employer_user_id', 'query_type'], 'analytics_cache_employer_type_idx');
                $table->index(['cache_key', 'expires_at'], 'analytics_cache_key_expires_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_cache');
        Schema::dropIfExists('report_templates');
        Schema::dropIfExists('industry_benchmarks');
        Schema::dropIfExists('report_shares');
        Schema::dropIfExists('report_executions');
        Schema::dropIfExists('custom_reports');
        Schema::dropIfExists('trending_skills');
        Schema::dropIfExists('competitor_intelligence');
        Schema::dropIfExists('supply_demand_metrics');
        Schema::dropIfExists('salary_benchmarks');
        Schema::dropIfExists('hiring_funnel_events');
        Schema::dropIfExists('source_attributions');
        Schema::dropIfExists('hiring_metrics');
    }
};