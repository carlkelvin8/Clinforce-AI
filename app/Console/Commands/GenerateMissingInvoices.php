<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Console\Command;

class GenerateMissingInvoices extends Command
{
    protected $signature = 'invoices:generate-missing';
    protected $description = 'Generate invoices for subscriptions that don\'t have them';

    public function handle()
    {
        $this->info('Checking for subscriptions without invoices...');

        $subscriptions = Subscription::query()
            ->whereDoesntHave('invoices')
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('All subscriptions have invoices!');
            return 0;
        }

        $this->info("Found {$subscriptions->count()} subscriptions without invoices");

        $bar = $this->output->createProgressBar($subscriptions->count());
        $bar->start();

        foreach ($subscriptions as $sub) {
            Invoice::create([
                'user_id' => $sub->user_id,
                'subscription_id' => $sub->id,
                'amount_cents' => $sub->amount_cents ?? 0,
                'currency_code' => $sub->currency_code ?? 'USD',
                'status' => 'paid',
                'provider' => 'stripe',
                'provider_ref' => $sub->stripe_subscription_id,
                'issued_at' => $sub->start_at ?? $sub->created_at,
                'paid_at' => $sub->start_at ?? $sub->created_at,
            ]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done! Generated ' . $subscriptions->count() . ' invoices.');

        return 0;
    }
}
