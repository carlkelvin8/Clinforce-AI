<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('zoom_filter_settings', function (Blueprint $table) {
            $table->boolean('monitor_audio')->default(false)->after('filter_domains');
            $table->boolean('lock_name')->default(true)->after('monitor_audio'); // Prevent renaming back
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zoom_filter_settings', function (Blueprint $table) {
            $table->dropColumn(['monitor_audio', 'lock_name']);
        });
    }
};
