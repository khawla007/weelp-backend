<?php

namespace Tests\Feature\Admin;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_cannot_create_announcement(): void
    {
        $this->postJson('/api/admin/announcements', [
            'type' => 'offer', 'title' => 'x', 'message' => 'y', 'is_active' => true,
        ])->assertStatus(401);
    }

    public function test_admin_can_create_announcement(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'api')->postJson('/api/admin/announcements', [
            'type' => 'offer',
            'title' => 'Summer sale',
            'message' => '20% off all tours',
            'link' => 'https://example.com/summer',
            'is_active' => true,
        ]);

        $response->assertCreated()->assertJsonFragment(['title' => 'Summer sale']);
        $this->assertDatabaseHas('announcements', [
            'title' => 'Summer sale',
            'created_by' => $admin->id,
        ]);
    }

    public function test_create_validation_rejects_bad_type_and_missing_title(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'api')->postJson('/api/admin/announcements', [
            'type' => 'bogus', 'message' => 'y', 'is_active' => true,
        ])->assertStatus(422);
    }

    public function test_expires_at_allowed_when_publish_at_null(): void
    {
        $admin = $this->admin();

        // "Show now, hide later" — expiry set, no publish date. Must NOT 422.
        $this->actingAs($admin, 'api')->postJson('/api/admin/announcements', [
            'type' => 'offer', 'title' => 'Flash', 'message' => 'ends soon',
            'is_active' => true, 'expires_at' => now()->addDay()->toDateTimeString(),
        ])->assertCreated();
    }

    public function test_expires_at_must_be_after_publish_at_when_both_set(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'api')->postJson('/api/admin/announcements', [
            'type' => 'offer', 'title' => 'Bad range', 'message' => 'm', 'is_active' => true,
            'publish_at' => now()->addDays(2)->toDateTimeString(),
            'expires_at' => now()->addDay()->toDateTimeString(),
        ])->assertStatus(422);
    }

    public function test_create_rejects_dangerous_link_scheme(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'api')->postJson('/api/admin/announcements', [
            'type' => 'offer', 'title' => 'XSS', 'message' => 'm', 'is_active' => true,
            'link' => 'javascript:alert(1)',
        ])->assertStatus(422);
    }

    public function test_create_allows_relative_link(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'api')->postJson('/api/admin/announcements', [
            'type' => 'update', 'title' => 'Internal', 'message' => 'm', 'is_active' => true,
            'link' => '/cities/dubai/activities/desert-safari',
        ])->assertCreated();
    }

    public function test_create_rejects_protocol_relative_link(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'api')->postJson('/api/admin/announcements', [
            'type' => 'offer', 'title' => 'Open redirect', 'message' => 'm', 'is_active' => true,
            'link' => '//evil.com',
        ])->assertStatus(422);
    }

    public function test_admin_can_update_and_delete_announcement(): void
    {
        $admin = $this->admin();
        $a = Announcement::create([
            'type' => 'update', 'title' => 'Old', 'message' => 'm', 'is_active' => true,
        ]);

        $this->actingAs($admin, 'api')->putJson("/api/admin/announcements/{$a->id}", [
            'title' => 'New title', 'is_active' => false,
        ])->assertOk()->assertJsonFragment(['title' => 'New title']);

        $this->assertDatabaseHas('announcements', ['id' => $a->id, 'title' => 'New title', 'is_active' => false]);

        $this->actingAs($admin, 'api')->deleteJson("/api/admin/announcements/{$a->id}")->assertOk();
        $this->assertDatabaseMissing('announcements', ['id' => $a->id]);
    }

    public function test_admin_index_lists_inactive_too(): void
    {
        $admin = $this->admin();
        Announcement::create(['type' => 'offer', 'title' => 'Hidden one', 'message' => 'm', 'is_active' => false]);

        $this->actingAs($admin, 'api')->getJson('/api/admin/announcements')
            ->assertOk()->assertJsonFragment(['title' => 'Hidden one']);
    }
}
