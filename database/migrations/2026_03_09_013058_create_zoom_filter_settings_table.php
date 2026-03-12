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
        Schema::create('zoom_filter_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('filter_emails')->default(true);
            $table->boolean('filter_domains')->default(false);
            $table->json('blocked_domains')->nullable(); // e.g. ["gmail.com", "yahoo.com"]
            $table->json('custom_patterns')->nullable(); // regex patterns
            $table->string('replacement_text')->default('Participant [Filtered]');
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_filter_settings');
    }
};
