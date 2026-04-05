<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;

class ArchiveExpiredJobs extends Command
{
    protected $signature   = 'jobs:archive-expired';
    protected $description = 'Archive published jobs whose closes_at deadline has passed';

    public function handle(): void
    {
        $count = Job::query()
            ->where('status', 'published')
            ->whereNotNull('closes_at')
            ->where('closes_at', '<', now())
            ->update([
                'status'      => 'archived',
                'archived_at' => now(),
            ]);

        $this->info("Archived {$count} expired job(s).");
    }
}
