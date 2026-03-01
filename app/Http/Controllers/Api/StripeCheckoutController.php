<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Illuminate\Support\Facades\Log;

class StripeCheckoutController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create Stripe Checkout Session
     */
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $user = $request->user();
        $plan = Plan::findOrFail($request->plan_id);

        if (!$plan->stripe_price_id) {
            return response()->json([
                'error' => 'Plan not configured for Stripe',
                'message' => 'This plan is not available for purchase.'
            ], 400);
        }

        try {
            // Get or create Stripe customer
            $stripeCustomerId = $this->getOrCreateStripeCustomer($user);

            // Create checkout session
            $session = Session::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $plan->stripe_price_id,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => config('app.frontend_url') . '/billing/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.frontend_url') . '/billing/plans?canceled=true',
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                ],
                'subscription_data' => [
                    'metadata' => [
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                    ],
                ],
            ]);

            return response()->json([
                'session_id' => $session->id,
                'url' => $session->url,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe checkout session creation failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'checkout_failed',
                'message' => 'Failed to create checkout session. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle successful checkout
     */
    public function handleSuccess(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        try {
            $session = Session::retrieve($request->session_id);

            if (!$session || $session->payment_status !== 'paid') {
                return response()->json([
                    'error' => 'invalid_session',
                    'message' => 'Invalid or unpaid session.'
                ], 400);
            }

            $userId = $session->metadata->user_id;
            $planId = $session->metadata->plan_id;

            // Create or update subscription
            $subscription = Subscription::updateOrCreate(
                [
                    'user_id' => $userId,
                    'stripe_subscription_id' => $session->subscription,
                ],
                [
                    'plan_id' => $planId,
                    'stripe_customer_id' => $session->customer,
                    'stripe_price_id' => $session->line_items->data[0]->price->id ?? null,
                    'status' => 'active',
                    'start_at' => now(),
                    'end_at' => null,
                    'current_period_end' => null, // Will be updated by webhook
                ]
            );

            return response()->json([
                'success' => true,
                'subscription' => $subscription,
                'message' => 'Subscription activated successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Checkout success handler failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'processing_failed',
                'message' => 'Failed to process subscription. Please contact support.'
            ], 500);
        }
    }

    /**
     * Create Stripe Customer Portal Session
     */
    public function createPortalSession(Request $request)
    {
        $user = $request->user();

        $subscription = Subscription::where('user_id', $user->id)
            ->whereNotNull('stripe_customer_id')
            ->latest()
            ->first();

        if (!$subscription || !$subscription->stripe_customer_id) {
            return response()->json([
                'error' => 'no_subscription',
                'message' => 'No subscription found.'
            ], 404);
        }

        try {
            $session = \Stripe\BillingPortal\Session::create([
                'customer' => $subscription->stripe_customer_id,
                'return_url' => config('app.frontend_url') . '/billing',
            ]);

            return response()->json([
                'url' => $session->url,
            ]);
        } catch (\Exception $e) {
            Log::error('Portal session creation failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'portal_failed',
                'message' => 'Failed to create portal session.'
            ], 500);
        }
    }

    /**
     * Get or create Stripe customer
     */
    protected function getOrCreateStripeCustomer(User $user): string
    {
        $subscription = Subscription::where('user_id', $user->id)
            ->whereNotNull('stripe_customer_id')
            ->first();

        if ($subscription && $subscription->stripe_customer_id) {
            return $subscription->stripe_customer_id;
        }

        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->employerProfile->organization_name ?? $user->email,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        return $customer->id;
    }
}
