<?php

namespace App\Console\Commands;

use App\Models\Interview;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendInterviewReminders extends Command
{
    protected $signature   = 'interviews:reminders';
    protected $description = 'Send 24h and 1h reminder emails for upcoming interviews';

    public function handle(): void
    {
        $now = Carbon::now();

        // Windows: 23h55m–24h05m and 55m–65m before start
        $windows = [
            ['label' => '24h', 'from' => $now->copy()->addMinutes(23 * 60 + 55), 'to' => $now->copy()->addMinutes(24 * 60 + 5)],
            ['label' => '1h',  'from' => $now->copy()->addMinutes(55),            'to' => $now->copy()->addMinutes(65)],
        ];

        foreach ($windows as $window) {
            $interviews = Interview::query()
                ->with(['application.job', 'application.applicant'])
                ->where('status', '!=', 'cancelled')
                ->whereBetween('scheduled_start', [$window['from'], $window['to']])
                ->get();

            foreach ($interviews as $iv) {
                $applicantEmail = $iv->application?->applicant?->email;
                $jobTitle       = $iv->application?->job?->title ?? 'your interview';
                $startTime      = Carbon::parse($iv->scheduled_start)->format('D, M j \a\t g:i A');
                $link           = $iv->meeting_link ?? $iv->location_text ?? 'See your dashboard';

                if ($applicantEmail) {
                    try {
                        Mail::raw(
                            "Hi,\n\nThis is a reminder that your interview for \"{$jobTitle}\" is scheduled in {$window['label']}.\n\n" .
                            "Time: {$startTime}\n" .
                            "Details: {$link}\n\n" .
                            "Good luck!\n— ClinForce Team",
                            fn ($m) => $m->to($applicantEmail)
                                ->subject("⏰ Interview reminder ({$window['label']}) — {$jobTitle}")
                        );
                        $this->info("Sent {$window['label']} reminder to {$applicantEmail}");
                    } catch (\Throwable $e) {
                        $this->warn("Failed to send to {$applicantEmail}: " . $e->getMessage());
                    }
                }
            }
        }

        $this->info('Interview reminders done.');
    }
}
