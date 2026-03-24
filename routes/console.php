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

// Send interview reminders 24h before scheduled interviews — runs hourly
Schedule::command('interviews:send-reminders')->hourly();
