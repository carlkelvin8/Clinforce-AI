<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('data_retention_days')->nullable()->after('address_line')
                ->comment('Auto-delete rejected applications after this many days. NULL = use platform default (90).');
        });
    }

    public function down(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->dropColumn('data_retention_days');
        });
    }
};
