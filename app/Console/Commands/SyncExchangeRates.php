<?php

namespace App\Console\Commands;

use App\Models\ExchangeRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncExchangeRates extends Command
{
    protected $signature = 'exchange-rates:sync';
    protected $description = 'Fetch latest exchange rates from exchangerate-api.com and store them';

    // Currencies we need for billing
    private array $currencies = ['PHP', 'EUR', 'GBP', 'AUD', 'CAD', 'SGD', 'AED', 'JPY'];

    public function handle(): int
    {
        $apiKey = config('services.exchangerate.key') ?: env('EXCHANGERATE_API_KEY');

        if (!$apiKey) {
            // Fallback: use open.er-api.com (free, no key required, USD base only)
            return $this->syncFree();
        }

        return $this->syncWithKey($apiKey);
    }

    private function syncFree(): int
    {
        $this->info('Fetching rates from open.er-api.com (free tier, USD base)...');

        try {
            $res = Http::timeout(15)->get('https://open.er-api.com/v6/latest/USD');

            if (!$res->successful()) {
                $this->error('Request failed: ' . $res->status());
                return 1;
            }

            $data = $res->json();
            if (($data['result'] ?? '') !== 'success') {
                $this->error('API error: ' . ($data['error-type'] ?? 'unknown'));
                return 1;
            }

            $rates = $data['rates'] ?? [];
            $this->upsertRates('USD', $rates);

            // Derive cross-rates for PHP base (needed for PH plans)
            if (isset($rates['PHP'])) {
                $phpRate = (float) $rates['PHP'];
                $crossRates = ['USD' => 1 / $phpRate];
                foreach ($this->currencies as $cur) {
                    if ($cur !== 'PHP' && isset($rates[$cur])) {
                        $crossRates[$cur] = (float) $rates[$cur] / $phpRate;
                    }
                }
                $this->upsertRates('PHP', $crossRates);
            }

            $this->info('Exchange rates synced successfully.');
            return 0;

        } catch (\Exception $e) {
            Log::error('SyncExchangeRates failed', ['error' => $e->getMessage()]);
            $this->error('Exception: ' . $e->getMessage());
            return 1;
        }
    }

    private function syncWithKey(string $apiKey): int
    {
        $this->info('Fetching rates from exchangerate-api.com...');

        $baseCurrencies = array_merge(['USD'], $this->currencies);
        $synced = 0;

        foreach ($baseCurrencies as $base) {
            try {
                $res = Http::timeout(15)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$base}");

                if (!$res->successful()) {
                    $this->warn("Failed for base {$base}: " . $res->status());
                    continue;
                }

                $data = $res->json();
                if (($data['result'] ?? '') !== 'success') {
                    $this->warn("API error for {$base}: " . ($data['error-type'] ?? 'unknown'));
                    continue;
                }

                $this->upsertRates($base, $data['conversion_rates'] ?? []);
                $synced++;

            } catch (\Exception $e) {
                Log::error("SyncExchangeRates failed for {$base}", ['error' => $e->getMessage()]);
                $this->warn("Exception for {$base}: " . $e->getMessage());
            }
        }

        $this->info("Synced rates for {$synced} base currencies.");
        return $synced > 0 ? 0 : 1;
    }

    private function upsertRates(string $base, array $rates): void
    {
        $targets = array_merge(['USD'], $this->currencies);

        foreach ($targets as $quote) {
            if ($quote === $base || !isset($rates[$quote])) {
                continue;
            }

            ExchangeRate::updateOrCreate(
                ['base_currency' => $base, 'quote_currency' => $quote],
                ['rate' => (float) $rates[$quote], 'updated_at' => now()]
            );
        }
    }
}
