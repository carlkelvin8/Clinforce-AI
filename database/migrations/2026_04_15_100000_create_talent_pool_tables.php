<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Talent pools - collections of candidates
        Schema::create('talent_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('pool_type')->default('general'); // general, future_role, passive, high_potential
            $table->json('target_skills')->nullable();
            $table->json('target_experience')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['employer_id', 'is_active']);
        });

        // Candidates in talent pools
        Schema::create('talent_pool_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_pool_id')->constrained()->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('active'); // active, contacted, engaged, hired, removed
            $table->text('notes')->nullable();
            $table->integer('priority')->default(3); // 1=high, 2=medium, 3=low
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_contact_at')->nullable();
            $table->timestamps();
            
            $table->unique(['talent_pool_id', 'candidate_id']);
            $table->index(['talent_pool_id', 'status']);
            $table->index('next_contact_at');
        });

        // Tags for categorizing candidates
        Schema::create('talent_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('color')->default('#3B82F6');
            $table->string('category')->nullable(); // skill, experience, location, industry
            $table->timestamps();
            
            $table->unique(['employer_id', 'name']);
        });

        // Candidate tags (many-to-many)
        Schema::create('candidate_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('talent_tag_id')->constrained()->onDelete('cascade');
            $table->foreignId('tagged_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['candidate_id', 'talent_tag_id']);
        });

        // Nurture campaigns
        Schema::create('nurture_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, active, paused, completed
            $table->string('trigger_type')->default('manual'); // manual, pool_added, time_based
            $table->integer('frequency_days')->default(30); // Contact every X days
            $table->integer('max_touches')->default(5); // Maximum number of contacts
            $table->json('target_pools')->nullable(); // Array of talent_pool_ids
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            
            $table->index(['employer_id', 'status']);
        });

        // Campaign messages/steps
        Schema::create('nurture_campaign_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nurture_campaign_id')->constrained()->onDelete('cascade');
            $table->integer('step_order')->default(1);
            $table->integer('delay_days')->default(0); // Days after previous step
            $table->string('message_type')->default('email'); // email, sms, notification
            $table->string('subject')->nullable();
            $table->text('message_body');
            $table->json('variables')->nullable(); // Available merge variables
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('nurture_campaign_id');
        });

        // Campaign enrollments
        Schema::create('nurture_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nurture_campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('active'); // active, completed, unsubscribed, bounced
            $table->integer('current_step')->default(0);
            $table->integer('touches_sent')->default(0);
            $table->timestamp('enrolled_at');
            $table->timestamp('next_send_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['nurture_campaign_id', 'candidate_id']);
            $table->index(['status', 'next_send_at']);
        });

        // Campaign message logs
        Schema::create('nurture_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nurture_enrollment_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_step_id')->constrained('nurture_campaign_steps')->onDelete('cascade');
            $table->string('message_type');
            $table->string('status')->default('pending'); // pending, sent, delivered, opened, clicked, failed
            $table->text('message_content')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['nurture_enrollment_id', 'status']);
        });

        // Talent pipeline analytics
        Schema::create('talent_pipeline_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('talent_pool_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('metric_date');
            $table->integer('total_candidates')->default(0);
            $table->integer('active_candidates')->default(0);
            $table->integer('contacted_candidates')->default(0);
            $table->integer('engaged_candidates')->default(0);
            $table->integer('hired_candidates')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['employer_id', 'talent_pool_id', 'metric_date']);
            $table->index('metric_date');
        });

        // Candidate interactions/touchpoints
        Schema::create('candidate_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Who interacted
            $table->string('interaction_type'); // email, call, message, meeting, note
            $table->string('subject')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('interaction_date');
            $table->timestamps();
            
            $table->index(['candidate_id', 'employer_id']);
            $table->index('interaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_interactions');
        Schema::dropIfExists('talent_pipeline_metrics');
        Schema::dropIfExists('nurture_message_logs');
        Schema::dropIfExists('nurture_enrollments');
        Schema::dropIfExists('nurture_campaign_steps');
        Schema::dropIfExists('nurture_campaigns');
        Schema::dropIfExists('candidate_tags');
        Schema::dropIfExists('talent_tags');
        Schema::dropIfExists('talent_pool_candidates');
        Schema::dropIfExists('talent_pools');
    }
};
