<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook invalid payload: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received', ['type' => $event->type]);

        try {
            switch ($event->type) {
                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdated($event->data->object);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($event->data->object);
                    break;

                case 'invoice.paid':
                    $this->handleInvoicePaid($event->data->object);
                    break;

                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($event->data->object);
                    break;

                default:
                    Log::info('Unhandled webhook event type: ' . $event->type);
            }
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing error: ' . $e->getMessage(), [
                'event_type' => $event->type,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleSubscriptionUpdated($stripeSubscription)
    {
        try {
            $this->subscriptionService->updateFromStripe([
                'id' => $stripeSubscription->id,
                'status' => $stripeSubscription->status,
                'current_period_end' => $stripeSubscription->current_period_end,
                'items' => [
                    'data' => [
                        ['price' => ['id' => $stripeSubscription->items->data[0]->price->id ?? null]]
                    ]
                ]
            ]);

            Log::info('Subscription updated successfully', [
                'stripe_subscription_id' => $stripeSubscription->id,
                'status' => $stripeSubscription->status
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update subscription: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function handleSubscriptionDeleted($stripeSubscription)
    {
        try {
            $this->subscriptionService->updateFromStripe([
                'id' => $stripeSubscription->id,
                'status' => 'canceled',
                'current_period_end' => $stripeSubscription->current_period_end,
                'items' => [
                    'data' => [
                        ['price' => ['id' => $stripeSubscription->items->data[0]->price->id ?? null]]
                    ]
                ]
            ]);

            Log::info('Subscription deleted', ['stripe_subscription_id' => $stripeSubscription->id]);
        } catch (\Exception $e) {
            Log::error('Failed to handle subscription deletion: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function handleInvoicePaid($invoice)
    {
        Log::info('Invoice paid', [
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription
        ]);
    }

    protected function handleInvoicePaymentFailed($invoice)
    {
        Log::warning('Invoice payment failed', [
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription
        ]);
    }
}
