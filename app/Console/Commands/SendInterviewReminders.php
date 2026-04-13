<?php

namespace App\Console\Commands;

use App\Models\Interview;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendInterviewReminders extends Command
{
    protected $signature   = 'interviews:reminders';
    protected $description = 'Send 24h and 1h reminder emails for upcoming interviews';

    public function handle(): int
    {
        $now = Carbon::now();
        $sent = 0;

        // 15-minute windows to catch when command runs every 30 min
        $windows = [
            [
                'label' => '24h',
                'flag'  => 'reminder_24h_sent_at',
                'from'  => $now->copy()->addMinutes(23 * 60 + 45),
                'to'    => $now->copy()->addMinutes(24 * 60 + 15),
            ],
            [
                'label' => '1h',
                'flag'  => 'reminder_1h_sent_at',
                'from'  => $now->copy()->addMinutes(45),
                'to'    => $now->copy()->addMinutes(75),
            ],
        ];

        foreach ($windows as $window) {
            // Dynamically add flag column handling if it exists
            $hasFlag = $this->hasReminderColumn($window['flag']);

            $query = Interview::query()
                ->with(['application.job', 'application.applicant'])
                ->where('status', 'confirmed')
                ->whereBetween('scheduled_start', [$window['from'], $window['to']]);

            if ($hasFlag) {
                $query->whereNull($window['flag']);
            }

            $interviews = $query->get();

            if ($interviews->isEmpty()) {
                continue;
            }

            $this->info("Found {$interviews->count()} interviews for {$window['label']} reminder window");

            foreach ($interviews as $iv) {
                $applicantEmail = $iv->application?->applicant?->email;
                $employerEmail  = $iv->application?->job?->owner?->email;
                $jobTitle       = $iv->application?->job?->title ?? 'your interview';
                $startTime      = Carbon::parse($iv->scheduled_start)->format('D, M j \a\t g:i A T');
                $link           = $iv->meeting_link ?? $iv->location_text ?? 'See your dashboard';

                // Send to applicant
                if ($applicantEmail) {
                    try {
                        Mail::raw(
                            "Hi,\n\n" .
                            "This is a friendly reminder that your interview for \"{$jobTitle}\" is coming up in about {$window['label']}.\n\n" .
                            "📅 Date & Time: {$startTime}\n" .
                            "🔗 Details: {$link}\n\n" .
                            "Please be ready a few minutes early. Good luck!\n" .
                            "— ClinForce Team",
                            fn ($m) => $m->to($applicantEmail)
                                ->subject("⏰ Interview Reminder ({$window['label']}) — {$jobTitle}")
                        );
                        $this->info("  → Applicant reminder sent to {$applicantEmail}");
                    } catch (\Throwable $e) {
                        $this->warn("  ✗ Failed applicant email to {$applicantEmail}: {$e->getMessage()}");
                    }
                }

                // Send to employer/interviewer
                if ($employerEmail) {
                    try {
                        $applicantName = $iv->application?->applicant?->name ?? 'the candidate';
                        Mail::raw(
                            "Hi,\n\n" .
                            "Reminder: You have an interview with {$applicantName} for \"{$jobTitle}\" in about {$window['label']}.\n\n" .
                            "📅 Date & Time: {$startTime}\n" .
                            "🔗 Details: {$link}\n\n" .
                            "— ClinForce Team",
                            fn ($m) => $m->to($employerEmail)
                                ->subject("⏰ Interview Reminder ({$window['label']}) — {$jobTitle}")
                        );
                        $this->info("  → Employer reminder sent to {$employerEmail}");
                    } catch (\Throwable $e) {
                        $this->warn("  ✗ Failed employer email to {$employerEmail}: {$e->getMessage()}");
                    }
                }

                // Mark as sent (if column exists)
                if ($hasFlag) {
                    $iv->{$window['flag']} = now();
                    $iv->save();
                }

                $sent++;
            }
        }

        $this->info("Interview reminders complete. Processed {$sent} interviews.");
        return self::SUCCESS;
    }

    private function hasReminderColumn(string $column): bool
    {
        static $columns = null;

        if ($columns === null) {
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing('interviews');
        }

        return in_array($column, $columns, true);
    }
}
