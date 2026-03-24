<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage:
 *   Route::middleware('subscription')           — requires any active subscription
 *   Route::middleware('subscription:ai')        — requires active subscription with ai_screening_enabled
 */
class EnforceSubscription
{
    public function __construct(protected SubscriptionService $svc) {}

    public function handle(Request $request, Closure $next, string $feature = 'basic'): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Admins and applicants are never gated
        if (in_array($user->role, ['admin', 'applicant'], true)) {
            return $next($request);
        }

        $allowed = match ($feature) {
            'ai'          => $this->svc->canUseAiScreening($user->id),
            'invite'      => $this->svc->canSendInvitations($user->id),
            'jobs'        => $this->svc->canPostJobs($user->id),
            default       => $this->svc->hasAccess($user->id),
        };

        if ($allowed) {
            return $next($request);
        }

        return response()->json([
            'error'   => 'subscription_required',
            'feature' => $feature,
            'message' => match ($feature) {
                'ai'     => 'AI screening requires an active subscription with AI features enabled.',
                'invite' => 'Sending invitations requires an active subscription.',
                'jobs'   => 'Posting jobs requires an active subscription.',
                default  => 'An active subscription is required to use this feature.',
            },
        ], 403);
    }
}
