<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Ensure id is AUTO_INCREMENT — it was created without it
            DB::statement('ALTER TABLE audit_logs MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
    }

    public function down(): void
    {
        // Intentionally not reversing — removing AUTO_INCREMENT would break inserts
    }
};
