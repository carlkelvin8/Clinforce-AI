<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employer_blacklist', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employer_user_id')->index();
            $table->unsignedBigInteger('candidate_user_id')->index();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->unique(['employer_user_id', 'candidate_user_id']);
        });

        // Add view_count to jobs
        Schema::table('jobs', function (Blueprint $table) {
            $table->unsignedInteger('view_count')->default(0)->after('archived_at');
        });

        // Add no_show flag to interviews
        Schema::table('interviews', function (Blueprint $table) {
            $table->boolean('no_show')->default(false)->after('cancel_reason');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_blacklist');
        Schema::table('jobs', fn($t) => $t->dropColumn('view_count'));
        Schema::table('interviews', fn($t) => $t->dropColumn('no_show'));
    }
};
