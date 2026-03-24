<?php
// app/Http/Controllers/Api/SubscriptionsController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\SubscriptionStoreRequest;
use App\Mail\InvoiceIssued;
use App\Mail\SubscriptionConfirmation;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
        // Country check removed - defaults to USD if not set

        $isTrial = (int) $plan->price_cents === 0;

        $paymentMethods = null;

        if (!$isTrial) {
            // Check if user has payment method (only required for paid plans)
            if (!$u->stripe_customer_id) {
                return $this->fail(
                    'Please add a payment method before subscribing.',
                    ['payment_method' => ['Payment method required']],
                    422
                );
            }

            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                $paymentMethods = \Stripe\PaymentMethod::all([
                    'customer' => $u->stripe_customer_id,
                    'type' => 'card',
                ]);

                if (count($paymentMethods->data) === 0) {
                    return $this->fail(
                        'Please add a payment method before subscribing.',
                        ['payment_method' => ['No payment method found']],
                        422
                    );
                }
            } catch (\Exception $e) {
                \Log::error('Stripe error', ['error' => $e->getMessage()]);
                return $this->fail('Failed to verify payment method: ' . $e->getMessage(), null, 500);
            }
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

        $isFree = (int) $amountCents === 0;

        $start = isset($v['start_at']) ? now()->parse($v['start_at']) : now();
        // duration_months = 0 means a 7-day trial
        $end = $plan->duration_months === 0
            ? (clone $start)->addDays(7)
            : (clone $start)->addMonths((int) $plan->duration_months);

        $sub = null;

        try {
            DB::transaction(function () use (&$sub, $u, $plan, $start, $end, $ctx, $amountCents, $isFree, $paymentMethods) {
                $intentId = null;

                if (!$isFree) {
                    // Charge the card via Stripe PaymentIntent
                    $paymentMethod = $paymentMethods->data[0];

                    $intent = \Stripe\PaymentIntent::create([
                        'amount' => $amountCents,
                        'currency' => strtolower($ctx['currency_code']),
                        'customer' => $u->stripe_customer_id,
                        'payment_method' => $paymentMethod->id,
                        'confirm' => true,
                        'off_session' => true,
                        'description' => "Subscription: {$plan->name}",
                        'metadata' => [
                            'user_id' => $u->id,
                            'plan_id' => $plan->id,
                        ],
                    ]);

                    if ($intent->status !== 'succeeded') {
                        throw new \RuntimeException('Payment did not succeed. Status: ' . $intent->status);
                    }

                    $intentId = $intent->id;
                }

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

                // Create invoice (free trials get a $0 invoice, no Stripe ref)
                \App\Models\Invoice::create([
                    'user_id' => $u->id,
                    'subscription_id' => $sub->id,
                    'amount_cents' => $amountCents,
                    'currency_code' => $ctx['currency_code'],
                    'status' => 'paid',
                    'provider' => 'stripe',
                    'provider_ref' => $intentId ?? ('free_trial_' . uniqid()),
                    'issued_at' => now(),
                    'paid_at' => now(),
                ]);
            });
        } catch (\Stripe\Exception\CardException $e) {
            return $this->fail('Card declined: ' . $e->getMessage(), ['stripe' => $e->getMessage()], 402);
        } catch (\Exception $e) {
            \Log::error('Subscription charge failed', ['error' => $e->getMessage()]);
            return $this->fail('Payment failed: ' . $e->getMessage(), null, 500);
        }

        app(AuditLogger::class)->log($u, 'subscription_created', 'subscription', $sub->id, [
            'plan_id' => $plan->id,
            'currency_code' => $ctx['currency_code'] ?? null,
            'amount_cents' => $amountCents,
            'start_at' => $start->toIso8601String(),
            'end_at' => $end->toIso8601String(),
        ], request());

        // Send confirmation + invoice emails
        try {
            $sub->load('plan');
            $invoice = $sub->invoices()->latest()->first();
            Mail::to($u->email)->send(new SubscriptionConfirmation($sub));
            if ($invoice) {
                Mail::to($u->email)->send(new InvoiceIssued($invoice));
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to send subscription emails', ['error' => $e->getMessage()]);
        }

        return $this->ok($sub->load('plan'), 'Subscription created', 201);
    }

    public function usage(): JsonResponse
    {
        $u = $this->requireAuth();

        $sub = Subscription::query()
            ->with('plan')
            ->where('user_id', $u->id)
            ->whereIn('status', ['active', 'past_due'])
            ->where('end_at', '>', now())
            ->latest('id')
            ->first();

        if (!$sub || !$sub->plan) {
            return $this->ok([
                'plan_name' => null,
                'job_post_limit' => null,
                'jobs_used' => 0,
            ]);
        }

        $jobsUsed = \App\Models\Job::query()
            ->where('owner_user_id', $u->id)
            ->whereIn('status', ['published', 'open', 'active'])
            ->count();

        return $this->ok([
            'plan_name' => $sub->plan->name,
            'job_post_limit' => $sub->plan->job_post_limit,
            'jobs_used' => $jobsUsed,
        ]);
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

    public function downloadInvoice(\Illuminate\Http\Request $request, int $invoiceId): \Illuminate\Http\Response
    {
        $u = $this->requireAuth();

        $invoice = \App\Models\Invoice::with(['subscription.plan'])->findOrFail($invoiceId);

        if ($u->role !== 'admin' && (int)$invoice->user_id !== (int)$u->id) {
            abort(403);
        }

        $plan = $invoice->subscription?->plan;
        $html = view('invoices.download', compact('invoice', 'plan', 'u'))->render();

        return response($html, 200)->header('Content-Type', 'text/html');
    }
}
