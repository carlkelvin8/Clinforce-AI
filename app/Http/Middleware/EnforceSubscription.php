<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSubscription
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Admins, Applicants, and Agencies are usually exempt from paid subscriptions in this model
        // (unless agencies pay too, but usually it's employers who pay for access)
        if (in_array($user->role, ['admin', 'applicant'])) {
            return $next($request);
        }

        // If agency needs to pay, include them here. Assuming only Employers pay for now based on context.
        // If agency role is free, skip.
        if ($user->role === 'agency') {
            // Check if agency needs subscription? Assuming free for now based on typical models or previous code context.
            // If they need to pay, remove this check.
            // Let's assume they are free for now unless specified otherwise.
            return $next($request);
        }

        // Check for active subscription or active trial
        if ($this->subscriptionService->hasAccess($user->id)) {
            return $next($request);
        }

        return response()->json([
            'error' => 'subscription_required',
            'message' => 'Your free trial has ended. Please subscribe to continue accessing this feature.',
            'trial_expired' => $user->hasExpiredTrial(),
        ], 403);
    }
}
