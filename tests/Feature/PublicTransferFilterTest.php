<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\State;
use App\Models\Transfer;
use App\Models\TransferRoute;
use App\Models\TransferSchedule;
use App\Models\TransferZone;
use App\Models\TransferZoneLocation;
use App\Models\TransferZonePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTransferFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{zoneA:TransferZone,zoneB:TransferZone,placeA:Place,placeB:Place,route:TransferRoute,transfer:Transfer,unrelated:Transfer}
     */
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

        TransferZonePrice::create([
            'from_zone_id' => $zoneA->id,
            'to_zone_id'   => $zoneB->id,
            'base_price'   => 50,
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

        TransferSchedule::create([
            'transfer_id'        => $transfer->id,
            'is_vendor'          => false,
            'maximum_passengers' => 4,
        ]);

        $unrelated = Transfer::create([
            'name'              => 'Unrelated Transfer',
            'slug'              => 'unrelated-transfer',
            'description'       => 'unrelated',
            'item_type'         => 'transfer',
            'transfer_type'     => 'car',
            'transfer_route_id' => null,
        ]);

        return compact('zoneA', 'zoneB', 'placeA', 'placeB', 'route', 'transfer', 'unrelated');
    }

    public function test_filters_by_origin_and_destination_resolves_zones_and_returns_matching_transfer(): void
    {
        $seed = $this->seedScenario();

        $response = $this->getJson(
            '/api/transfers?origin_type=place&origin_id=' . $seed['placeA']->id
            . '&destination_type=place&destination_id=' . $seed['placeB']->id
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame($seed['transfer']->name, $data[0]['name']);
        $this->assertEquals(50, (float) $data[0]['route_price']);
        $this->assertSame(90, (int) $data[0]['route_duration_minutes']);
        $this->assertSame('USD', $data[0]['route_currency']);
    }

    public function test_zone_filter_with_no_matching_routes_returns_empty_paginated_result(): void
    {
        $seed = $this->seedScenario();

        // zoneA -> zoneA has no route
        $response = $this->getJson(
            '/api/transfers?from_zone_id=' . $seed['zoneA']->id
            . '&to_zone_id=' . $seed['zoneA']->id
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);
    }

    public function test_no_filter_preserves_back_compat_and_returns_all_transfers(): void
    {
        $this->seedScenario();

        $response = $this->getJson('/api/transfers');

        $response->assertStatus(200)->assertJsonPath('success', true);

        // Legacy shape: no `meta` key when unfiltered.
        $this->assertArrayNotHasKey('meta', $response->json());

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    public function test_city_id_filters_by_origin_city_zone(): void
    {
        $seed = $this->seedScenario();

        // Attach the city (of placeA) to zoneA so city_id resolves to from_zone_id.
        TransferZoneLocation::create([
            'transfer_zone_id' => $seed['zoneA']->id,
            'locatable_type'   => 'city',
            'locatable_id'     => $seed['placeA']->city_id,
        ]);

        $response = $this->getJson('/api/transfers?city_id=' . $seed['placeA']->city_id);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $data = $response->json('data');
        $names = collect($data)->pluck('name')->all();
        $this->assertContains($seed['transfer']->name, $names);
    }

    /**
     * A3: Transfers without a schedule row must still appear when passengers filter is sent.
     * Transfers WITH a schedule row and sufficient capacity must also appear.
     */
    public function test_passengers_filter_does_not_exclude_transfers_without_schedule(): void
    {
        $seed = $this->seedScenario();

        // The 'transfer' seeded by seedScenario() has a schedule with max_passengers=4.
        // The 'unrelated' transfer has no schedule at all.
        // Both should appear when passengers=1 is sent with no route filter.
        $response = $this->getJson('/api/transfers?passengers=1');

        $response->assertStatus(200)->assertJsonPath('success', true);

        $data = $response->json('data');
        $names = collect($data)->pluck('name')->all();

        // Transfer with schedule (max 4 passengers) — must appear for passengers=1.
        $this->assertContains($seed['transfer']->name, $names);

        // Transfer without schedule — must NOT be silently excluded.
        $this->assertContains($seed['unrelated']->name, $names);
    }

    /**
     * A3: origin+destination exact-route match with passengers=1 returns non-empty data.
     */
    public function test_origin_destination_with_passengers_returns_matching_transfer(): void
    {
        $seed = $this->seedScenario();

        $response = $this->getJson(
            '/api/transfers?origin_type=place&origin_id=' . $seed['placeA']->id
            . '&destination_type=place&destination_id=' . $seed['placeB']->id
            . '&passengers=1'
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertNotEmpty($data, 'Expected non-empty data for place-to-place filter with passengers=1');
        $this->assertSame($seed['transfer']->name, $data[0]['name']);
    }
}
