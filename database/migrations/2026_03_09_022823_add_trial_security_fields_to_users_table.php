<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'trial_activated_ip')) {
                $table->string('trial_activated_ip', 45)->nullable()->after('trial_consumed');
            }
            if (!Schema::hasColumn('users', 'trial_activated_user_agent')) {
                $table->string('trial_activated_user_agent', 255)->nullable()->after('trial_activated_ip');
            }
            if (!Schema::hasColumn('users', 'trial_device_hash')) {
                $table->char('trial_device_hash', 64)->nullable()->after('trial_activated_user_agent');
                $table->index('trial_device_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'trial_device_hash')) {
                $table->dropIndex(['trial_device_hash']);
            }
            $toDrop = [];
            foreach (['trial_activated_ip', 'trial_activated_user_agent', 'trial_device_hash'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $toDrop[] = $col;
                }
            }
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
