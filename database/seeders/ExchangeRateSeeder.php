<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            // USD to other currencies
            ['base_currency' => 'USD', 'quote_currency' => 'USD', 'rate' => 1.0000],
            ['base_currency' => 'USD', 'quote_currency' => 'EUR', 'rate' => 0.9200],
            ['base_currency' => 'USD', 'quote_currency' => 'GBP', 'rate' => 0.7900],
            ['base_currency' => 'USD', 'quote_currency' => 'PHP', 'rate' => 56.5000],
            ['base_currency' => 'USD', 'quote_currency' => 'JPY', 'rate' => 149.0000],
            ['base_currency' => 'USD', 'quote_currency' => 'AUD', 'rate' => 1.5200],
            ['base_currency' => 'USD', 'quote_currency' => 'CAD', 'rate' => 1.3500],
        ];

        foreach ($rates as $rate) {
            DB::table('exchange_rates')->updateOrInsert(
                [
                    'base_currency' => $rate['base_currency'],
                    'quote_currency' => $rate['quote_currency'],
                ],
                [
                    'rate' => $rate['rate'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
