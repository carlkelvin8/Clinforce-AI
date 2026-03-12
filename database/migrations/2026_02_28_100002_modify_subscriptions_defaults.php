<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE subscriptions MODIFY currency_code VARCHAR(3) NOT NULL DEFAULT 'USD'");
            DB::statement("ALTER TABLE subscriptions MODIFY amount_cents INT NOT NULL DEFAULT 0");
        }
        // SQLite doesn't support MODIFY, columns are created with proper constraints
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE subscriptions MODIFY currency_code VARCHAR(3) NULL");
            DB::statement("ALTER TABLE subscriptions MODIFY amount_cents INT NULL");
        }
    }
};
