<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure feature is enabled and deterministic window
        Config::set('ratelimit.enabled', true);
        Config::set('ratelimit.window_seconds', 60);
        Config::set('ratelimit.roles.anonymous.limit', 5);
        Config::set('ratelimit.roles.anonymous.burst', 0);
        Config::set('ratelimit.roles.authenticated.limit', 10);
        Config::set('ratelimit.roles.authenticated.burst', 0);
    }

    public function test_anonymous_is_limited_and_returns_headers(): void
    {
        // /api/health is public
        $ok = 0;
        $blocked = 0;
        for ($i = 0; $i < 7; $i++) {
            $resp = $this->getJson('/api/health');
            if ($resp->status() === 200) {
                $ok++;
                $resp->assertHeader('X-RateLimit-Limit');
                $resp->assertHeader('X-RateLimit-Remaining');
                $resp->assertHeader('X-RateLimit-Reset');
            } else {
                $blocked++;
                $resp->assertStatus(429);
                $resp->assertJsonStructure(['message']);
            }
        }
        $this->assertGreaterThan(0, $ok);
        $this->assertGreaterThan(0, $blocked);
    }

    public function test_authenticated_gets_higher_limits(): void
    {
        $user = User::factory()->create([
            'role' => 'applicant',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        // /api/me requires auth
        $resp = $this->getJson('/api/me');
        $resp->assertStatus(200);
        $resp->assertHeader('X-RateLimit-Limit', (string) config('ratelimit.roles.authenticated.limit'));
    }
}

