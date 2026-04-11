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

        // Only employers and agencies need subscriptions
        if (!in_array($user->role, ['employer', 'agency'], true)) {
            return $next($request);
        }

        // 1. Check for active trial (platform trial)
        if ($user->onTrial()) {
            return $next($request);
        }

        // 2. Check for active subscription (paid/stripe)
        // Use the subscription relationship or service logic
        $subscription = $user->subscription; // This uses the model relation which checks for active status

        if ($subscription && ($subscription->status === 'active' || $subscription->status === 'trialing')) {
            // Also validate period end
            if (!$subscription->current_period_end || $subscription->current_period_end->isFuture()) {
                return $next($request);
            }
        }

        return $this->respondSubscriptionRequired($request);
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
