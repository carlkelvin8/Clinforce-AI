<?php

namespace App\Http\Middleware;

use App\Services\RateLimiter\TokenBucketLimiter;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    public function __construct(protected TokenBucketLimiter $limiter)
    {
    }

    public function handle(Request $request, Closure $next, ...$params): Response
    {
        if (!config('ratelimit.enabled', true)) {
            return $next($request);
        }

        $user = $request->user();
        $role = $user?->role ?: 'guest';
        $isAuthed = (bool) $user;

        // Defaults from config
        $window = (int) config('ratelimit.window_seconds', 60);
        $defaultAuth = config('ratelimit.roles.authenticated');
        $defaultAnon = config('ratelimit.roles.anonymous');
        $limit = (int) ($isAuthed ? ($defaultAuth['limit'] ?? 100) : ($defaultAnon['limit'] ?? 20));
        $burst = (int) ($isAuthed ? ($defaultAuth['burst'] ?? 50) : ($defaultAnon['burst'] ?? 10));

        // Parse overrides from middleware params: e.g., "limit=200,window=60,burst=20"
        if (!empty($params)) {
            $merged = implode(',', $params);
            foreach (explode(',', $merged) as $pair) {
                $pair = trim($pair);
                if ($pair === '') continue;
                if (!str_contains($pair, '=')) continue;
                [$k, $v] = array_map('trim', explode('=', $pair, 2));
                if ($k === 'limit') $limit = max(1, (int) $v);
                if ($k === 'window') $window = max(1, (int) $v);
                if ($k === 'burst') $burst = max(0, (int) $v);
            }
        }

        // Build endpoint key (by route name if present, otherwise method+path)
        $routeName = $request->route()?->getName();
        $endpoint = $routeName ?: ($request->method() . ':' . preg_replace('#\d+#', '*', $request->path()));

        // Identifiers
        $ip = $request->ip() ?: '0.0.0.0';
        $userKey = $isAuthed ? "user:" . $user->id : null;
        $ipKey = "ip:" . $ip;

        // Enforce both user-based (if authed) and IP-based rate limits
        $results = [];
        if ($userKey) {
            $results[] = $this->limiter->consume("{$endpoint}|{$userKey}", $limit, $window, $burst);
        }
        // Apply IP limiter with same window but stricter limit for anonymous traffic baseline
        $ipLimit = $isAuthed ? max(10, (int) floor($limit * 0.8)) : $limit; // slight reduction for IP even if authed
        $results[] = $this->limiter->consume("{$endpoint}|{$ipKey}", $ipLimit, $window, (int) floor($burst / 2));

        // Combine results: request allowed only if all are allowed
        $allowed = collect($results)->every(fn ($r) => $r['allowed']);
        $combinedLimit = min(array_column($results, 'limit'));
        $combinedRemaining = min(array_column($results, 'remaining'));
        $combinedReset = max(array_column($results, 'reset'));

        if (!$allowed) {
            $payload = [
                'message' => 'Too Many Requests: rate limit exceeded.',
                'errors' => null,
            ];
            $resp = new JsonResponse($payload, 429);
            if (config('ratelimit.headers.enabled', true)) {
                $resp->headers->set('X-RateLimit-Limit', (string) $combinedLimit);
                $resp->headers->set('X-RateLimit-Remaining', '0');
                $resp->headers->set('X-RateLimit-Reset', (string) $combinedReset);
            }
            Log::warning('API rate limit exceeded', [
                'endpoint' => $endpoint,
                'role' => $role,
                'user_id' => $user?->id,
                'ip' => $ip,
                'limit' => $combinedLimit,
                'remaining' => $combinedRemaining,
                'reset' => $combinedReset,
            ]);
            return $resp;
        }

        /** @var Response $response */
        $response = $next($request);

        if (config('ratelimit.headers.enabled', true)) {
            $response->headers->set('X-RateLimit-Limit', (string) $combinedLimit);
            $response->headers->set('X-RateLimit-Remaining', (string) max(0, $combinedRemaining));
            $response->headers->set('X-RateLimit-Reset', (string) $combinedReset);
        }

        return $response;
    }
}

