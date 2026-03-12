<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employer_profiles')) {
            return;
        }

        // First, check if business_name column exists
        if (!Schema::hasColumn('employer_profiles', 'business_name')) {
            // If it doesn't exist, check if organization_name exists and rename it
            if (Schema::hasColumn('employer_profiles', 'organization_name')) {
                Schema::table('employer_profiles', function (Blueprint $table) {
                    $table->renameColumn('organization_name', 'business_name');
                });
            } else {
                // If neither exists, create business_name
                Schema::table('employer_profiles', function (Blueprint $table) {
                    $table->string('business_name', 200)->nullable()->after('logo');
                });
            }
        }

        // Make sure business_name is nullable
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE employer_profiles MODIFY business_name VARCHAR(200) NULL");
        }

        // Also add business_type if it doesn't exist
        if (!Schema::hasColumn('employer_profiles', 'business_type')) {
            Schema::table('employer_profiles', function (Blueprint $table) {
                $table->string('business_type', 50)->nullable()->after('business_name');
            });
        }

        // Rename organization_type to business_type if it exists
        if (Schema::hasColumn('employer_profiles', 'organization_type') && !Schema::hasColumn('employer_profiles', 'business_type')) {
            Schema::table('employer_profiles', function (Blueprint $table) {
                $table->renameColumn('organization_type', 'business_type');
            });
        }

        // Add address_line if it doesn't exist
        if (!Schema::hasColumn('employer_profiles', 'address_line')) {
            if (Schema::hasColumn('employer_profiles', 'address')) {
                Schema::table('employer_profiles', function (Blueprint $table) {
                    $table->renameColumn('address', 'address_line');
                });
            } else {
                Schema::table('employer_profiles', function (Blueprint $table) {
                    $table->string('address_line', 255)->nullable()->after('city');
                });
            }
        }

        // Add website_url if it doesn't exist
        if (!Schema::hasColumn('employer_profiles', 'website_url')) {
            if (Schema::hasColumn('employer_profiles', 'website')) {
                Schema::table('employer_profiles', function (Blueprint $table) {
                    $table->renameColumn('website', 'website_url');
                });
            } else {
                Schema::table('employer_profiles', function (Blueprint $table) {
                    $table->string('website_url', 255)->nullable()->after('address_line');
                });
            }
        }

        // Add verification fields if they don't exist
        if (!Schema::hasColumn('employer_profiles', 'verification_status')) {
            Schema::table('employer_profiles', function (Blueprint $table) {
                $table->string('verification_status', 20)->default('pending')->after('website_url');
                $table->timestamp('verified_at')->nullable()->after('verification_status');
                $table->text('rejected_reason')->nullable()->after('verified_at');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('employer_profiles')) {
            return;
        }

        // Reverse the changes
        if (Schema::hasColumn('employer_profiles', 'rejected_reason')) {
            Schema::table('employer_profiles', function (Blueprint $table) {
                $table->dropColumn(['verification_status', 'verified_at', 'rejected_reason']);
            });
        }
    }
};
