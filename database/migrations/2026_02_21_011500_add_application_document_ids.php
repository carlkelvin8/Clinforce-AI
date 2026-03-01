<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('resume_document_id')->nullable()->after('cover_letter');
            $table->unsignedBigInteger('cover_letter_document_id')->nullable()->after('resume_document_id');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn('resume_document_id');
            $table->dropColumn('cover_letter_document_id');
        });
    }
};
