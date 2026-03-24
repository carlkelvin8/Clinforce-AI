<?php

namespace App\Console\Commands;

use App\Mail\InterviewScheduled;
use App\Models\Interview;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendInterviewReminders extends Command
{
    protected $signature = 'interviews:send-reminders';
    protected $description = 'Send email reminders 24h before scheduled interviews';

    public function handle(): int
    {
        $now = now();
        $windowStart = $now->copy()->addHours(23)->addMinutes(30);
        $windowEnd = $now->copy()->addHours(24)->addMinutes(30);

        $interviews = Interview::query()
            ->with(['application.applicant', 'application.job.owner'])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereBetween('scheduled_start', [$windowStart, $windowEnd])
            ->whereNull('reminder_sent_at')
            ->get();

        $sent = 0;

        foreach ($interviews as $interview) {
            try {
                $candidate = $interview->application?->applicant;
                $employer = $interview->application?->job?->owner;

                if ($candidate && $candidate->email) {
                    Mail::to($candidate->email)->send(new InterviewScheduled($interview));
                }

                if ($employer && $employer->email && $employer->email !== $candidate?->email) {
                    Mail::to($employer->email)->send(new InterviewScheduled($interview));
                }

                $interview->reminder_sent_at = now();
                $interview->save();

                $sent++;
            } catch (\Throwable $e) {
                $this->error("Failed to send reminder for interview {$interview->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} interview reminder(s).");

        return 0;
    }
}
