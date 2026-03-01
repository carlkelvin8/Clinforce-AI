<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_access_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employer_user_id')->index();
            $table->unsignedBigInteger('applicant_user_id')->index();
            $table->unsignedBigInteger('application_id')->nullable()->index();
            $table->string('access_type', 50)->default('per_applicant'); // per_applicant, document_pack
            $table->integer('amount_cents');
            $table->string('currency_code', 3)->default('USD');
            $table->string('status', 50)->default('pending'); // pending, paid, failed, refunded
            $table->string('provider', 50)->default('stripe');
            $table->string('provider_ref')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // optional expiry
            $table->timestamps();

            $table->foreign('employer_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('applicant_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('application_id')->references('id')->on('job_applications')->onDelete('set null');
            
            // Prevent duplicate payments for same employer-applicant pair
            $table->unique(['employer_user_id', 'applicant_user_id', 'status'], 'unique_active_access');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_access_payments');
    }
};
