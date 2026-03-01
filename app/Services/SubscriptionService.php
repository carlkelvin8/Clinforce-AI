<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionService
{
    /**
     * Check if user has an active subscription
     */
    public function hasActiveSubscription(int $userId): bool
    {
        $subscription = Subscription::where('user_id', $userId)
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', Carbon::now());
            })
            ->first();

        return $subscription !== null;
    }

    /**
     * Get active subscription for user
     */
    public function getActiveSubscription(int $userId): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', Carbon::now());
            })
            ->with('plan')
            ->first();
    }

    /**
     * Check if user can access applicant details
     */
    public function canAccessApplicantDetails(int $userId): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }

        // Applicants and agencies can always access
        if (in_array($user->role, ['applicant', 'agency', 'admin'])) {
            return true;
        }

        // Employers need active subscription
        if ($user->role === 'employer') {
            return $this->hasActiveSubscription($userId);
        }

        return false;
    }

    /**
     * Get subscription status details
     */
    public function getSubscriptionStatus(int $userId): array
    {
        $subscription = $this->getActiveSubscription($userId);

        if (!$subscription) {
            return [
                'has_subscription' => false,
                'status' => 'none',
                'plan_name' => null,
                'expires_at' => null,
            ];
        }

        return [
            'has_subscription' => true,
            'status' => $subscription->status,
            'plan_name' => $subscription->plan->name ?? null,
            'expires_at' => $subscription->current_period_end?->toIso8601String(),
            'stripe_subscription_id' => $subscription->stripe_subscription_id,
        ];
    }

    /**
     * Update subscription from Stripe webhook
     */
    public function updateFromStripe(array $stripeData): Subscription
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeData['id'])->first();

        if (!$subscription) {
            throw new \Exception('Subscription not found');
        }

        $subscription->update([
            'status' => $stripeData['status'],
            'current_period_end' => Carbon::createFromTimestamp($stripeData['current_period_end']),
            'stripe_price_id' => $stripeData['items']['data'][0]['price']['id'] ?? null,
        ]);

        return $subscription;
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(int $subscriptionId): bool
    {
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return false;
        }

        $subscription->update([
            'status' => 'canceled',
            'cancelled_at' => Carbon::now(),
        ]);

        return true;
    }
}
