<?php

namespace Tests\Feature\Admin;

use App\Models\TransferZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferZoneTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_zone_with_auto_slug(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'api')->postJson('/api/admin/transfer-zones', [
            'name' => 'Downtown Dubai',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transfer_zones', [
            'name' => 'Downtown Dubai',
            'slug' => 'downtown-dubai',
        ]);
    }

    public function test_duplicate_slug_rejected(): void
    {
        $admin = $this->admin();
        TransferZone::create(['name' => 'A', 'slug' => 'a']);

        $response = $this->actingAs($admin, 'api')->postJson('/api/admin/transfer-zones', [
            'name' => 'A two',
            'slug' => 'a',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_update_zone(): void
    {
        $admin = $this->admin();
        $zone = TransferZone::create(['name' => 'Old', 'slug' => 'old']);

        $response = $this->actingAs($admin, 'api')->putJson("/api/admin/transfer-zones/{$zone->id}", [
            'name' => 'New',
        ]);

        $response->assertStatus(200);
        $this->assertSame('New', $zone->fresh()->name);
    }

    public function test_admin_can_delete_zone(): void
    {
        $admin = $this->admin();
        $zone = TransferZone::create(['name' => 'X', 'slug' => 'x']);

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/admin/transfer-zones/{$zone->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('transfer_zones', ['id' => $zone->id]);
    }

    public function test_bulk_delete_removes_multiple_zones(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $z1 = TransferZone::create(['name' => 'Z1', 'slug' => 'z1']);
        $z2 = TransferZone::create(['name' => 'Z2', 'slug' => 'z2']);

        $this->actingAs($superAdmin, 'api')
            ->postJson('/api/admin/transfer-zones/bulk-delete', ['ids' => [$z1->id, $z2->id]])
            ->assertStatus(200);

        $this->assertDatabaseMissing('transfer_zones', ['id' => $z1->id]);
        $this->assertDatabaseMissing('transfer_zones', ['id' => $z2->id]);
    }

    public function test_index_returns_locations_count(): void
    {
        $admin = $this->admin();
        TransferZone::create(['name' => 'A', 'slug' => 'a']);

        $response = $this->actingAs($admin, 'api')->getJson('/api/admin/transfer-zones');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'A']);
        $this->assertArrayHasKey('locations_count', $response->json('data.0'));
    }
}
