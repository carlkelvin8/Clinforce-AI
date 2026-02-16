<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employer_profiles')) {
            return;
        }

        Schema::table('employer_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('employer_profiles', 'zip_code')) {
                $table->string('zip_code', 20)->nullable()->after('city');
            }
            if (!Schema::hasColumn('employer_profiles', 'tax_id')) {
                $table->string('tax_id', 50)->nullable()->after('zip_code');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employer_profiles')) {
            return;
        }

        Schema::table('employer_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('employer_profiles', 'tax_id')) {
                $table->dropColumn('tax_id');
            }
            if (Schema::hasColumn('employer_profiles', 'zip_code')) {
                $table->dropColumn('zip_code');
            }
        });
    }
};

