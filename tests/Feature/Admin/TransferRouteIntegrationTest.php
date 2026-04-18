<?php

namespace Tests\Feature\Admin;

use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\State;
use App\Models\Transfer;
use App\Models\TransferPricingAvailability;
use App\Models\TransferRoute;
use App\Models\TransferVendorRoute;
use App\Models\TransferZone;
use App\Models\TransferZonePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferRouteIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function seedRouteWithZonePrice(): array
    {
        $country = Country::create(['name' => 'UAE', 'code' => 'AE', 'slug' => 'uae']);
        $state   = State::create(['name' => 'Dubai', 'slug' => 'dubai-state', 'country_id' => $country->id]);
        $city    = City::create(['name' => 'Dubai', 'slug' => 'dubai-city', 'state_id' => $state->id]);
        $pickup  = Place::create([
            'name' => 'DXB', 'code' => 'DXB', 'slug' => 'dxb',
            'type' => 'airport', 'city_id' => $city->id,
        ]);
        $dropoff = Place::create([
            'name' => 'Hotel', 'code' => 'HTL', 'slug' => 'hotel',
            'type' => 'hotel', 'city_id' => $city->id,
        ]);

        $z1 = TransferZone::create(['name' => 'Airport', 'slug' => 'airport']);
        $z2 = TransferZone::create(['name' => 'Downtown', 'slug' => 'downtown']);
        TransferZonePrice::create([
            'from_zone_id' => $z1->id, 'to_zone_id' => $z2->id,
            'base_price' => 55.0, 'currency' => 'USD',
        ]);

        $route = TransferRoute::create([
            'name' => 'DXB to Downtown', 'slug' => 'dxb-to-downtown',
            'origin_type' => 'place', 'origin_id' => $pickup->id,
            'destination_type' => 'place', 'destination_id' => $dropoff->id,
            'from_zone_id' => $z1->id, 'to_zone_id' => $z2->id,
            'is_active' => true,
        ]);

        return compact('route', 'pickup', 'dropoff');
    }

    public function test_transfer_store_auto_resolves_pickup_dropoff_and_base_price_from_route(): void
    {
        $seed  = $this->seedRouteWithZonePrice();
        $admin = $this->admin();

        $payload = [
            'name'              => 'Auto-resolved Transfer',
            'slug'              => 'auto-resolved-transfer',
            'description'       => 'test transfer',
            'transfer_type'     => 'car',
            'is_vendor'         => false,
            'transfer_route_id' => $seed['route']->id,
            'vehicle_type'      => 'sedan',
            'inclusion'         => 'water',
            'currency'          => 'USD',
            'price_type'        => 'per_vehicle',
            'transfer_price'    => 100,
            'extra_luggage_charge' => 0,
            'waiting_charge'       => 0,
        ];

        $response = $this->actingAs($admin, 'api')->postJson('/api/admin/transfers', $payload);

        $response->assertStatus(200);
        $transfer = Transfer::where('slug', 'auto-resolved-transfer')->first();
        $this->assertNotNull($transfer);
        $this->assertSame($seed['route']->id, $transfer->transfer_route_id);

        $vendorRoute = TransferVendorRoute::where('transfer_id', $transfer->id)->first();
        $this->assertSame($seed['pickup']->id,  $vendorRoute->pickup_place_id,  'pickup_place_id derived from route origin');
        $this->assertSame($seed['dropoff']->id, $vendorRoute->dropoff_place_id, 'dropoff_place_id derived from route destination');

        $pricing = TransferPricingAvailability::where('transfer_id', $transfer->id)->first();
        $this->assertEquals(100, (float) $pricing->transfer_price, 'user-provided transfer_price wins over matrix');
    }

    public function test_transfer_store_persists_transfer_route_id(): void
    {
        $seed  = $this->seedRouteWithZonePrice();
        $admin = $this->admin();

        $payload = [
            'name'              => 'Persisted Route',
            'slug'              => 'persisted-route',
            'description'       => 'test transfer',
            'transfer_type'     => 'car',
            'is_vendor'         => false,
            'transfer_route_id' => $seed['route']->id,
            'pickup_location'   => 'A',
            'dropoff_location'  => 'B',
            'vehicle_type'      => 'sedan',
            'inclusion'         => 'x',
            'transfer_price'    => 10,
            'currency'          => 'USD',
            'price_type'        => 'per_vehicle',
            'extra_luggage_charge' => 0,
            'waiting_charge'       => 0,
        ];

        $this->actingAs($admin, 'api')->postJson('/api/admin/transfers', $payload)->assertStatus(200);

        $this->assertDatabaseHas('transfers', [
            'slug'              => 'persisted-route',
            'transfer_route_id' => $seed['route']->id,
        ]);
    }

    public function test_invalid_transfer_route_id_rejected(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'api')->postJson('/api/admin/transfers', [
            'name'              => 'x',
            'slug'              => 'x-x',
            'description'       => 'test transfer',
            'transfer_type'     => 'car',
            'is_vendor'         => false,
            'transfer_route_id' => 99999,
            'pickup_location'   => 'A',
            'dropoff_location'  => 'B',
            'vehicle_type'      => 'sedan',
            'inclusion'         => 'x',
            'transfer_price'    => 10,
            'currency'          => 'USD',
            'price_type'        => 'per_vehicle',
            'extra_luggage_charge' => 0,
            'waiting_charge'       => 0,
        ]);

        $response->assertStatus(422);
    }
}
