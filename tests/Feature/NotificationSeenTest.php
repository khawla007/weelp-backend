<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationSeenTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

    public function test_unread_count_falls_back_to_all_unread_when_never_seen(): void
    {
        $user = User::factory()->create(['notifications_last_seen_at' => null]);
        Notification::factory()->count(3)->create(['user_id' => $user->id]);
        Notification::factory()->read()->create(['user_id' => $user->id]);

        $res = $this->withHeaders($this->authHeader($user))->getJson('/api/notifications/unread-count');

        $res->assertOk()->assertJson(['success' => true, 'count' => 3]);
    }

    public function test_seen_endpoint_stamps_last_seen_and_zeroes_count(): void
    {
        $user = User::factory()->create(['notifications_last_seen_at' => null]);
        Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'created_at' => now()->subMinutes(5),
        ]);

        $seen = $this->withHeaders($this->authHeader($user))->postJson('/api/notifications/seen');
        $seen->assertOk()->assertJson(['success' => true]);

        $this->assertNotNull($user->fresh()->notifications_last_seen_at);

        $res = $this->withHeaders($this->authHeader($user))->getJson('/api/notifications/unread-count');
        $res->assertOk()->assertJson(['count' => 0]);
    }

    public function test_notifications_arriving_after_seen_still_count(): void
    {
        $user = User::factory()->create(['notifications_last_seen_at' => now()]);
        Notification::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->addMinute(),
        ]);

        $res = $this->withHeaders($this->authHeader($user))->getJson('/api/notifications/unread-count');
        $res->assertOk()->assertJson(['count' => 1]);
    }

    public function test_read_notifications_never_count_even_if_after_seen(): void
    {
        $user = User::factory()->create(['notifications_last_seen_at' => now()->subDay()]);
        Notification::factory()->read()->create([
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $res = $this->withHeaders($this->authHeader($user))->getJson('/api/notifications/unread-count');
        $res->assertOk()->assertJson(['count' => 0]);
    }

    public function test_seen_requires_auth(): void
    {
        $this->postJson('/api/notifications/seen')->assertUnauthorized();
    }
}
