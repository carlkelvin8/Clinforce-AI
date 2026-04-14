<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs_table', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs_table', 'owner_type')) {
                $table->string('owner_type', 20)->default('employer')->after('id');
            }
            if (!Schema::hasColumn('jobs_table', 'owner_user_id')) {
                $table->unsignedBigInteger('owner_user_id')->nullable()->after('owner_type');
                $table->foreign('owner_user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs_table', function (Blueprint $table) {
            if (Schema::hasColumn('jobs_table', 'owner_user_id')) {
                $table->dropForeign(['owner_user_id']);
                $table->dropColumn('owner_user_id');
            }
            if (Schema::hasColumn('jobs_table', 'owner_type')) {
                $table->dropColumn('owner_type');
            }
        });
    }
};
