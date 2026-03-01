<?php

namespace App\Services\RateLimiter;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class TokenBucketLimiter
{
    protected CacheRepository $cache;
    protected bool $redisAvailable;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cache = $cacheManager->store(config('cache.default'));
        $this->redisAvailable = $this->detectRedis();
    }

    protected function detectRedis(): bool
    {
        try {
            // If Redis facade is configured and can ping, consider available
            if (class_exists(\Illuminate\Support\Facades\Redis::class)) {
                Redis::connection()->client();
                return true;
            }
        } catch (Throwable $e) {
            // fall through
        }
        return false;
    }

    /**
     * Consume one token from the bucket.
     *
     * @return array{allowed:bool, remaining:int, limit:int, reset:int}
     */
    public function consume(string $key, int $limit, int $windowSeconds, int $burst = 0): array
    {
        $capacity = max(1, $limit + max(0, $burst));
        $nowMs = (int) round(microtime(true) * 1000);
        $ratePerMs = $limit / ($windowSeconds * 1000);

        if ($this->redisAvailable) {
            return $this->consumeWithCache($key, $capacity, $ratePerMs, $nowMs, $limit, $windowSeconds);
        }

        // Fallback to cache (non-redis); not strictly atomic but acceptable for single-instance or degraded mode
        return $this->consumeWithCache($key, $capacity, $ratePerMs, $nowMs, $limit, $windowSeconds);
    }

    protected function bucketCacheKeys(string $key): array
    {
        $base = "rate:bucket:" . $key;
        return [$base . ":tokens", $base . ":ts"];
    }

    protected function consumeWithCache(string $key, int $capacity, float $ratePerMs, int $nowMs, int $limit, int $windowSeconds): array
    {
        [$tokensKey, $tsKey] = $this->bucketCacheKeys($key);
        $tokens = (float) ($this->cache->get($tokensKey, $capacity));
        $lastTs = (int) ($this->cache->get($tsKey, $nowMs));

        $delta = max(0, $nowMs - $lastTs);
        $refill = $delta * $ratePerMs;
        $tokens = min($capacity, $tokens + $refill);
        $allowed = false;
        $remaining = (int) floor($tokens);
        $reset = 0;

        if ($tokens >= 1.0) {
            $tokens -= 1.0;
            $allowed = true;
            $remaining = (int) floor($tokens);
        } else {
            // seconds until next full token
            $needed = 1.0 - $tokens;
            $reset = (int) ceil($needed / $ratePerMs / 1000);
        }

        // Persist with TTL slightly larger than window
        $ttl = $windowSeconds + 5;
        $this->cache->put($tokensKey, $tokens, $ttl);
        $this->cache->put($tsKey, $nowMs, $ttl);

        return [
            'allowed' => $allowed,
            'remaining' => max(0, $remaining),
            'limit' => $limit,
            'reset' => max(0, $reset),
        ];
    }
}

