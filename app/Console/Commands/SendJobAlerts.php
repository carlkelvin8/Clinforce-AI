<?php

namespace App\Console\Commands;

use App\Mail\JobAlertMail;
use App\Models\Job;
use App\Models\JobAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendJobAlerts extends Command
{
    protected $signature   = 'job-alerts:send';
    protected $description = 'Send email alerts for newly published jobs matching active user alerts';

    public function handle(): void
    {
        // Only check jobs published in the last 24 hours
        $since = now()->subHours(24);

        $newJobs = Job::query()
            ->where('status', 'published')
            ->where('published_at', '>=', $since)
            ->get();

        if ($newJobs->isEmpty()) {
            $this->info('No new jobs to alert on.');
            return;
        }

        $alerts = JobAlert::query()
            ->where('active', true)
            ->with('user')
            ->get();

        $sent = 0;

        foreach ($alerts as $alert) {
            if (!$alert->user?->email) continue;

            foreach ($newJobs as $job) {
                if (!$alert->matches($job)) continue;

                try {
                    Mail::to($alert->user->email)->send(new JobAlertMail($job, $alert));
                    $sent++;
                    $this->info("Sent alert to {$alert->user->email} for job: {$job->title}");
                } catch (\Throwable $e) {
                    $this->warn("Failed to send to {$alert->user->email}: " . $e->getMessage());
                }
            }
        }

        $this->info("Job alerts done. Sent: {$sent}");
    }
}
