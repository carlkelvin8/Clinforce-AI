<?php
// app/Http/Controllers/Api/SubscriptionsController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\SubscriptionStoreRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends ApiController
{
    private CurrencyService $currency;

    public function __construct(CurrencyService $currency)
    {
        $this->currency = $currency;
    }
    public function index(): JsonResponse
    {
        $u = $this->requireAuth();

        \Log::info('Fetching subscriptions', [
            'user_id' => $u->id,
            'role' => $u->role,
        ]);

        $q = Subscription::query()->with('plan')->orderByDesc('id');

        if ($u->role !== 'admin') {
            if (!in_array($u->role, ['employer','agency'], true)) {
                return $this->fail('Only employer/agency can manage subscriptions', null, 403);
            }
            $q->where('user_id', $u->id);
        }

        $result = $q->paginate(20);
        
        \Log::info('Subscriptions fetched', [
            'count' => $result->count(),
            'total' => $result->total(),
            'data' => $result->items(),
        ]);

        return $this->ok($result);
    }

    public function store(SubscriptionStoreRequest $request): JsonResponse
    {
        $u = $this->requireAuth();
        if ($u->role !== 'admin' && !in_array($u->role, ['employer', 'agency'], true)) {
            return $this->fail('Only employer/agency can subscribe', null, 403);
        }

        $v = $request->validated();

        $plan = Plan::query()->where('id', $v['plan_id'])->where('is_active', 1)->first();
        if (!$plan) {
            return $this->fail('Invalid plan', ['plan_id' => ['Not active or not found']], 422);
        }

        if ($u->role !== 'admin') {
            $overlap = Subscription::query()
                ->where('user_id', $u->id)
                ->whereIn('status', ['active', 'past_due'])
                ->where('end_at', '>', now())
                ->exists();

            if ($overlap) {
                return $this->fail('Existing active subscription detected', null, 409);
            }
        }

        $profile = $this->currency->getEmployerProfile($u instanceof User ? $u : User::find($u->id));
        // Country check removed - defaults to USD if not set

        // Check if user has payment method
        \Log::info('Subscription check', [
            'user_id' => $u->id,
            'stripe_customer_id' => $u->stripe_customer_id,
        ]);

        if (!$u->stripe_customer_id) {
            return $this->fail(
                'Please add a payment method before subscribing.',
                ['payment_method' => ['Payment method required'], 'debug' => ['user_id' => $u->id, 'stripe_customer_id' => null]],
                422
            );
        }

        // Verify payment method exists
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            $paymentMethods = \Stripe\PaymentMethod::all([
                'customer' => $u->stripe_customer_id,
                'type' => 'card',
            ]);

            \Log::info('Payment methods found', [
                'customer_id' => $u->stripe_customer_id,
                'count' => count($paymentMethods->data),
            ]);

            if (count($paymentMethods->data) === 0) {
                return $this->fail(
                    'Please add a payment method before subscribing.',
                    ['payment_method' => ['No payment method found'], 'debug' => ['customer_id' => $u->stripe_customer_id]],
                    422
                );
            }
        } catch (\Exception $e) {
            \Log::error('Stripe error', ['error' => $e->getMessage()]);
            return $this->fail('Failed to verify payment method: ' . $e->getMessage(), null, 500);
        }

        $ctx = $this->currency->getEmployerCurrencyContext($u instanceof User ? $u : User::find($u->id));

        $conversion = $this->currency->convertPlanPriceForUser($plan, $ctx);
        $amountCents = $conversion['amount_cents'];

        if ($amountCents === null) {
            return $this->fail(
                'Unable to convert price for your billing currency. Please try again later or contact support.',
                ['currency_code' => ['Missing or outdated exchange rate']],
                422
            );
        }

        $start = isset($v['start_at']) ? now()->parse($v['start_at']) : now();
        $end = (clone $start)->addMonths((int) $plan->duration_months);

        $sub = null;
        DB::transaction(function () use (&$sub, $u, $plan, $start, $end, $ctx, $amountCents) {
            $sub = Subscription::query()->create([
                'user_id' => $u->id,
                'plan_id' => $plan->id,
                'stripe_customer_id' => $u->stripe_customer_id,
                'currency_code' => $ctx['currency_code'],
                'amount_cents' => $amountCents,
                'status' => 'active',
                'start_at' => $start,
                'end_at' => $end,
            ]);

            // Create initial invoice
            \App\Models\Invoice::create([
                'user_id' => $u->id,
                'subscription_id' => $sub->id,
                'amount_cents' => $amountCents,
                'currency_code' => $ctx['currency_code'],
                'status' => 'paid',
                'provider' => 'stripe',
                'provider_ref' => null,
                'issued_at' => now(),
                'paid_at' => now(),
            ]);
        });

        app(AuditLogger::class)->log($u, 'subscription_created', 'subscription', $sub->id, [
            'plan_id' => $plan->id,
            'currency_code' => $ctx['currency_code'] ?? null,
            'amount_cents' => $amountCents,
            'start_at' => $start->toIso8601String(),
            'end_at' => $end->toIso8601String(),
        ], request());

        return $this->ok($sub->load('plan'), 'Subscription created', 201);
    }

    public function cancel(Subscription $subscription): JsonResponse
    {
        $u = $this->requireAuth();
        if ($u->role !== 'admin' && $subscription->user_id !== $u->id) {
            return $this->fail('Forbidden', null, 403);
        }

        if (in_array($subscription->status, ['cancelled','expired'], true)) {
            return $this->ok($subscription, 'Already inactive');
        }

        $subscription->status = 'cancelled';
        $subscription->cancelled_at = now();
        $subscription->save();

        return $this->ok($subscription, 'Cancelled');
    }

    public function invoices(): JsonResponse
    {
        $u = $this->requireAuth();

        $q = \App\Models\Invoice::query()
            ->with(['subscription.plan'])
            ->orderByDesc('issued_at');

        if ($u->role !== 'admin') {
            if (!in_array($u->role, ['employer','agency'], true)) {
                return $this->fail('Only employer/agency can view invoices', null, 403);
            }
            $q->where('user_id', $u->id);
        }

        return $this->ok($q->paginate(20));
    }
}
