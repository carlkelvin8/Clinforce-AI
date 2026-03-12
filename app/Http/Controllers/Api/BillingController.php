<?php

namespace App\Http\Controllers\Api;

use App\Models\EmployerProfile;
use App\Models\Plan;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends ApiController
{
    private CurrencyService $currency;

    public function __construct(CurrencyService $currency)
    {
        $this->currency = $currency;
    }

    public function currency(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Only employers and agencies can access billing', null, 403);
        }

        $profile = $this->currency->getEmployerProfile($u);
        
        \Log::info('Billing currency request', [
            'user_id' => $u->id,
            'profile_country' => $profile?->country,
        ]);
        
        // Country check removed - defaults to USD if not set

        $ctx = $this->currency->getEmployerCurrencyContext($u);
        
        \Log::info('Currency context', $ctx);

        $plans = Plan::query()
            ->where('is_active', 1)
            ->orderBy('price_cents')
            ->get();

        $converted = [];

        foreach ($plans as $plan) {
            $conv = $this->currency->convertPlanPriceForUser($plan, $ctx);
            
            \Log::info('Plan conversion', [
                'plan_id' => $plan->id,
                'base_price' => $plan->price_cents,
                'base_currency' => $plan->currency,
                'converted_price' => $conv['amount_cents'],
                'target_currency' => $conv['currency_code'],
            ]);

            $amountCents = $conv['amount_cents'] ?? null;
            $decimals = $conv['decimals'];

            $converted[] = [
                'id' => $plan->id,
                'name' => $plan->name,
                'duration_months' => (int) $plan->duration_months,
                'job_post_limit' => (int) $plan->job_post_limit,
                'ai_screening_enabled' => (bool) $plan->ai_screening_enabled,
                'analytics_enabled' => (bool) $plan->analytics_enabled,
                'base_price_cents' => (int) $plan->price_cents,
                'base_currency' => $plan->currency ?: $ctx['base_currency'],
                'currency_code' => $ctx['currency_code'],
                'currency_symbol' => $ctx['currency_symbol'],
                'price_cents' => $amountCents,
                'price' => $amountCents !== null ? $this->currency->formatMinor($amountCents, $decimals) : null,
                'interval' => 'month',
            ];
        }

        $payload = [
            'country_code' => $ctx['country_code'],
            'country_name' => $ctx['country_name'],
            'currency_code' => $ctx['currency_code'],
            'symbol' => $ctx['currency_symbol'],
            'decimals' => $ctx['currency_decimals'],
            'base_currency' => $ctx['base_currency'],
            'rate' => $ctx['rate'],
            'rate_updated_at' => $ctx['rate_updated_at'],
            'rate_is_stale' => $ctx['rate_is_stale'],
            'fallback_applied' => $ctx['fallback_applied'],
            'conversion_available' => $ctx['conversion_available'],
            'preferred_currency_code' => $ctx['preferred_currency_code'],
            'converted_prices' => $converted,
        ];

        return $this->ok($payload);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Only employers and agencies can update billing profile', null, 403);
        }

        $data = $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
        ]);

        $countryCode = strtoupper($data['country_code']);
        $billingCurrency = $countryCode === 'PH' ? 'PHP' : 'USD';

        $profile = EmployerProfile::query()->updateOrCreate(
            ['user_id' => $u->id],
            [
                'country_code' => $countryCode,
                'billing_currency_code' => $billingCurrency,
            ]
        );

        $ctx = $this->currency->getEmployerCurrencyContext($u);

        return $this->ok([
            'profile' => $profile,
            'currency' => $ctx,
        ], 'Billing profile updated');
    }

    private function currencySupported(string $currencyCode): bool
    {
        $code = strtoupper($currencyCode);
        $supported = config('billing.supported_currencies', []);
        return in_array($code, $supported, true);
    }
}
