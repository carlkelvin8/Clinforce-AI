<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['country_code' => 'US', 'country_name' => 'United States', 'currency_code' => 'USD', 'currency_symbol' => '$', 'currency_decimals' => 2],
            ['country_code' => 'GB', 'country_name' => 'United Kingdom', 'currency_code' => 'GBP', 'currency_symbol' => '£', 'currency_decimals' => 2],
            ['country_code' => 'CA', 'country_name' => 'Canada', 'currency_code' => 'CAD', 'currency_symbol' => 'C$', 'currency_decimals' => 2],
            ['country_code' => 'AU', 'country_name' => 'Australia', 'currency_code' => 'AUD', 'currency_symbol' => 'A$', 'currency_decimals' => 2],
            ['country_code' => 'DE', 'country_name' => 'Germany', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_decimals' => 2],
            ['country_code' => 'FR', 'country_name' => 'France', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_decimals' => 2],
            ['country_code' => 'IT', 'country_name' => 'Italy', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_decimals' => 2],
            ['country_code' => 'ES', 'country_name' => 'Spain', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_decimals' => 2],
            ['country_code' => 'NL', 'country_name' => 'Netherlands', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_decimals' => 2],
            ['country_code' => 'BE', 'country_name' => 'Belgium', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_decimals' => 2],
            ['country_code' => 'CH', 'country_name' => 'Switzerland', 'currency_code' => 'CHF', 'currency_symbol' => 'CHF', 'currency_decimals' => 2],
            ['country_code' => 'SE', 'country_name' => 'Sweden', 'currency_code' => 'SEK', 'currency_symbol' => 'kr', 'currency_decimals' => 2],
            ['country_code' => 'NO', 'country_name' => 'Norway', 'currency_code' => 'NOK', 'currency_symbol' => 'kr', 'currency_decimals' => 2],
            ['country_code' => 'DK', 'country_name' => 'Denmark', 'currency_code' => 'DKK', 'currency_symbol' => 'kr', 'currency_decimals' => 2],
            ['country_code' => 'PL', 'country_name' => 'Poland', 'currency_code' => 'PLN', 'currency_symbol' => 'zł', 'currency_decimals' => 2],
            ['country_code' => 'JP', 'country_name' => 'Japan', 'currency_code' => 'JPY', 'currency_symbol' => '¥', 'currency_decimals' => 0],
            ['country_code' => 'CN', 'country_name' => 'China', 'currency_code' => 'CNY', 'currency_symbol' => '¥', 'currency_decimals' => 2],
            ['country_code' => 'IN', 'country_name' => 'India', 'currency_code' => 'INR', 'currency_symbol' => '₹', 'currency_decimals' => 2],
            ['country_code' => 'SG', 'country_name' => 'Singapore', 'currency_code' => 'SGD', 'currency_symbol' => 'S$', 'currency_decimals' => 2],
            ['country_code' => 'HK', 'country_name' => 'Hong Kong', 'currency_code' => 'HKD', 'currency_symbol' => 'HK$', 'currency_decimals' => 2],
            ['country_code' => 'NZ', 'country_name' => 'New Zealand', 'currency_code' => 'NZD', 'currency_symbol' => 'NZ$', 'currency_decimals' => 2],
            ['country_code' => 'BR', 'country_name' => 'Brazil', 'currency_code' => 'BRL', 'currency_symbol' => 'R$', 'currency_decimals' => 2],
            ['country_code' => 'MX', 'country_name' => 'Mexico', 'currency_code' => 'MXN', 'currency_symbol' => 'Mex$', 'currency_decimals' => 2],
            ['country_code' => 'AR', 'country_name' => 'Argentina', 'currency_code' => 'ARS', 'currency_symbol' => '$', 'currency_decimals' => 2],
            ['country_code' => 'ZA', 'country_name' => 'South Africa', 'currency_code' => 'ZAR', 'currency_symbol' => 'R', 'currency_decimals' => 2],
            ['country_code' => 'AE', 'country_name' => 'United Arab Emirates', 'currency_code' => 'AED', 'currency_symbol' => 'د.إ', 'currency_decimals' => 2],
            ['country_code' => 'SA', 'country_name' => 'Saudi Arabia', 'currency_code' => 'SAR', 'currency_symbol' => '﷼', 'currency_decimals' => 2],
            ['country_code' => 'IL', 'country_name' => 'Israel', 'currency_code' => 'ILS', 'currency_symbol' => '₪', 'currency_decimals' => 2],
            ['country_code' => 'TR', 'country_name' => 'Turkey', 'currency_code' => 'TRY', 'currency_symbol' => '₺', 'currency_decimals' => 2],
            ['country_code' => 'RU', 'country_name' => 'Russia', 'currency_code' => 'RUB', 'currency_symbol' => '₽', 'currency_decimals' => 2],
            ['country_code' => 'KR', 'country_name' => 'South Korea', 'currency_code' => 'KRW', 'currency_symbol' => '₩', 'currency_decimals' => 0],
            ['country_code' => 'TH', 'country_name' => 'Thailand', 'currency_code' => 'THB', 'currency_symbol' => '฿', 'currency_decimals' => 2],
            ['country_code' => 'MY', 'country_name' => 'Malaysia', 'currency_code' => 'MYR', 'currency_symbol' => 'RM', 'currency_decimals' => 2],
            ['country_code' => 'ID', 'country_name' => 'Indonesia', 'currency_code' => 'IDR', 'currency_symbol' => 'Rp', 'currency_decimals' => 0],
            ['country_code' => 'PH', 'country_name' => 'Philippines', 'currency_code' => 'PHP', 'currency_symbol' => '₱', 'currency_decimals' => 2],
            ['country_code' => 'VN', 'country_name' => 'Vietnam', 'currency_code' => 'VND', 'currency_symbol' => '₫', 'currency_decimals' => 0],
            ['country_code' => 'EG', 'country_name' => 'Egypt', 'currency_code' => 'EGP', 'currency_symbol' => '£', 'currency_decimals' => 2],
            ['country_code' => 'NG', 'country_name' => 'Nigeria', 'currency_code' => 'NGN', 'currency_symbol' => '₦', 'currency_decimals' => 2],
            ['country_code' => 'KE', 'country_name' => 'Kenya', 'currency_code' => 'KES', 'currency_symbol' => 'KSh', 'currency_decimals' => 2],
            ['country_code' => 'IE', 'country_name' => 'Ireland', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_decimals' => 2],
            ['country_code' => 'PT', 'country_name' => 'Portugal', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_decimals' => 2],
            ['country_code' => 'GR', 'country_name' => 'Greece', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_decimals' => 2],
            ['country_code' => 'AT', 'country_name' => 'Austria', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_decimals' => 2],
            ['country_code' => 'FI', 'country_name' => 'Finland', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_decimals' => 2],
            ['country_code' => 'CZ', 'country_name' => 'Czech Republic', 'currency_code' => 'CZK', 'currency_symbol' => 'Kč', 'currency_decimals' => 2],
            ['country_code' => 'HU', 'country_name' => 'Hungary', 'currency_code' => 'HUF', 'currency_symbol' => 'Ft', 'currency_decimals' => 0],
            ['country_code' => 'RO', 'country_name' => 'Romania', 'currency_code' => 'RON', 'currency_symbol' => 'lei', 'currency_decimals' => 2],
            ['country_code' => 'BG', 'country_name' => 'Bulgaria', 'currency_code' => 'BGN', 'currency_symbol' => 'лв', 'currency_decimals' => 2],
            ['country_code' => 'HR', 'country_name' => 'Croatia', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_decimals' => 2],
            ['country_code' => 'CL', 'country_name' => 'Chile', 'currency_code' => 'CLP', 'currency_symbol' => '$', 'currency_decimals' => 0],
        ];

        foreach ($countries as $country) {
            DB::table('countries')->updateOrInsert(
                ['country_code' => $country['country_code']],
                array_merge($country, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
