<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_user_id')->index();
            $table->string('name', 200); // Test name
            $table->string('description', 500)->nullable();
            $table->string('base_template_id')->nullable(); // Original template
            $table->json('variant_ids')->nullable(); // Array of variant template IDs
            $table->string('test_type', 50)->default('conversion'); // 'conversion', 'clicks', 'applications'
            $table->integer('target_sample_size')->nullable(); // Statistical significance target
            $table->decimal('confidence_level', 5, 2)->default(95.00); // 95% confidence
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('status', 20)->default('draft'); // 'draft', 'running', 'completed', 'cancelled'
            $table->json('results')->nullable(); // Test results JSON
            $table->timestamps();
            
            $table->foreign('owner_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_ab_tests');
    }
};
