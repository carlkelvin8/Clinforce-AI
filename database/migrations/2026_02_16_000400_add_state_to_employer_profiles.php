<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employer_profiles') && !Schema::hasColumn('employer_profiles', 'state')) {
            Schema::table('employer_profiles', function (Blueprint $table) {
                $table->string('state', 120)->nullable()->after('country_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employer_profiles') && Schema::hasColumn('employer_profiles', 'state')) {
            Schema::table('employer_profiles', function (Blueprint $table) {
                $table->dropColumn('state');
            });
        }
    }
};

