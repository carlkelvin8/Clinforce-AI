<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\SetupIntent;
use Stripe\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PaymentMethodController extends ApiController
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create Setup Intent for adding payment method
     */
    public function createSetupIntent(Request $request): JsonResponse
    {
        $user = $this->requireAuth();

        Log::info('Creating setup intent', [
            'user_id' => $user->id,
            'current_stripe_customer_id' => $user->stripe_customer_id,
        ]);

        try {
            // Get or create Stripe customer
            $stripeCustomerId = $this->getOrCreateStripeCustomer($user);

            Log::info('Stripe customer obtained', [
                'user_id' => $user->id,
                'stripe_customer_id' => $stripeCustomerId,
            ]);

            // Create setup intent
            $setupIntent = SetupIntent::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'usage' => 'off_session',
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);

            Log::info('Setup intent created', [
                'setup_intent_id' => $setupIntent->id,
                'customer_id' => $stripeCustomerId,
            ]);

            return $this->ok([
                'client_secret' => $setupIntent->client_secret,
                'customer_id' => $stripeCustomerId,
            ]);
        } catch (\Exception $e) {
            Log::error('Setup intent creation failed: ' . $e->getMessage());
            return $this->fail('Failed to initialize payment setup', null, 500);
        }
    }

    /**
     * Get user's payment methods
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->requireAuth();

        try {
            $stripeCustomerId = $this->getStripeCustomerId($user);

            if (!$stripeCustomerId) {
                return $this->ok([
                    'has_payment_method' => false,
                    'payment_methods' => [],
                ]);
            }

            $paymentMethods = PaymentMethod::all([
                'customer' => $stripeCustomerId,
                'type' => 'card',
            ]);

            $methods = array_map(function ($pm) {
                return [
                    'id' => $pm->id,
                    'brand' => $pm->card->brand,
                    'last4' => $pm->card->last4,
                    'exp_month' => $pm->card->exp_month,
                    'exp_year' => $pm->card->exp_year,
                    'is_default' => true, // You can track default in your DB
                ];
            }, $paymentMethods->data);

            return $this->ok([
                'has_payment_method' => count($methods) > 0,
                'payment_methods' => $methods,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch payment methods: ' . $e->getMessage());
            return $this->fail('Failed to fetch payment methods', null, 500);
        }
    }

    /**
     * Confirm payment method was added
     */
    public function confirm(Request $request): JsonResponse
    {
        Log::info('Payment method confirm called', [
            'setup_intent_id' => $request->input('setup_intent_id'),
            'user_id' => auth()->id(),
        ]);

        $request->validate([
            'setup_intent_id' => 'required|string',
        ]);

        $user = $this->requireAuth();

        try {
            $setupIntent = SetupIntent::retrieve($request->setup_intent_id);

            Log::info('Setup intent retrieved', [
                'status' => $setupIntent->status,
                'customer' => $setupIntent->customer,
                'payment_method' => $setupIntent->payment_method,
            ]);

            if ($setupIntent->status !== 'succeeded') {
                Log::warning('Setup intent not succeeded', ['status' => $setupIntent->status]);
                return $this->fail('Payment method setup not completed', null, 400);
            }

            // Update user's stripe_customer_id if not set
            $oldCustomerId = $user->stripe_customer_id;
            if (!$user->stripe_customer_id) {
                $user->stripe_customer_id = $setupIntent->customer;
                $saved = $user->save();
                
                Log::info('Updated stripe_customer_id', [
                    'user_id' => $user->id,
                    'old_customer_id' => $oldCustomerId,
                    'new_customer_id' => $user->stripe_customer_id,
                    'saved' => $saved,
                ]);
            } else {
                Log::info('User already has stripe_customer_id', [
                    'user_id' => $user->id,
                    'stripe_customer_id' => $user->stripe_customer_id,
                ]);
            }

            return $this->ok([
                'message' => 'Payment method added successfully',
                'payment_method_id' => $setupIntent->payment_method,
                'stripe_customer_id' => $user->stripe_customer_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Payment method confirmation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->fail('Failed to confirm payment method', null, 500);
        }
    }

    /**
     * Remove payment method
     */
    public function destroy(Request $request, string $paymentMethodId): JsonResponse
    {
        $user = $this->requireAuth();

        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);

            // Verify ownership
            $stripeCustomerId = $this->getStripeCustomerId($user);
            if ($paymentMethod->customer !== $stripeCustomerId) {
                return $this->fail('Unauthorized', null, 403);
            }

            $paymentMethod->detach();

            return $this->ok(['message' => 'Payment method removed']);
        } catch (\Exception $e) {
            Log::error('Payment method removal failed: ' . $e->getMessage());
            return $this->fail('Failed to remove payment method', null, 500);
        }
    }

    /**
     * Get or create Stripe customer
     */
    protected function getOrCreateStripeCustomer(User $user): string
    {
        // Check if user already has stripe_customer_id
        if ($user->stripe_customer_id) {
            Log::info('User already has Stripe customer', [
                'user_id' => $user->id,
                'stripe_customer_id' => $user->stripe_customer_id,
            ]);
            return $user->stripe_customer_id;
        }

        Log::info('Creating new Stripe customer', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        // Create new customer
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->employerProfile->organization_name ?? $user->email,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        Log::info('Stripe customer created', [
            'user_id' => $user->id,
            'stripe_customer_id' => $customer->id,
        ]);

        // Save to user
        $user->stripe_customer_id = $customer->id;
        $saved = $user->save();

        Log::info('Saved stripe_customer_id to user', [
            'user_id' => $user->id,
            'stripe_customer_id' => $user->stripe_customer_id,
            'saved' => $saved,
        ]);

        // Refresh user from database to verify
        $user->refresh();
        Log::info('User refreshed from database', [
            'user_id' => $user->id,
            'stripe_customer_id' => $user->stripe_customer_id,
        ]);

        return $customer->id;
    }

    /**
     * Get Stripe customer ID
     */
    protected function getStripeCustomerId(User $user): ?string
    {
        return $user->stripe_customer_id;
    }
}
