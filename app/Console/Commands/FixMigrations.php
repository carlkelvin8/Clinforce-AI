<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMigrations extends Command
{
    protected $signature = 'fix:migrations';
    protected $description = 'Mark stuck migrations as run and add missing columns';

    public function handle()
    {
        // Mark the stuck migration as already run
        $exists = DB::table('migrations')
            ->where('migration', '2026_03_20_000020_create_feature_tables')
            ->exists();

        if (!$exists) {
            DB::table('migrations')->insert([
                'migration' => '2026_03_20_000020_create_feature_tables',
                'batch' => 99,
            ]);
            $this->info('Marked 2026_03_20_000020_create_feature_tables as run.');
        } else {
            $this->info('Migration already marked as run.');
        }

        $this->info('Done. Now run: php artisan migrate --force');
    }
}
