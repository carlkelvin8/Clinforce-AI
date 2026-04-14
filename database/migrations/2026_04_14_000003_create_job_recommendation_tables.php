<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Job swipe tracking (Tinder-style)
        Schema::create('job_swipes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('job_id')->index();
            $table->string('action', 10); // 'right' (saved/interested), 'left' (rejected), 'super' (high interest)
            $table->text('reason')->nullable(); // Why they swiped this way
            $table->decimal('match_score', 5, 2)->nullable(); // AI match score at time of swipe
            $table->timestamps();
            
            $table->unique(['user_id', 'job_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
        });

        // Job recommendations with scoring
        Schema::create('job_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('job_id')->index();
            $table->decimal('match_score', 5, 2); // 0-100 match percentage
            $table->json('match_reasons')->nullable(); // Why this job matched
            $table->string('source', 50)->default('algorithm'); // 'algorithm', 'ai', 'similar_to', 'trending'
            $table->boolean('is_seen')->default(false);
            $table->boolean('is_interacted')->default(false);
            $table->dateTime('expires_at')->nullable(); // Recommendations expire after 30 days
            $table->timestamps();
            
            $table->unique(['user_id', 'job_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
        });

        // Trending jobs tracking
        Schema::create('trending_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id')->index();
            $table->string('category', 100)->nullable()->index(); // Specialty/category
            $table->string('region', 100)->nullable()->index(); // Geographic region
            $table->integer('view_count')->default(0);
            $table->integer('application_count')->default(0);
            $table->integer('save_count')->default(0);
            $table->decimal('trend_score', 8, 2)->default(0); // Weighted trend score
            $table->dateTime('trending_starts_at')->nullable();
            $table->dateTime('trending_ends_at')->nullable();
            $table->timestamps();
            
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_swipes');
        Schema::dropIfExists('job_recommendations');
        Schema::dropIfExists('trending_jobs');
    }
};
