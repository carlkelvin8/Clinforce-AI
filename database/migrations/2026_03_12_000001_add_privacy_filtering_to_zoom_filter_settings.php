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
            $table->boolean('privacy_filtering')->default(true)->after('lock_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zoom_filter_settings', function (Blueprint $table) {
            $table->dropColumn('privacy_filtering');
        });
    }
};
