<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('admin_user_notes')) {
            Schema::create('admin_user_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->text('note');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('feature_flags')) {
            Schema::create('feature_flags', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('enabled')->default(false);
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('webhook_logs')) {
            Schema::create('webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->string('source', 50)->index(); // stripe, zoom
                $table->string('event_type', 100)->nullable();
                $table->string('status', 20)->default('received'); // received, processed, failed
                $table->json('payload')->nullable();
                $table->text('error')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_user_notes');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('webhook_logs');
    }
};
