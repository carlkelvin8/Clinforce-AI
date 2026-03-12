<?php

namespace App\Services;

use App\Models\Country;
use App\Models\EmployerProfile;
use App\Models\ExchangeRate;
use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

class CurrencyService
{
    public function getEmployerProfile(User $user): ?EmployerProfile
    {
        if (!$user->relationLoaded('employerProfile')) {
            $user->load('employerProfile');
        }

        return $user->employerProfile;
    }

    public function getCountryData(?string $countryInput): ?array
    {
        if (!$countryInput) {
            return null;
        }

        $input = trim($countryInput);

        $country = null;

        try {
            $country = Country::query()
                ->where('country_code', $input)
                ->orWhere('country_name', $input)
                ->first();
        } catch (QueryException $e) {
            $codeStr = (string) $e->getCode();
            if ($codeStr !== '42S02') {
                throw $e;
            }
        }

        if ($country) {
            $currencyCode = $country->currency_code ?: 'USD';
            return [
                'country_code' => $country->country_code,
                'country_name' => $country->country_name,
                'currency_code' => $currencyCode,
                'currency_symbol' => $country->currency_symbol ?: $this->fallbackSymbol($currencyCode),
                'currency_decimals' => (int) $country->currency_decimals,
            ];
        }

        // Try static map (assuming input is code)
        $code = strtoupper($input);
        $fallback = $this->staticCountryMap()[$code] ?? null;
        
        // If not found by code, try to find by name in static map
        if (!$fallback) {
            foreach ($this->staticCountryMap() as $c => $data) {
                if (strcasecmp($data['name'], $input) === 0) {
                    $fallback = $data;
                    $code = $c;
                    break;
                }
            }
        }

        if ($fallback) {
            return [
                'country_code' => $code,
                'country_name' => $fallback['name'],
                'currency_code' => $fallback['currency'],
                'currency_symbol' => $fallback['symbol'],
                'currency_decimals' => $fallback['decimals'],
            ];
        }

        return null;
    }

    public function getEmployerCurrencyContext(User $user): array
    {
        $profile = $this->getEmployerProfile($user);

        $countryInput = $profile?->country ?: null;
        $country = $this->getCountryData($countryInput);

        $baseCurrency = strtoupper(config('billing.base_currency', 'USD'));

        $preferredCurrency = null;
        if ($profile && $profile->billing_currency_code) {
            $preferredCurrency = strtoupper($profile->billing_currency_code);
        }

        $countryCurrency = $country['currency_code'] ?? $baseCurrency;

        $effectiveCurrency = $preferredCurrency ?: $countryCurrency;

        $supported = $this->supportedCurrencies();

        $fallbackApplied = false;
        if (!in_array($effectiveCurrency, $supported, true)) {
            $effectiveCurrency = $baseCurrency;
            $fallbackApplied = true;
        }

        $rateInfo = $this->getRate($baseCurrency, $effectiveCurrency);

        if (!$rateInfo['conversion_available'] && $effectiveCurrency !== $baseCurrency) {
            $effectiveCurrency = $baseCurrency;
            $fallbackApplied = true;
            $rateInfo = $this->getRate($baseCurrency, $effectiveCurrency);
        }

        $decimals = $country['currency_decimals'] ?? $this->defaultDecimals($effectiveCurrency);
        $symbol = $country['currency_symbol'] ?? $this->fallbackSymbol($effectiveCurrency);

        return [
            'country_code' => $country['country_code'] ?? null,
            'country_name' => $country['country_name'] ?? null,
            'preferred_currency_code' => $preferredCurrency,
            'currency_code' => $effectiveCurrency,
            'currency_symbol' => $symbol,
            'currency_decimals' => $decimals,
            'base_currency' => $baseCurrency,
            'rate' => $rateInfo['rate'],
            'rate_updated_at' => $rateInfo['updated_at'],
            'rate_is_stale' => $rateInfo['is_stale'],
            'fallback_applied' => $fallbackApplied,
            'conversion_available' => $rateInfo['conversion_available'],
        ];
    }

    public function convertPlanPriceForUser(Plan $plan, array $context): array
    {
        $baseCurrency = strtoupper($plan->currency ?: config('billing.base_currency', 'USD'));
        $targetCurrency = $context['currency_code'];

        $basePriceCents = (int) $plan->price_cents;

        if ($baseCurrency === $targetCurrency) {
            $amountCents = $basePriceCents;
            return [
                'amount_cents' => $amountCents,
                'currency_code' => $targetCurrency,
                'decimals' => $this->defaultDecimals($targetCurrency),
            ];
        }

        $rateInfo = $this->getRate($baseCurrency, $targetCurrency);
        if (!$rateInfo['conversion_available'] || $rateInfo['rate'] === null) {
            return [
                'amount_cents' => null,
                'currency_code' => $targetCurrency,
                'decimals' => $this->defaultDecimals($targetCurrency),
            ];
        }

        $decimals = $this->defaultDecimals($targetCurrency);

        $baseAmount = $basePriceCents / 100;
        $converted = $baseAmount * $rateInfo['rate'];
        $scaled = (int) round($converted * pow(10, $decimals));

        return [
            'amount_cents' => $scaled,
            'currency_code' => $targetCurrency,
            'decimals' => $decimals,
        ];
    }

