<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionService
{
    /**
     * Check if a user (employer/agency) has active access.
     * Includes a 24-hour grace period after expiry.
     * Admins and applicants are always allowed.
     */
    public function hasAccess(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) return false;

        // Non-paying roles always pass
        if (in_array($user->role, ['admin', 'applicant'], true)) return true;

        return $this->hasActiveSubscription($userId) || $this->isInGracePeriod($userId);
    }

    /**
     * 24-hour grace period after subscription expires.
     * Allows mid-session work to continue without abrupt lockout.
     */
    public function isInGracePeriod(int $userId): bool
    {
        return Subscription::where('user_id', $userId)
            ->whereIn('status', ['active', 'expired'])
            ->where(function ($q) {
                $graceEnd = now()->subHours(24);
                $q->where(function ($inner) use ($graceEnd) {
                    $inner->whereNotNull('end_at')
                          ->where('end_at', '<=', now())
                          ->where('end_at', '>', $graceEnd);
                })->orWhere(function ($inner) use ($graceEnd) {
                    $inner->whereNotNull('current_period_end')
                          ->where('current_period_end', '<=', now())
                          ->where('current_period_end', '>', $graceEnd);
                });
            })
            ->exists();
    }

    /**
     * Check for a currently active paid subscription.
     * Uses end_at (our column) — falls back to current_period_end for Stripe-synced rows.
     */
    public function hasActiveSubscription(int $userId): bool
    {
        return Subscription::where('user_id', $userId)
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($q) {
                $q->where(function ($inner) {
                    // Our column
                    $inner->whereNotNull('end_at')->where('end_at', '>', now());
                })->orWhere(function ($inner) {
                    // Stripe-synced column
                    $inner->whereNotNull('current_period_end')->where('current_period_end', '>', now());
                })->orWhere(function ($inner) {
                    // Both null = no expiry set yet, treat as active
                    $inner->whereNull('end_at')->whereNull('current_period_end');
                });
            })
            ->exists();
    }

    /** Get the active subscription with plan loaded. */
    public function getActiveSubscription(int $userId): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('end_at')->where('end_at', '>', now());
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('current_period_end')->where('current_period_end', '>', now());
                })->orWhere(function ($inner) {
                    $inner->whereNull('end_at')->whereNull('current_period_end');
                });
            })
            ->with('plan')
            ->first();
    }

    /** Check if employer can post jobs (needs active subscription). */
    public function canPostJobs(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) return false;
        if (in_array($user->role, ['admin'], true)) return true;
        return $this->hasActiveSubscription($userId);
    }

    /** Check if employer can use AI screening. */
    public function canUseAiScreening(int $userId): bool
    {
        $sub = $this->getActiveSubscription($userId);
        if (!$sub) return false;
        return (bool) ($sub->plan?->ai_screening_enabled ?? false);
    }

    /** Check if employer can send invitations. */
    public function canSendInvitations(int $userId): bool
    {
        return $this->hasActiveSubscription($userId);
    }
}
