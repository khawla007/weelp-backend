<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationComposeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function header(User $u): array
    {
        return ['Authorization' => 'Bearer '.JWTAuth::fromUser($u)];
    }

    public function test_single_user_compose_inserts_one_custom_notification(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['role' => 'customer']);

        $res = $this->withHeaders($this->header($admin))->postJson('/api/admin/notifications', [
            'title' => 'Hello',
            'message' => "Line one\nLine two",
            'action_url' => '/dashboard/customer/earnings',
            'target_type' => 'user',
            'target_user_id' => $target->id,
        ]);

        $res->assertOk()->assertJson(['success' => true, 'count' => 1]);
        $n = Notification::where('user_id', $target->id)->first();
        $this->assertNotNull($n);
        $this->assertSame('custom', $n->type);
        $this->assertSame($admin->id, $n->created_by);
        $this->assertSame('/dashboard/customer/earnings', $n->action_url);
    }

    public function test_role_segment_compose_inserts_one_row_per_matching_user(): void
    {
        $admin = $this->admin();
        User::factory()->count(3)->create(['role' => 'customer']);
        User::factory()->create(['role' => 'admin']);

        $res = $this->withHeaders($this->header($admin))->postJson('/api/admin/notifications', [
            'title' => 'Promo',
            'message' => 'Body',
            'target_type' => 'role',
            'target_role' => 'customer',
        ]);

        $res->assertOk()->assertJson(['success' => true, 'count' => 3]);
        $this->assertSame(3, Notification::where('type', 'custom')->count());
    }

    public function test_creator_segment_filters_by_is_creator(): void
    {
        $admin = $this->admin();
        User::factory()->create(['role' => 'customer', 'is_creator' => true]);
        User::factory()->create(['role' => 'customer', 'is_creator' => false]);

        $res = $this->withHeaders($this->header($admin))->postJson('/api/admin/notifications', [
            'title' => 'Creators only',
            'message' => 'Body',
            'target_type' => 'role',
            'target_role' => 'creator',
        ]);

        $res->assertOk()->assertJson(['count' => 1]);
    }

    public function test_images_snapshot_to_data_images_urls(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();
        $m1 = Media::create(['name' => 'a', 'url' => 'https://cdn/a.jpg']);
        $m2 = Media::create(['name' => 'b', 'url' => 'https://cdn/b.jpg']);

        $res = $this->withHeaders($this->header($admin))->postJson('/api/admin/notifications', [
            'title' => 'Pics',
            'message' => 'Body',
            'image_media_ids' => [$m1->id, $m2->id],
            'target_type' => 'user',
            'target_user_id' => $target->id,
        ]);

        $res->assertOk();
        $n = Notification::where('user_id', $target->id)->first();
        $expected = Media::whereIn('id', [$m1->id, $m2->id])->get()->pluck('url')->all();
        $this->assertSame($expected, $n->data['images']);
        $this->assertCount(2, $n->data['images']);
    }

    public function test_action_url_rejects_unsafe_scheme(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        $this->withHeaders($this->header($admin))->postJson('/api/admin/notifications', [
            'title' => 'x', 'message' => 'y',
            'action_url' => 'javascript:alert(1)',
            'target_type' => 'user', 'target_user_id' => $target->id,
        ])->assertStatus(422);

        $this->withHeaders($this->header($admin))->postJson('/api/admin/notifications', [
            'title' => 'x', 'message' => 'y',
            'action_url' => '//evil.com',
            'target_type' => 'user', 'target_user_id' => $target->id,
        ])->assertStatus(422);
    }

    public function test_nonexistent_media_id_422(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();
        $this->withHeaders($this->header($admin))->postJson('/api/admin/notifications', [
            'title' => 'x', 'message' => 'y',
            'image_media_ids' => [999999],
            'target_type' => 'user', 'target_user_id' => $target->id,
        ])->assertStatus(422);
    }

    public function test_requires_admin(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->withHeaders($this->header($customer))->postJson('/api/admin/notifications', [
            'title' => 'x', 'message' => 'y', 'target_type' => 'role', 'target_role' => 'customer',
        ])->assertStatus(403);

        // The prior request leaves the JWT guard with a cached user and the test
        // client with a persisted Authorization header; clear both so the guest
        // request actually hits auth:api (401) rather than admin (403).
        $this->flushHeaders();
        $this->refreshApplication();
        $this->postJson('/api/admin/notifications', [])->assertUnauthorized();
    }
}
