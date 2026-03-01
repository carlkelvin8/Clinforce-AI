<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Applicant Profiles
        if (!Schema::hasTable('applicant_profiles')) {
            Schema::create('applicant_profiles', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->primary();
                $table->string('first_name', 100)->nullable();
                $table->string('last_name', 100)->nullable();
                $table->string('headline', 255)->nullable();
                $table->text('summary')->nullable();
                $table->unsignedSmallInteger('years_experience')->nullable();
                $table->string('country_code', 2)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('public_display_name', 200)->nullable();
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Employer Profiles
        if (!Schema::hasTable('employer_profiles')) {
            Schema::create('employer_profiles', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->primary();
                $table->string('organization_name', 200)->nullable();
                $table->string('organization_type', 50)->nullable();
                $table->text('description')->nullable();
                $table->string('website', 255)->nullable();
                $table->string('country_code', 2)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('address', 255)->nullable();
                $table->string('zip', 20)->nullable();
                $table->string('tax_id', 50)->nullable();
                $table->string('logo', 255)->nullable();
                $table->string('billing_currency_code', 3)->nullable();
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Agency Profiles
        if (!Schema::hasTable('agency_profiles')) {
            Schema::create('agency_profiles', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->primary();
                $table->string('agency_name', 200)->nullable();
                $table->text('description')->nullable();
                $table->string('website', 255)->nullable();
                $table->string('country_code', 2)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('logo', 255)->nullable();
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_profiles');
        Schema::dropIfExists('employer_profiles');
        Schema::dropIfExists('applicant_profiles');
    }
};
