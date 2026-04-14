<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_templates', function (Blueprint $table) {
            // Template categorization
            $table->string('category', 100)->nullable()->after('name'); // e.g., 'nursing', 'allied_health', 'physician'
            $table->string('role_type', 100)->nullable()->after('category'); // e.g., 'ER Nurse', 'Travel RN', 'ICU Nurse'
            $table->json('tags')->nullable()->after('role_type'); // Searchable tags
            
            // AI-generated metadata
            $table->boolean('is_ai_generated')->default(false)->after('salary_currency');
            $table->string('ai_model_used', 50)->nullable()->after('is_ai_generated');
            $table->json('ai_suggestions')->nullable()->after('ai_model_used'); // AI best-practice suggestions
            
            // Compliance fields
            $table->json('required_certifications')->nullable()->after('ai_suggestions'); // ['BSN', 'RN', 'ACLS']
            $table->json('required_licenses')->nullable()->after('required_certifications'); // State licenses
            $table->string('shift_type', 100)->nullable()->after('required_licenses'); // 'day', 'night', 'rotating', '12hr'
            $table->json('shift_details')->nullable()->after('shift_type'); // Detailed shift info
            $table->string('experience_level', 50)->nullable()->after('shift_details'); // 'entry', 'mid', 'senior', 'expert'
            $table->integer('min_experience_years')->nullable()->after('experience_level');
            $table->json('benefits')->nullable()->after('min_experience_years'); // Benefits package
            $table->json('compliance_checklist')->nullable()->after('benefits'); // Compliance checklist JSON
            
            // A/B Testing support
            $table->unsignedBigInteger('ab_test_id')->nullable()->after('compliance_checklist')->index();
            $table->string('ab_variant', 10)->nullable()->after('ab_test_id'); // 'A', 'B', 'C', etc.
            $table->integer('views_count')->default(0)->after('ab_variant');
            $table->integer('conversions_count')->default(0)->after('views_count');
            $table->dateTime('ab_test_started_at')->nullable()->after('conversions_count');
            $table->dateTime('ab_test_ended_at')->nullable()->after('ab_test_started_at');
            $table->boolean('is_ab_winner')->nullable()->after('ab_test_ended_at');
            
            // Template status and usage
            $table->boolean('is_system_template')->default(false)->after('is_ab_winner'); // Pre-built system templates
            $table->integer('usage_count')->default(0)->after('is_system_template');
            $table->decimal('avg_conversion_rate', 5, 2)->nullable()->after('usage_count');
            
            // Add indexes for common queries
            $table->index('category');
            $table->index('role_type');
            $table->index('is_system_template');
        });
    }

    public function down(): void
    {
        Schema::table('job_templates', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['role_type']);
            $table->dropIndex(['is_system_template']);
            
            $table->dropColumn([
                'category',
                'role_type',
                'tags',
                'is_ai_generated',
                'ai_model_used',
                'ai_suggestions',
                'required_certifications',
                'required_licenses',
                'shift_type',
                'shift_details',
                'experience_level',
                'min_experience_years',
                'benefits',
                'compliance_checklist',
                'ab_test_id',
                'ab_variant',
                'views_count',
                'conversions_count',
                'ab_test_started_at',
                'ab_test_ended_at',
                'is_ab_winner',
                'is_system_template',
                'usage_count',
                'avg_conversion_rate',
            ]);
        });
    }
};
