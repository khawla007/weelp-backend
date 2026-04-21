<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\State;
use App\Models\Transfer;
use App\Models\TransferPricingAvailability;
use App\Models\TransferRoute;
use App\Models\TransferZone;
use App\Models\TransferZoneLocation;
use App\Models\TransferZonePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTransferControllerContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Transfer::clearZonePriceCache();
    }

    private function seedScenario(): array
    {
        $country = Country::create(['name' => 'UAE', 'code' => 'AE', 'slug' => 'uae']);
        $state   = State::create(['name' => 'Dubai', 'slug' => 'dubai-state', 'country_id' => $country->id]);
        $city    = City::create(['name' => 'Dubai', 'slug' => 'dubai-city', 'state_id' => $state->id]);

        $placeA = Place::create([
            'name' => 'Place A', 'code' => 'PA', 'slug' => 'place-a',
            'type' => 'airport', 'city_id' => $city->id,
        ]);
        $placeB = Place::create([
            'name' => 'Place B', 'code' => 'PB', 'slug' => 'place-b',
            'type' => 'hotel', 'city_id' => $city->id,
        ]);

        $zoneA = TransferZone::create(['name' => 'Zone A', 'slug' => 'zone-a', 'is_active' => true, 'sort_order' => 1]);
        $zoneB = TransferZone::create(['name' => 'Zone B', 'slug' => 'zone-b', 'is_active' => true, 'sort_order' => 2]);

        TransferZoneLocation::create([
            'transfer_zone_id' => $zoneA->id,
            'locatable_type'   => 'place',
            'locatable_id'     => $placeA->id,
        ]);
        TransferZoneLocation::create([
            'transfer_zone_id' => $zoneB->id,
            'locatable_type'   => 'place',
            'locatable_id'     => $placeB->id,
        ]);

        // Seed zone pricing: base_price=50, currency=USD
        TransferZonePrice::create([
            'from_zone_id' => $zoneA->id,
            'to_zone_id'   => $zoneB->id,
            'base_price'   => 50.00,
            'currency'     => 'USD',
        ]);

        $route = TransferRoute::create([
            'name'             => 'A to B',
            'slug'             => 'a-to-b',
            'origin_type'      => 'place',
            'origin_id'        => $placeA->id,
            'destination_type' => 'place',
            'destination_id'   => $placeB->id,
            'from_zone_id'     => $zoneA->id,
            'to_zone_id'       => $zoneB->id,
            'duration_minutes' => 90,
            'is_active'        => true,
        ]);

        $transfer = Transfer::create([
            'name'              => 'Zone A-B Private Transfer',
            'slug'              => 'zone-a-b-private-transfer',
            'description'       => 'test',
            'item_type'         => 'transfer',
            'transfer_type'     => 'car',
            'transfer_route_id' => $route->id,
        ]);

        // Seed non-vendor pricing availability with transfer_price=30
        TransferPricingAvailability::create([
            'transfer_id'   => $transfer->id,
            'is_vendor'     => false,
            'transfer_price' => 30.00,
            'currency'      => 'USD',
        ]);

        return compact('zoneA', 'zoneB', 'placeA', 'placeB', 'route', 'transfer');
    }

    /**
     * Test that /api/transfers response contains the required price-related keys
     * and they match the formula: zone_base_price + transfer_price = route_price.
     */
    public function test_api_transfers_response_contains_price_keys_with_correct_values(): void
    {
        $seed = $this->seedScenario();

        // Query the transfer via the API
        $response = $this->getJson(
            '/api/transfers?origin_type=place&origin_id=' . $seed['placeA']->id
            . '&destination_type=place&destination_id=' . $seed['placeB']->id
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertCount(1, $data);

        $transfer = $data[0];

        // Assert required keys are present
        $this->assertArrayHasKey('zone_base_price', $transfer, 'zone_base_price key missing');
        $this->assertArrayHasKey('transfer_price', $transfer, 'transfer_price key missing');
        $this->assertArrayHasKey('route_price', $transfer, 'route_price key missing');
        $this->assertArrayHasKey('route_currency', $transfer, 'route_currency key missing');

        // Assert correct values: base 50 + transfer 30 = route 80
        $this->assertEquals(50.0, (float) $transfer['zone_base_price'], 'zone_base_price mismatch');
        $this->assertEquals(30.0, (float) $transfer['transfer_price'], 'transfer_price mismatch');
        $this->assertEquals(80.0, (float) $transfer['route_price'], 'route_price mismatch');
        $this->assertSame('USD', $transfer['route_currency'], 'route_currency mismatch');
    }

    /**
     * Test that the response shape is preserved when filters are used with pagination.
     */
    public function test_api_transfers_paginated_response_maintains_price_shape(): void
    {
        $seed = $this->seedScenario();

        // Query with pagination
        $response = $this->getJson(
            '/api/transfers?origin_type=place&origin_id=' . $seed['placeA']->id
            . '&destination_type=place&destination_id=' . $seed['placeB']->id
            . '&per_page=10&page=1'
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.current_page', 1);

        $data = $response->json('data');
        $this->assertCount(1, $data);

        $transfer = $data[0];

        // Assert the price-related keys are still present
        $this->assertArrayHasKey('zone_base_price', $transfer);
        $this->assertArrayHasKey('transfer_price', $transfer);
        $this->assertArrayHasKey('route_price', $transfer);
        $this->assertArrayHasKey('route_currency', $transfer);

        // Assert values match formula
        $this->assertEquals(80.0, (float) $transfer['route_price']);
    }
}
