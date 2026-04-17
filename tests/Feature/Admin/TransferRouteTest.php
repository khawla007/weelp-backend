<?php

namespace Tests\Feature\Admin;

use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\State;
use App\Models\TransferRoute;
use App\Models\TransferZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferRouteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function seedLocations(): array
    {
        $country = Country::create(['name' => 'UAE', 'code' => 'AE', 'slug' => 'uae']);
        $state   = State::create(['name' => 'Dubai', 'slug' => 'dubai-state', 'country_id' => $country->id]);
        $city    = City::create(['name' => 'Dubai', 'slug' => 'dubai-city', 'state_id' => $state->id]);
        $place   = Place::create([
            'name' => 'DXB Airport', 'code' => 'DXB', 'slug' => 'dxb-airport',
            'type' => 'airport', 'city_id' => $city->id,
        ]);
        return [$city, $place];
    }

    public function test_admin_can_create_route_with_mixed_polymorphic_endpoints(): void
    {
        [$city, $place] = $this->seedLocations();
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'api')->postJson('/api/admin/transfer-routes', [
            'name'             => 'Airport to Downtown',
            'origin_type'      => 'place',
            'origin_id'        => $place->id,
            'destination_type' => 'city',
            'destination_id'   => $city->id,
            'distance_km'      => 12.5,
            'duration_minutes' => 25,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transfer_routes', [
            'slug'             => 'airport-to-downtown',
            'origin_type'      => 'place',
            'origin_id'        => $place->id,
            'destination_type' => 'city',
            'destination_id'   => $city->id,
        ]);
    }

    public function test_invalid_endpoint_id_rejected(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'api')->postJson('/api/admin/transfer-routes', [
            'name'             => 'X',
            'origin_type'      => 'city',
            'origin_id'        => 99999,
            'destination_type' => 'city',
            'destination_id'   => 99999,
        ]);

        $response->assertStatus(422);
    }

    public function test_toggle_status_and_popular(): void
    {
        [$city, $place] = $this->seedLocations();
        $route = TransferRoute::create([
            'name' => 'R', 'slug' => 'r',
            'origin_type' => 'place', 'origin_id' => $place->id,
            'destination_type' => 'city', 'destination_id' => $city->id,
            'is_active' => true, 'is_popular' => false,
        ]);
        $admin = $this->admin();

        $this->actingAs($admin, 'api')->patchJson("/api/admin/transfer-routes/{$route->id}/toggle-status")
            ->assertStatus(200)->assertJson(['is_active' => false]);

        $this->actingAs($admin, 'api')->patchJson("/api/admin/transfer-routes/{$route->id}/toggle-popular")
            ->assertStatus(200)->assertJson(['is_popular' => true]);
    }

    public function test_show_eager_loads_endpoints(): void
    {
        [$city, $place] = $this->seedLocations();
        $route = TransferRoute::create([
            'name' => 'R', 'slug' => 'r',
            'origin_type' => 'place', 'origin_id' => $place->id,
            'destination_type' => 'city', 'destination_id' => $city->id,
        ]);
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'api')->getJson("/api/admin/transfer-routes/{$route->id}");

        $response->assertStatus(200)
            ->assertJsonPath('origin.name', 'DXB Airport')
            ->assertJsonPath('destination.name', 'Dubai');
    }

    public function test_location_search_endpoint_returns_cities_and_places(): void
    {
        $this->seedLocations();
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'api')->getJson('/api/admin/locations/search?q=Du');

        $response->assertStatus(200);
        $types = collect($response->json('data'))->pluck('locatable_type')->unique()->values()->all();
        $this->assertContains('city', $types);
    }
}
