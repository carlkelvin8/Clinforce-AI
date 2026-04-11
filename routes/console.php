<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire subscriptions past end_at — runs every hour
Schedule::command('subscriptions:expire')->hourly();

// Sync exchange rates — runs every 6 hours
Schedule::command('exchange-rates:sync')->everySixHours();

// Prune expired Sanctum tokens — runs daily
Schedule::command('sanctum:prune-expired --hours=0')->daily();

// Send interview reminders (24h and 1h before) — runs every 30 minutes
Schedule::command('interviews:reminders')->everyThirtyMinutes();

// Prune rejected applications per employer data retention settings — runs daily at 2am
Schedule::command('applications:prune-rejected')->dailyAt('02:00');

// Archive jobs past their closes_at deadline — runs hourly
Schedule::command('jobs:archive-expired')->hourly();

// Send job alert emails for newly published jobs — runs every 6 hours
Schedule::command('job-alerts:send')->everySixHours();

// Subscription renewal reminders — runs daily at 9am
Schedule::command('subscriptions:renewal-reminders')->dailyAt('09:00');

// Trial ending/expired notifications — runs daily at 8am
Schedule::command('trials:notify-ending')->dailyAt('08:00');

// Process queued jobs — runs every minute (for database queue on shared hosting)
// On a proper server, run: php artisan queue:work --daemon instead
Schedule::command('queue:work --stop-when-empty --tries=3')->everyMinute()->withoutOverlapping();
