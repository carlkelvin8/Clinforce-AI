<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('role', 20)->nullable();
                $table->string('category', 50)->index();
                $table->string('type', 60)->index();
                $table->string('title', 190);
                $table->text('body')->nullable();
                $table->json('data')->nullable();
                $table->string('url', 255)->nullable();
                $table->boolean('is_read')->default(false)->index();
                $table->string('batch_key', 120)->nullable()->index();
                $table->unsignedInteger('batch_count')->default(1);
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->boolean('email_enabled')->default(true);
                $table->boolean('in_app_enabled')->default(true);
                $table->string('frequency', 20)->default('immediate');
                $table->json('category_toggles')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};
