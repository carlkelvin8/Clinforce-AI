<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('job_applications', 'resume_document_id')) {
                $table->unsignedBigInteger('resume_document_id')->nullable()->after('cover_letter');
            }
            if (!Schema::hasColumn('job_applications', 'cover_letter_document_id')) {
                $table->unsignedBigInteger('cover_letter_document_id')->nullable()->after('resume_document_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            if (Schema::hasColumn('job_applications', 'resume_document_id')) {
                $table->dropColumn('resume_document_id');
            }
            if (Schema::hasColumn('job_applications', 'cover_letter_document_id')) {
                $table->dropColumn('cover_letter_document_id');
            }
        });
    }
};
