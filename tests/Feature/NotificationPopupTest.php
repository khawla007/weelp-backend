<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationPopupTest extends TestCase
{
    use RefreshDatabase;

    private function header(User $u): array
    {
        return ['Authorization' => 'Bearer '.JWTAuth::fromUser($u)];
    }

    public function test_popup_returns_only_unread_popup_rows_for_caller(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $wanted = Notification::factory()->create(['user_id' => $user->id, 'type' => 'custom', 'display_style' => 'popup', 'read_at' => null]);
        Notification::factory()->create(['user_id' => $user->id, 'type' => 'custom', 'display_style' => 'inline', 'read_at' => null]);
        Notification::factory()->read()->create(['user_id' => $user->id, 'type' => 'custom', 'display_style' => 'popup']);
        Notification::factory()->create(['user_id' => $other->id, 'type' => 'custom', 'display_style' => 'popup', 'read_at' => null]);

        $res = $this->withHeaders($this->header($user))->getJson('/api/notifications/popup');

        $res->assertOk()->assertJson(['success' => true])->assertJsonCount(1, 'data');
        $this->assertSame($wanted->id, $res->json('data.0.id'));
    }

    public function test_popup_requires_auth(): void
    {
        $this->getJson('/api/notifications/popup')->assertUnauthorized();
    }
}
