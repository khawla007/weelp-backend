<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationReadStateTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

    public function test_mark_unread_clears_read_at(): void
    {
        $user = User::factory()->create();
        $n = Notification::factory()->read()->create(['user_id' => $user->id]);
        $this->assertNotNull($n->read_at);

        $res = $this->withHeaders($this->authHeader($user))
            ->putJson("/api/notifications/{$n->id}/unread");

        $res->assertOk()->assertJson(['success' => true]);
        $this->assertNull($n->fresh()->read_at);
    }

    public function test_mark_unread_404_for_other_users_notification(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $n = Notification::factory()->read()->create(['user_id' => $owner->id]);

        $res = $this->withHeaders($this->authHeader($other))
            ->putJson("/api/notifications/{$n->id}/unread");

        $res->assertStatus(404);
        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_mark_unread_requires_auth(): void
    {
        $n = Notification::factory()->read()->create();
        $this->putJson("/api/notifications/{$n->id}/unread")->assertUnauthorized();
    }
}
