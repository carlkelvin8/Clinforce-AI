<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class TrialsNotifyEnding extends Command
{
    protected $signature = 'trials:notify-ending';

    protected $description = 'Notify employers whose trials are ending soon (2 days) or have just expired';

    public function handle()
    {
        $now = Carbon::now();

        // 1. Notify users whose trial ends in ~2 days (window: 48-72h left)
        $endingSoon = User::where('role', 'employer')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', $now->copy()->addHours(48))
            ->where('trial_ends_at', '<=', $now->copy()->addHours(72))
            ->whereDoesntHave('notifications', function ($q) {
                $q->where('type', 'trial_ending_soon')
                  ->where('created_at', '>=', now()->subDay());
            })
            ->get();

        foreach ($endingSoon as $user) {
            $daysLeft = max(1, ceil($user->trial_ends_at->diffInHours(now()) / 24));
            Notification::pushNotification([
                'user_id' => $user->id,
                'role' => 'employer',
                'category' => 'system',
                'type' => 'trial_ending_soon',
                'title' => 'Trial ending soon',
                'body' => "Your free trial ends in {$daysLeft} day(s). Subscribe now to keep access to all features.",
                'data' => ['trial_ends_at' => $user->trial_ends_at->toIso8601String()],
                'url' => '/employer/billing',
            ]);
            $this->info("Notified employer #{$user->id} — trial ending in {$daysLeft} day(s).");
        }

        // 2. Notify users whose trial just expired (within last 24 hours)
        $justExpired = User::where('role', 'employer')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', $now->copy()->subHours(24))
            ->where('trial_ends_at', '<=', $now)
            ->whereDoesntHave('notifications', function ($q) {
                $q->where('type', 'trial_expired')
                  ->where('created_at', '>=', now()->subDay());
            })
            ->get();

        foreach ($justExpired as $user) {
            Notification::pushNotification([
                'user_id' => $user->id,
                'role' => 'employer',
                'category' => 'system',
                'type' => 'trial_expired',
                'title' => 'Your trial has expired',
                'body' => 'Your 7-day free trial has ended. Choose a subscription plan to restore access to candidate profiles, messaging, and hiring features.',
                'data' => ['trial_ended_at' => $user->trial_ends_at->toIso8601String()],
                'url' => '/employer/billing',
            ]);
            $this->info("Notified employer #{$user->id} — trial has expired.");
        }

        $this->info('Trial notification check complete.');

        return Command::SUCCESS;
    }
}
