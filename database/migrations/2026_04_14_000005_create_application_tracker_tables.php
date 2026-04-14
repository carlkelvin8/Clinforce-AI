<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Application timeline events
        Schema::create('application_timeline', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id')->index();
            $table->unsignedBigInteger('user_id')->nullable(); // User who triggered event
            $table->string('event_type', 50)->index(); // 'submitted', 'viewed', 'screened', 'interviewed', 'offered', 'rejected', 'hired'
            $table->string('status_from', 50)->nullable();
            $table->string('status_to', 50)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional event data
            $table->boolean('is_visible_to_candidate')->default(true);
            $table->timestamps();
            
            $table->foreign('application_id')->references('id')->on('job_applications')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Application read receipts
        Schema::create('application_read_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id')->index();
            $table->unsignedBigInteger('viewer_user_id')->nullable(); // Employer who viewed
            $table->dateTime('viewed_at');
            $table->string('view_type', 50)->default('application'); // 'application', 'resume', 'profile'
            $table->integer('view_duration_seconds')->nullable(); // How long they viewed
            $table->timestamps();
            
            $table->foreign('application_id')->references('id')->on('job_applications')->onDelete('cascade');
            $table->foreign('viewer_user_id')->references('id')->on('users')->onDelete('set null');
        });

        // ETA predictions for applications
        Schema::create('application_eta_predictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id')->index();
            $table->string('stage', 50); // Current stage
            $table->dateTime('predicted_response_date')->nullable();
            $table->decimal('confidence', 5, 2)->nullable(); // Confidence in prediction
            $table->json('factors')->nullable(); // What influenced prediction
            $table->boolean('is_accurate')->nullable(); // Was prediction accurate?
            $table->dateTime('actual_response_date')->nullable();
            $table->timestamps();
            
            $table->foreign('application_id')->references('id')->on('job_applications')->onDelete('cascade');
        });

        // Feedback requests
        Schema::create('application_feedback_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id')->index();
            $table->unsignedBigInteger('requested_by_user_id')->index(); // Candidate requesting
            $table->unsignedBigInteger('requested_to_user_id')->nullable(); // Employer being asked
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending'); // pending, sent, responded, declined
            $table->text('feedback_response')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->timestamps();
            
            $table->foreign('application_id')->references('id')->on('job_applications')->onDelete('cascade');
            $table->foreign('requested_by_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('requested_to_user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Application pipeline visualization data
        Schema::create('application_pipelines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id')->unique()->index();
            $table->json('stages')->nullable(); // Array of pipeline stages
            $table->string('current_stage', 50)->nullable();
            $table->integer('stage_progress')->default(0); // 0-100% progress
            $table->dateTime('stage_started_at')->nullable();
            $table->dateTime('estimated_stage_end')->nullable();
            $table->timestamps();
            
            $table->foreign('application_id')->references('id')->on('job_applications')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_pipelines');
        Schema::dropIfExists('application_feedback_requests');
        Schema::dropIfExists('application_eta_predictions');
        Schema::dropIfExists('application_read_receipts');
        Schema::dropIfExists('application_timeline');
    }
};
