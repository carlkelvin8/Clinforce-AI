<?php

namespace App\Console\Commands;

use App\Models\JobApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneRejectedApplications extends Command
{
    protected $signature   = 'applications:prune-rejected';
    protected $description = 'Delete rejected applications older than the employer data retention setting';

    public function handle(): void
    {
        // Global default: 90 days. Per-employer override stored in employer_profiles.data_retention_days
        $defaultDays = (int) config('app.data_retention_days', 90);

        // Get all employers with a custom retention setting
        $employers = DB::table('employer_profiles')
            ->whereNotNull('data_retention_days')
            ->where('data_retention_days', '>', 0)
            ->pluck('data_retention_days', 'user_id');

        // Build a list of (owner_user_id => days) for all employers
        // For those without a custom setting, use the default
        $ownerIds = DB::table('jobs')->distinct()->pluck('owner_user_id');

        $deleted = 0;

        foreach ($ownerIds as $ownerId) {
            $days = $employers[$ownerId] ?? $defaultDays;
            if ($days <= 0) continue;

            $cutoff = now()->subDays($days);

            $count = JobApplication::query()
                ->where('status', 'rejected')
                ->where('updated_at', '<', $cutoff)
                ->whereHas('job', fn ($q) => $q->where('owner_user_id', $ownerId))
                ->delete();

            $deleted += $count;
        }

        $this->info("Pruned {$deleted} rejected application(s).");
    }
}
