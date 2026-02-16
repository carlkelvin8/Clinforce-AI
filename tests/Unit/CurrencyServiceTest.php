<?php

namespace Tests\Unit;

use App\Models\Country;
use App\Models\EmployerProfile;
use App\Models\ExchangeRate;
use App\Models\Plan;
use App\Models\User;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @requires extension pdo_sqlite
 */
class CurrencyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_mapping_uses_static_fallback_when_table_empty(): void
    {
        $svc = new CurrencyService();

        $ph = $svc->getCountryData('PH');

        $this->assertNotNull($ph);
        $this->assertSame('PH', $ph['country_code']);
        $this->assertSame('Philippines', $ph['country_name']);
        $this->assertSame('PHP', $ph['currency_code']);
    }

    public function test_conversion_uses_exchange_rate_and_respects_decimals(): void
    {
        Country::create([
            'country_code' => 'PH',
            'country_name' => 'Philippines',
            'currency_code' => 'PHP',
            'currency_symbol' => '₱',
            'currency_decimals' => 2,
        ]);

        ExchangeRate::create([
            'base_currency' => 'USD',
            'quote_currency' => 'PHP',
            'rate' => 50.00,
        ]);

        $user = User::factory()->create([
            'role' => 'employer',
        ]);

        EmployerProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Test',
            'business_type' => 'clinic',
            'country_code' => 'PH',
        ]);

        $plan = Plan::create([
            'name' => 'Basic',
            'duration_months' => 1,
            'job_post_limit' => 1,
            'ai_screening_enabled' => false,
            'analytics_enabled' => false,
            'price_cents' => 10000,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $svc = new CurrencyService();
        $ctx = $svc->getEmployerCurrencyContext($user);
        $result = $svc->convertPlanPriceForUser($plan, $ctx);

        $this->assertSame('PHP', $ctx['currency_code']);
        $this->assertSame(2, $result['decimals']);

        $this->assertSame(5000000, $result['amount_cents']);
    }

    public function test_conversion_returns_null_when_rate_missing(): void
    {
        $user = User::factory()->create([
            'role' => 'employer',
        ]);

        EmployerProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Test',
            'business_type' => 'clinic',
            'country_code' => 'PH',
        ]);

        $plan = Plan::create([
            'name' => 'Basic',
            'duration_months' => 1,
            'job_post_limit' => 1,
            'ai_screening_enabled' => false,
            'analytics_enabled' => false,
            'price_cents' => 10000,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $svc = new CurrencyService();
        $ctx = $svc->getEmployerCurrencyContext($user);
        $result = $svc->convertPlanPriceForUser($plan, $ctx);

        $this->assertNull($result['amount_cents']);
    }
}
