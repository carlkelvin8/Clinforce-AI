<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('access_logs')) {
            Schema::create('access_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('candidate_user_id')->constrained('users')->onDelete('cascade');
                $table->string('action', 50); // view_candidate, download_resume, blocked_attempt
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();
                
                $table->index(['employer_id', 'created_at']);
                $table->index(['candidate_user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
