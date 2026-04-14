<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify employment_type to include new combined values
        DB::statement("ALTER TABLE jobs_table MODIFY COLUMN employment_type VARCHAR(50) NOT NULL DEFAULT 'full_time'");
    }

    public function down(): void
    {
        // Revert to original constraint (data may be truncated if new values exist)
        DB::statement("ALTER TABLE jobs_table MODIFY COLUMN employment_type VARCHAR(50) NOT NULL DEFAULT 'full_time'");
    }
};
