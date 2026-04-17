<?php

namespace Tests\Feature\Admin;

use App\Models\TransferZone;
use App\Models\TransferZonePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferZonePriceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function seedZones(): array
    {
        return [
            TransferZone::create(['name' => 'A', 'slug' => 'a']),
            TransferZone::create(['name' => 'B', 'slug' => 'b']),
        ];
    }

    public function test_index_returns_zones_and_cells(): void
    {
        [$a, $b] = $this->seedZones();
        TransferZonePrice::create(['from_zone_id' => $a->id, 'to_zone_id' => $b->id, 'price' => 50, 'currency' => 'USD']);
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'api')->getJson('/api/admin/transfer-zone-prices');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'zones')
            ->assertJsonFragment(['from_zone_id' => $a->id, 'to_zone_id' => $b->id]);
    }

    public function test_upsert_creates_and_updates_cell(): void
    {
        [$a, $b] = $this->seedZones();
        $admin = $this->admin();

        // create
        $this->actingAs($admin, 'api')->postJson('/api/admin/transfer-zone-prices/upsert', [
            'from_zone_id' => $a->id, 'to_zone_id' => $b->id, 'price' => 42.5,
        ])->assertStatus(200);

        // update same pair
        $this->actingAs($admin, 'api')->postJson('/api/admin/transfer-zone-prices/upsert', [
            'from_zone_id' => $a->id, 'to_zone_id' => $b->id, 'price' => 77,
        ])->assertStatus(200);

        $this->assertSame(1, TransferZonePrice::count());
        $this->assertEquals(77, (float) TransferZonePrice::first()->price);
    }

    public function test_diagonal_cell_allowed(): void
    {
        [$a] = $this->seedZones();
        $admin = $this->admin();

        $this->actingAs($admin, 'api')->postJson('/api/admin/transfer-zone-prices/upsert', [
            'from_zone_id' => $a->id, 'to_zone_id' => $a->id, 'price' => 15,
        ])->assertStatus(200);

        $this->assertDatabaseHas('transfer_zone_prices', [
            'from_zone_id' => $a->id, 'to_zone_id' => $a->id,
        ]);
    }
}
