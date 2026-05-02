<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('verify_email');
    }

    public function test_verify_email_returns_429_after_5_requests_per_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/api/verify-email?token=invalid');
            $this->assertNotSame(429, $response->status(), "Request {$i} should not be throttled");
        }

        $response = $this->getJson('/api/verify-email?token=invalid');
        $response->assertStatus(429);
    }

    public function test_resend_verification_returns_429_after_5_requests_per_minute(): void
    {
        $email = 'rate-limit-target@example.com';

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/resend-verification', ['email' => $email]);
            $this->assertNotSame(429, $response->status(), "Request {$i} should not be throttled");
        }

        $response = $this->postJson('/api/resend-verification', ['email' => $email]);
        $response->assertStatus(429);
    }

    public function test_user_profile_returns_429_after_30_requests_per_minute(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 30; $i++) {
            $response = $this->actingAs($user, 'api')->getJson('/api/user/profile');
            $this->assertNotSame(429, $response->status(), "Request {$i} should not be throttled");
        }

        $response = $this->actingAs($user, 'api')->getJson('/api/user/profile');
        $response->assertStatus(429);
    }

    public function test_avatar_upload_returns_429_after_10_requests_per_minute(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user, 'api')->postJson('/api/user/avatar', []);
            $this->assertNotSame(429, $response->status(), "Request {$i} should not be throttled");
        }

        $response = $this->actingAs($user, 'api')->postJson('/api/user/avatar', []);
        $response->assertStatus(429);
    }

    public function test_admin_route_returns_429_after_60_requests_per_minute(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 0; $i < 60; $i++) {
            $response = $this->actingAs($admin, 'api')->getJson('/api/admin/dashboard/metrics');
            $this->assertNotSame(429, $response->status(), "Request {$i} should not be throttled");
        }

        $response = $this->actingAs($admin, 'api')->getJson('/api/admin/dashboard/metrics');
        $response->assertStatus(429);
    }

    public function test_admin_throttle_does_not_count_unauthenticated_requests(): void
    {
        for ($i = 0; $i < 65; $i++) {
            $response = $this->getJson('/api/admin/dashboard/metrics');
            $response->assertStatus(401);
        }
    }

    public function test_test_route_is_removed(): void
    {
        $response = $this->getJson('/api/test');
        $response->assertStatus(404);
    }
}