    /**
     * Convert amount from one currency to another
     * 
     * @param int $amountCents Amount in cents (minor units)
     * @param string $fromCurrency Source currency code (e.g., 'USD')
     * @param string $toCurrency Target currency code (e.g., 'PHP')
     * @return int Converted amount in cents (minor units)
     */
    public function convertAmount(int $amountCents, string $fromCurrency, string $toCurrency): int
    {
        // If same currency, no conversion needed
        if ($fromCurrency === $toCurrency) {
            return $amountCents;
        }

        // Get exchange rate
        $rateData = $this->getRate($fromCurrency, $toCurrency);
        $rate = $rateData['rate'] ?? null;

        if ($rate === null) {
            \Log::warning("Currency conversion failed: no rate for {$fromCurrency} to {$toCurrency}");
            return $amountCents; // Return original amount if conversion fails
        }

        // Convert
        $converted = (int) round($amountCents * $rate);

        return $converted;
    }

    public function formatMinor(int $amountCents, int $decimals): string
    {
        $factor = pow(10, $decimals);
        $value = $amountCents / $factor;

        return number_format($value, $decimals, '.', ',');
    }

    public function getCountriesList(): array
    {
        $fromDb = Country::query()
            ->orderBy('country_name')
            ->get()
            ->map(function (Country $c) {
                return [
                    'country_code' => $c->country_code,
                    'country_name' => $c->country_name,
                    'currency_code' => $c->currency_code,
                ];
            })
            ->all();

        if ($fromDb) {
            return $fromDb;
        }

        $map = $this->staticCountryMap();
        $rows = [];
        foreach ($map as $code => $row) {
            $rows[] = [
                'country_code' => $code,
                'country_name' => $row['name'],
                'currency_code' => $row['currency'],
            ];
        }

        usort($rows, function ($a, $b) {
            return strcmp($a['country_name'], $b['country_name']);
        });

        return $rows;
    }

    protected function getRate(string $baseCurrency, string $quoteCurrency): array
    {
        $base = strtoupper($baseCurrency);
        $quote = strtoupper($quoteCurrency);

        if ($base === $quote) {
            return [
                'rate' => 1.0,
                'updated_at' => null,
                'is_stale' => false,
                'conversion_available' => true,
            ];
        }

        $rate = null;

        try {
            $rate = ExchangeRate::query()
                ->where('base_currency', $base)
                ->where('quote_currency', $quote)
                ->orderByDesc('updated_at')
                ->first();
        } catch (QueryException $e) {
            $codeStr = (string) $e->getCode();
            if ($codeStr !== '42S02') {
                throw $e;
            }
        }

        if (!$rate) {
            return [
                'rate' => null,
                'updated_at' => null,
                'is_stale' => false,
                'conversion_available' => false,
            ];
        }

        $maxAgeHours = (int) config('billing.max_rate_age_hours', 24);
        $updatedAt = $rate->updated_at instanceof Carbon ? $rate->updated_at : Carbon::parse($rate->updated_at);
        $isStale = $updatedAt->lt(now()->subHours($maxAgeHours));

        return [
            'rate' => (float) $rate->rate,
            'updated_at' => $updatedAt->toIso8601String(),
            'is_stale' => $isStale,
            'conversion_available' => true,
        ];
    }

    protected function supportedCurrencies(): array
    {
        return array_values(array_unique(array_map('strtoupper', config('billing.supported_currencies', []))));
    }

    protected function defaultDecimals(string $currency): int
    {
        $code = strtoupper($currency);
        if ($code === 'JPY') {
            return 0;
        }
        return 2;
    }

    protected function fallbackSymbol(string $currency): string
    {
        $code = strtoupper($currency);
        if ($code === 'USD') return '$';
        if ($code === 'EUR') return '€';
        if ($code === 'GBP') return '£';
        if ($code === 'PHP') return '₱';
        if ($code === 'JPY') return '¥';
        if ($code === 'AUD') return 'A$';
        if ($code === 'CAD') return 'CA$';
        return $code;
    }

    protected function staticCountryMap(): array
    {
        return [
            'US' => [
                'name' => 'United States',
                'currency' => 'USD',
                'symbol' => '$',
                'decimals' => 2,
            ],
            'PH' => [
                'name' => 'Philippines',
                'currency' => 'PHP',
                'symbol' => '₱',
                'decimals' => 2,
            ],
            'GB' => [
                'name' => 'United Kingdom',
                'currency' => 'GBP',
                'symbol' => '£',
                'decimals' => 2,
            ],
            'DE' => [
                'name' => 'Germany',
                'currency' => 'EUR',
                'symbol' => '€',
                'decimals' => 2,
            ],
            'FR' => [
                'name' => 'France',
                'currency' => 'EUR',
                'symbol' => '€',
                'decimals' => 2,
            ],
            'ES' => [
                'name' => 'Spain',
                'currency' => 'EUR',
                'symbol' => '€',
                'decimals' => 2,
            ],
            'NL' => [
                'name' => 'Netherlands',
                'currency' => 'EUR',
                'symbol' => '€',
                'decimals' => 2,
            ],
            'AU' => [
                'name' => 'Australia',
                'currency' => 'AUD',
                'symbol' => 'A$',
                'decimals' => 2,
            ],
            'CA' => [
                'name' => 'Canada',
                'currency' => 'CAD',
                'symbol' => 'CA$',
                'decimals' => 2,
            ],
            'JP' => [
                'name' => 'Japan',
                'currency' => 'JPY',
                'symbol' => '¥',
                'decimals' => 0,
            ],
        ];
    }
}
