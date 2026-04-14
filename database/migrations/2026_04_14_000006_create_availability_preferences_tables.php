<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User availability and shift preferences
        Schema::create('user_availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique()->index();
            
            // General availability
            $table->json('available_days')->nullable(); // ['monday', 'tuesday', ...]
            $table->json('available_hours')->nullable(); // {monday: {start: '08:00', end: '20:00'}, ...}
            $table->string('availability_type', 50)->default('flexible'); // 'flexible', 'fixed', 'weekends_only', 'nights_only'
            
            // Shift preferences
            $table->json('preferred_shifts')->nullable(); // ['day', 'night', 'rotating']
            $table->string('shift_length_pref', 20)->nullable(); // '8hr', '12hr', 'any'
            $table->boolean('willing_to_work_weekends')->default(true);
            $table->boolean('willing_to_work_holidays')->default(false);
            $table->boolean('willing_to_do_overtime')->default(true);
            $table->boolean('willing_to_be_on_call')->default(false);
            
            // Start date and notice
            $table->string('notice_period', 20)->nullable(); // 'immediate', '2weeks', '1month', 'custom'
            $table->date('available_from')->nullable();
            $table->integer('notice_days')->nullable(); // Custom notice period in days
            
            // Travel and location preferences
            $table->boolean('willing_to_relocate')->default(false);
            $table->boolean('willing_to_travel')->default(false);
            $table->boolean('interested_in_travel_contracts')->default(false);
            $table->json('preferred_locations')->nullable(); // Cities/states willing to work
            $table->integer('max_travel_distance_miles')->nullable(); // Max commute distance
            $table->boolean('willing_to_float')->default(false); // Float between units/departments
            
            // Contract preferences
            $table->json('preferred_contract_types')->nullable(); // ['full-time', 'part-time', 'contract', 'per-diem']
            $table->decimal('min_salary_expected', 10, 2)->nullable();
            $table->string('salary_currency', 10)->default('USD');
            
            // Instant match settings
            $table->boolean('instant_match_enabled')->default(true);
            $table->integer('instant_match_threshold')->default(70); // Minimum match score for alerts
            $table->boolean('receive_email_alerts')->default(true);
            $table->boolean('receive_push_alerts')->default(true);
            
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Availability blacklist (times/dates not available)
        Schema::create('user_unavailable_times', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->date('date')->nullable(); // Specific date unavailable
            $table->string('day_of_week')->nullable(); // Recurring day unavailable
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('reason', 100)->nullable(); // 'vacation', 'education', 'personal', etc.
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Match alerts based on availability
        Schema::create('availability_job_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('job_id')->index();
            $table->decimal('match_score', 5, 2);
            $table->json('match_factors')->nullable(); // Why it matched
            $table->boolean('alert_sent')->default(false);
            $table->dateTime('alert_sent_at')->nullable();
            $table->string('user_action', 20)->nullable(); // 'saved', 'applied', 'dismissed', null
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_job_matches');
        Schema::dropIfExists('user_unavailable_times');
        Schema::dropIfExists('user_availability');
    }
};
