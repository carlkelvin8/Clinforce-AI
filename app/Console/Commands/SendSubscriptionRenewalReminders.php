<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionRenewalReminders extends Command
{
    protected $signature   = 'subscriptions:renewal-reminders';
    protected $description = 'Send renewal reminder emails 7 days and 1 day before subscription expiry';

    public function handle(): void
    {
        $windows = [
            ['label' => '7 days',  'days' => 7],
            ['label' => '1 day',   'days' => 1],
        ];

        foreach ($windows as $window) {
            $targetDate = now()->addDays($window['days'])->toDateString();

            $subs = Subscription::query()
                ->with(['user', 'plan'])
                ->where('status', 'active')
                ->whereDate('end_at', $targetDate)
                ->get();

            foreach ($subs as $sub) {
                $user = $sub->user;
                if (!$user?->email) continue;

                $planName  = $sub->plan?->name ?? 'your plan';
                $expiresOn = $sub->end_at?->format('F j, Y') ?? 'soon';
                $amount    = number_format($sub->amount_cents / 100, 2);
                $currency  = strtoupper($sub->currency_code ?? 'USD');

                try {
                    Mail::raw(
                        "Hi,\n\n" .
                        "Your ClinForce subscription ({$planName}) expires in {$window['label']} on {$expiresOn}.\n\n" .
                        "Plan: {$planName}\n" .
                        "Amount: {$currency} {$amount}\n\n" .
                        "To renew, visit: " . config('app.frontend_url') . "/employer/billing\n\n" .
                        "If you have any questions, reply to this email.\n\n" .
                        "— ClinForce Team",
                        fn ($m) => $m->to($user->email)
                            ->subject("⏰ Your ClinForce subscription expires in {$window['label']}")
                    );
                    $this->info("Sent {$window['label']} reminder to {$user->email}");
                } catch (\Throwable $e) {
                    $this->warn("Failed to send to {$user->email}: " . $e->getMessage());
                }
            }
        }

        $this->info('Renewal reminders done.');
    }
}
