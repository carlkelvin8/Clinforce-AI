<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('application_notes')) {
            Schema::create('application_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('application_id');
                $table->unsignedBigInteger('user_id');
                $table->text('content');
                $table->timestamps();
                $table->foreign('application_id')->references('id')->on('job_applications')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('interview_feedback')) {
            Schema::create('interview_feedback', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('interview_id');
                $table->unsignedBigInteger('submitted_by_user_id');
                $table->unsignedTinyInteger('rating');
                $table->text('notes')->nullable();
                $table->unsignedTinyInteger('technical_score')->nullable();
                $table->unsignedTinyInteger('communication_score')->nullable();
                $table->unsignedTinyInteger('culture_fit_score')->nullable();
                $table->string('recommendation')->nullable();
                $table->timestamps();
                $table->foreign('interview_id')->references('id')->on('interviews')->onDelete('cascade');
                $table->foreign('submitted_by_user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('job_alerts')) {
            Schema::create('job_alerts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('keywords')->nullable();
                $table->string('location')->nullable();
                $table->string('employment_type')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('job_shares')) {
            Schema::create('job_shares', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('job_id');
                $table->unsignedBigInteger('shared_by_user_id')->nullable();
                $table->string('share_token', 32)->unique();
                $table->unsignedInteger('clicks')->default(0);
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            });
        }

        Schema::table('job_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('job_applications', 'share_token')) {
                $table->string('share_token', 32)->nullable()->after('submitted_at');
            }
        });

        Schema::table('applicant_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('applicant_profiles', 'portfolio_links')) {
                $table->json('portfolio_links')->nullable()->after('education');
            }
        });

        Schema::table('employer_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('employer_profiles', 'description')) {
                $table->text('description')->nullable()->after('website_url');
            }
            if (!Schema::hasColumn('employer_profiles', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('description');
            }
        });

        if (!Schema::hasTable('two_factor_auth')) {
            Schema::create('two_factor_auth', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->string('secret');
                $table->json('backup_codes')->nullable();
                $table->timestamp('enabled_at')->nullable();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            if (Schema::hasColumn('job_applications', 'share_token')) {
                $table->dropColumn('share_token');
            }
        });
        Schema::table('applicant_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('applicant_profiles', 'portfolio_links')) {
                $table->dropColumn('portfolio_links');
            }
        });
        Schema::table('employer_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('employer_profiles', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('employer_profiles', 'slug')) {
                $table->dropColumn('slug');
            }
        });
        Schema::dropIfExists('two_factor_auth');
        Schema::dropIfExists('job_shares');
        Schema::dropIfExists('job_alerts');
        Schema::dropIfExists('interview_feedback');
        Schema::dropIfExists('application_notes');
    }
};
