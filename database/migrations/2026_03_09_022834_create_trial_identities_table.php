<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_identities', function (Blueprint $table) {
            $table->id();
            $table->string('identity_type', 20);
            $table->char('identity_hash', 64);
            $table->foreignId('first_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('trial_consumed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->unique(['identity_type', 'identity_hash']);
            $table->index('identity_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_identities');
    }
};
