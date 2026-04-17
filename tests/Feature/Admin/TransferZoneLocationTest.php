<?php

namespace Tests\Feature\Admin;

use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\State;
use App\Models\TransferZone;
use App\Models\TransferZoneLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferZoneLocationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function seedZoneAndLocations(): array
    {
        $zone    = TransferZone::create(['name' => 'Zone A', 'slug' => 'zone-a']);
        $country = Country::create(['name' => 'UAE', 'code' => 'AE', 'slug' => 'uae']);
        $state   = State::create(['name' => 'Dubai', 'slug' => 'dubai-state', 'country_id' => $country->id]);
        $city    = City::create(['name' => 'Dubai', 'slug' => 'dubai-city', 'state_id' => $state->id]);
        $place   = Place::create([
            'name' => 'DXB Airport', 'code' => 'DXB', 'slug' => 'dxb-airport',
            'type' => 'airport', 'city_id' => $city->id,
        ]);
        return [$zone, $city, $place];
    }

    public function test_admin_can_assign_city_and_place_to_zone(): void
    {
        [$zone, $city, $place] = $this->seedZoneAndLocations();
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'api')->postJson(
            "/api/admin/transfer-zones/{$zone->id}/locations/assign",
            ['locations' => [
                ['locatable_type' => 'city',  'locatable_id' => $city->id],
                ['locatable_type' => 'place', 'locatable_id' => $place->id],
            ]],
        );

        $response->assertStatus(200)->assertJson(['assigned' => 2]);
        $this->assertDatabaseHas('transfer_zone_locations', [
            'transfer_zone_id' => $zone->id,
            'locatable_type'   => 'city',
            'locatable_id'     => $city->id,
        ]);
    }

    public function test_assign_is_idempotent(): void
    {
        [$zone, $city] = $this->seedZoneAndLocations();
        $admin = $this->admin();

        $payload = ['locations' => [['locatable_type' => 'city', 'locatable_id' => $city->id]]];
        $this->actingAs($admin, 'api')->postJson("/api/admin/transfer-zones/{$zone->id}/locations/assign", $payload);
        $response = $this->actingAs($admin, 'api')->postJson("/api/admin/transfer-zones/{$zone->id}/locations/assign", $payload);

        $response->assertStatus(200)->assertJson(['assigned' => 0]);
        $this->assertSame(1, TransferZoneLocation::count());
    }

    public function test_admin_can_unassign(): void
    {
        [$zone, $city] = $this->seedZoneAndLocations();
        $admin = $this->admin();
        TransferZoneLocation::create([
            'transfer_zone_id' => $zone->id,
            'locatable_type'   => 'city',
            'locatable_id'     => $city->id,
        ]);

        $response = $this->actingAs($admin, 'api')->deleteJson(
            "/api/admin/transfer-zones/{$zone->id}/locations/unassign",
            ['locations' => [['locatable_type' => 'city', 'locatable_id' => $city->id]]],
        );

        $response->assertStatus(200)->assertJson(['unassigned' => 1]);
        $this->assertDatabaseMissing('transfer_zone_locations', [
            'transfer_zone_id' => $zone->id,
            'locatable_id'     => $city->id,
        ]);
    }

    public function test_index_returns_merged_cities_and_places_with_current_zones(): void
    {
        [$zone, $city, $place] = $this->seedZoneAndLocations();
        $admin = $this->admin();
        TransferZoneLocation::create([
            'transfer_zone_id' => $zone->id,
            'locatable_type'   => 'city',
            'locatable_id'     => $city->id,
        ]);

        $response = $this->actingAs($admin, 'api')->getJson("/api/admin/transfer-zones/{$zone->id}/locations");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data)); // city + place
        $cityRow = collect($data)->firstWhere('locatable_type', 'city');
        $this->assertTrue($cityRow['assigned_to_current']);
        $this->assertNotEmpty($cityRow['current_zones']);
    }
}
