<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\City;
use App\Models\Country;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryTransfer;
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

class PublicItineraryScheduleTotalExtrasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Transfer::clearZonePriceCache();
    }

    /**
     * Seed a complex itinerary scenario with 2 schedules and transfers with extras.
     *
     * Schedule 1: activity (100) + transfer (zone 80 + transfer_price 30 + luggage 5 + waiting 10 = 125) = 225
     * Schedule 2: activity (50) + transfer (zone 60 + transfer_price 20 + luggage 0 + waiting 5 = 85) = 135
     * Total: 360
     */
    private function seedComplexItinerary(): Itinerary
    {
        // Setup zones, places, and pricing
        $country = Country::create(['name' => 'UAE', 'code' => 'AE', 'slug' => 'uae']);
        $state = State::create(['name' => 'Dubai', 'slug' => 'dubai-state', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Dubai', 'slug' => 'dubai-city', 'state_id' => $state->id]);

        $placeA = Place::create([
            'name' => 'Place A', 'code' => 'PA', 'slug' => 'place-a',
            'type' => 'airport', 'city_id' => $city->id,
        ]);
        $placeB = Place::create([
            'name' => 'Place B', 'code' => 'PB', 'slug' => 'place-b',
            'type' => 'hotel', 'city_id' => $city->id,
        ]);
        $placeC = Place::create([
            'name' => 'Place C', 'code' => 'PC', 'slug' => 'place-c',
            'type' => 'hotel', 'city_id' => $city->id,
        ]);

        // Create zones
        $zoneA = TransferZone::create(['name' => 'Zone A', 'slug' => 'zone-a', 'is_active' => true, 'sort_order' => 1]);
        $zoneB = TransferZone::create(['name' => 'Zone B', 'slug' => 'zone-b', 'is_active' => true, 'sort_order' => 2]);
        $zoneC = TransferZone::create(['name' => 'Zone C', 'slug' => 'zone-c', 'is_active' => true, 'sort_order' => 3]);

        // Link places to zones
        TransferZoneLocation::create([
            'transfer_zone_id' => $zoneA->id,
            'locatable_type' => 'place',
            'locatable_id' => $placeA->id,
        ]);
        TransferZoneLocation::create([
            'transfer_zone_id' => $zoneB->id,
            'locatable_type' => 'place',
            'locatable_id' => $placeB->id,
        ]);
        TransferZoneLocation::create([
            'transfer_zone_id' => $zoneC->id,
            'locatable_type' => 'place',
            'locatable_id' => $placeC->id,
        ]);

        // Create zone prices
        // Route 1: A to B
        TransferZonePrice::create([
            'from_zone_id' => $zoneA->id,
            'to_zone_id' => $zoneB->id,
            'base_price' => 80.00,
            'currency' => 'USD',
        ]);

        // Route 2: B to C
        TransferZonePrice::create([
            'from_zone_id' => $zoneB->id,
            'to_zone_id' => $zoneC->id,
            'base_price' => 60.00,
            'currency' => 'USD',
        ]);

        // Create routes
        $route1 = TransferRoute::create([
            'name' => 'A to B',
            'slug' => 'a-to-b',
            'origin_type' => 'place',
            'origin_id' => $placeA->id,
            'destination_type' => 'place',
            'destination_id' => $placeB->id,
            'from_zone_id' => $zoneA->id,
            'to_zone_id' => $zoneB->id,
            'duration_minutes' => 90,
            'is_active' => true,
        ]);

        $route2 = TransferRoute::create([
            'name' => 'B to C',
            'slug' => 'b-to-c',
            'origin_type' => 'place',
            'origin_id' => $placeB->id,
            'destination_type' => 'place',
            'destination_id' => $placeC->id,
            'from_zone_id' => $zoneB->id,
            'to_zone_id' => $zoneC->id,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        // Create transfers with pricing
        $transfer1 = Transfer::create([
            'name' => 'Transfer A to B',
            'slug' => 'transfer-a-to-b-' . uniqid(),
            'description' => 'test',
            'item_type' => 'transfer',
            'transfer_type' => 'car',
            'transfer_route_id' => $route1->id,
        ]);

        $transfer2 = Transfer::create([
            'name' => 'Transfer B to C',
            'slug' => 'transfer-b-to-c-' . uniqid(),
            'description' => 'test',
            'item_type' => 'transfer',
            'transfer_type' => 'car',
            'transfer_route_id' => $route2->id,
        ]);

        // Add non-vendor pricing with extras
        // Transfer 1: transfer_price=30, luggage=5, waiting=10
        TransferPricingAvailability::create([
            'transfer_id' => $transfer1->id,
            'is_vendor' => false,
            'transfer_price' => 30.00,
            'extra_luggage_charge' => 5.00,
            'waiting_charge' => 10.00,
            'currency' => 'USD',
        ]);

        // Transfer 2: transfer_price=20, luggage=0, waiting=5
        TransferPricingAvailability::create([
            'transfer_id' => $transfer2->id,
            'is_vendor' => false,
            'transfer_price' => 20.00,
            'extra_luggage_charge' => 0.00,
            'waiting_charge' => 5.00,
            'currency' => 'USD',
        ]);

        // Create activities
        $activity1 = Activity::create([
            'name' => 'Activity 1',
            'slug' => 'activity-1-' . uniqid(),
        ]);

        $activity2 = Activity::create([
            'name' => 'Activity 2',
            'slug' => 'activity-2-' . uniqid(),
        ]);

        // Create itinerary with 2 schedules
        $itinerary = Itinerary::factory()->create();

        // Schedule 1
        $schedule1 = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
            'title' => 'Day 1',
        ]);

        ItineraryActivity::create([
            'schedule_id' => $schedule1->id,
            'activity_id' => $activity1->id,
            'price' => 100.00,
            'included' => true,
        ]);

        ItineraryTransfer::create([
            'schedule_id' => $schedule1->id,
            'transfer_id' => $transfer1->id,
            'price' => 0.00, // This should be ignored, using live computeRoutePrice instead
            'included' => true,
        ]);

        // Schedule 2
        $schedule2 = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 2,
            'title' => 'Day 2',
        ]);

        ItineraryActivity::create([
            'schedule_id' => $schedule2->id,
            'activity_id' => $activity2->id,
            'price' => 50.00,
            'included' => true,
        ]);

        ItineraryTransfer::create([
            'schedule_id' => $schedule2->id,
            'transfer_id' => $transfer2->id,
            'price' => 0.00, // This should be ignored, using live computeRoutePrice instead
            'included' => true,
        ]);

        return $itinerary->fresh();
    }

    public function test_schedule_total_includes_transfer_extras_on_show(): void
    {
        $itinerary = $this->seedComplexItinerary();

        $response = $this->getJson('/api/itineraries/' . $itinerary->slug);

        $response->assertOk();
        $payload = $response->json('data');

        // Expected: (100 + 125) + (50 + 85) = 360
        $this->assertArrayHasKey('schedule_total_price', $payload);
        $this->assertSame(360.00, (float) $payload['schedule_total_price']);
    }

    public function test_schedule_total_includes_transfer_extras_on_index(): void
    {
        $itinerary = $this->seedComplexItinerary();

        $response = $this->getJson('/api/itineraries');

        $response->assertOk();
        $items = $response->json('data');
        $this->assertNotEmpty($items);

        $found = collect($items)->firstWhere('id', $itinerary->id);
        $this->assertNotNull($found, 'Seeded itinerary missing from index response');

        // Expected: (100 + 125) + (50 + 85) = 360
        $this->assertArrayHasKey('schedule_total_price', $found);
        $this->assertSame(360.00, (float) $found['schedule_total_price']);
    }

    public function test_parity_between_index_and_show_totals(): void
    {
        $itinerary = $this->seedComplexItinerary();

        // Hit show endpoint
        $showResponse = $this->getJson('/api/itineraries/' . $itinerary->slug);
        $showResponse->assertOk();
        $showPayload = $showResponse->json('data');
        $showTotal = (float) $showPayload['schedule_total_price'];

        // Hit index endpoint
        $indexResponse = $this->getJson('/api/itineraries');
        $indexResponse->assertOk();
        $indexItems = $indexResponse->json('data');
        $indexFound = collect($indexItems)->firstWhere('id', $itinerary->id);

        $this->assertNotNull($indexFound);
        $indexTotal = (float) $indexFound['schedule_total_price'];

        // Assert parity
        $this->assertSame($showTotal, $indexTotal, 'Index and show totals must match');
    }
}
