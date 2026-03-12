<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionService
{
    /**
     * Check if user has access (either active subscription or active trial)
     */
    public function hasAccess(int $userId): bool
    {
        // 1. Check paid subscription first (most robust)
        if ($this->hasActiveSubscription($userId)) {
            return true;
        }

        // 2. Check for active trial
        $user = User::find($userId);
        if ($user && $user->onTrial()) {
            return true;
        }

        return false;
    }

    /**
     * Check if user has an active subscription (paid)
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
        $user = User::find($userId);
        $subscription = $this->getActiveSubscription($userId);

        $status = 'none';
        $expiresAt = null;
        $planName = null;

        if ($subscription) {
            $status = $subscription->status;
            $planName = $subscription->plan->name ?? 'Standard Plan';
            $expiresAt = $subscription->current_period_end?->toIso8601String();
        } elseif ($user) {
            if ($user->onTrial()) {
                $status = 'trial_active';
                $planName = 'Free Trial (7 Days)';
                $expiresAt = $user->trial_ends_at?->toIso8601String();
            } elseif ($user->hasExpiredTrial()) {
                $status = 'trial_expired';
                $planName = 'Trial Expired';
                $expiresAt = $user->trial_ends_at?->toIso8601String();
            }
        }

        return [
            'has_subscription' => (bool)$subscription,
            'has_access' => (bool)$subscription || ($user && $user->onTrial()),
            'on_trial' => $user ? $user->onTrial() : false,
            'trial_ends_at' => $user?->trial_ends_at?->toIso8601String(),
            'status' => $status,
            'plan_name' => $planName,
            'expires_at' => $expiresAt,
            'stripe_subscription_id' => $subscription->stripe_subscription_id ?? null,
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
