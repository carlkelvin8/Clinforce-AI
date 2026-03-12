<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
            return;
        }

        Schema::table('notification_preferences', function (Blueprint $table) {
            if (!Schema::hasColumn('notification_preferences', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->unique('user_id');
            }
            if (!Schema::hasColumn('notification_preferences', 'email_enabled')) {
                $table->boolean('email_enabled')->default(true)->after('user_id');
            }
            if (!Schema::hasColumn('notification_preferences', 'in_app_enabled')) {
                $table->boolean('in_app_enabled')->default(true)->after('email_enabled');
            }
            if (!Schema::hasColumn('notification_preferences', 'frequency')) {
                $table->string('frequency', 20)->default('immediate')->after('in_app_enabled');
            }
            if (!Schema::hasColumn('notification_preferences', 'category_toggles')) {
                $table->json('category_toggles')->nullable()->after('frequency');
            }
        });
    }

    public function down(): void
    {
        // Intentionally no-op: removing these columns can destroy user preferences.
    }
};
