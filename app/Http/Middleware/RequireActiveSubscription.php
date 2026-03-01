<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireActiveSubscription
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Must be logged in
        if (!$user) {
            return $this->respondUnauthorized($request);
        }

        // Only employers need subscriptions
        if ($user->role !== 'employer') {
            return $next($request);
        }

        // Check for active subscription
        $subscription = $user->subscription;

        if (!$subscription) {
            return $this->respondSubscriptionRequired($request);
        }

        // Validate subscription status
        if ($subscription->status !== 'active') {
            return $this->respondSubscriptionRequired($request);
        }

        // Validate period end if set
        if ($subscription->current_period_end && $subscription->current_period_end->isPast()) {
            return $this->respondSubscriptionRequired($request);
        }

        return $next($request);
    }

    /**
     * Respond with unauthorized error
     */
    protected function respondUnauthorized(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication required.',
            ], 401);
        }

        return redirect()->route('login');
    }

    /**
     * Respond with subscription required error
     */
    protected function respondSubscriptionRequired(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'code' => 'SUBSCRIPTION_REQUIRED',
                'message' => 'Subscription required to view candidate details.',
            ], 402);
        }

        return redirect('/billing/plans')->with('sub_required', 1);
    }
}
