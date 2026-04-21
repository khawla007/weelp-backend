<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItineraryBasePricing;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryTransfer;
use App\Models\Transfer;
use App\Models\TransferPricingAvailability;
use App\Models\TransferRoute;
use App\Models\TransferZone;
use App\Models\TransferZonePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicItineraryScheduleTotalCurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: Seed an itinerary with one transfer and one activity.
     * Chain: TransferZone → TransferZonePrice → TransferRoute → Transfer → TransferPricingAvailability,
     * then Itinerary → ItineraryBasePricing → ItinerarySchedule → ItineraryActivity + ItineraryTransfer.
     */
    private function seedItineraryWithOneTransferAndOneActivity(
        float $activityPrice,
        float $zoneBase,
        float $transferPrice,
        string $currency
    ): Itinerary {
        // Create transfer zones
        $fromZone = TransferZone::factory()->create();
        $toZone = TransferZone::factory()->create();

        // Create zone price with specified base and currency
        $zonePrice = TransferZonePrice::factory()->create([
            'from_zone_id' => $fromZone->id,
            'to_zone_id'   => $toZone->id,
            'base_price'   => $zoneBase,
            'currency'     => $currency,
        ]);

        // Create transfer route linking the zones
        $route = TransferRoute::factory()->create([
            'from_zone_id' => $fromZone->id,
            'to_zone_id'   => $toZone->id,
        ]);

        // Create transfer with the route
        $transfer = Transfer::factory()->create([
            'transfer_route_id' => $route->id,
        ]);

        // Create non-vendor pricing availability for the transfer
        TransferPricingAvailability::factory()->create([
            'transfer_id'    => $transfer->id,
            'is_vendor'      => false,
            'transfer_price' => $transferPrice,
            'currency'       => $currency,
        ]);

        // Create itinerary
        $itinerary = Itinerary::factory()->create();

        // Create base pricing with specified currency
        ItineraryBasePricing::factory()->create([
            'itinerary_id' => $itinerary->id,
            'currency'     => $currency,
        ]);

        // Create schedule
        $schedule = ItinerarySchedule::factory()->create([
            'itinerary_id' => $itinerary->id,
            'day'          => 1,
            'title'        => 'Day 1',
        ]);

        // Create activity
        $activity = Activity::factory()->create();

        // Link activity to schedule with specified price
        ItineraryActivity::factory()->create([
            'schedule_id' => $schedule->id,
            'activity_id' => $activity->id,
            'price'       => $activityPrice,
            'included'    => true,
        ]);

        // Link transfer to schedule
        ItineraryTransfer::factory()->create([
            'schedule_id' => $schedule->id,
            'transfer_id' => $transfer->id,
            'included'    => true,
        ]);

        return $itinerary->fresh();
    }

    public function test_show_returns_schedule_total_price_and_currency_using_live_transfer(): void
    {
        // Seed: activity 100, zone_base 30, transfer_price 20, currency USD
        // Expected total: 100 + 30 + 20 = 150 USD
        $itinerary = $this->seedItineraryWithOneTransferAndOneActivity(
            activityPrice: 100.0,
            zoneBase: 30.0,
            transferPrice: 20.0,
            currency: 'USD'
        );

        // Clear cache to ensure fresh computation
        Transfer::clearZonePriceCache();

        // GET the itinerary
        $response = $this->getJson('/api/itineraries/' . $itinerary->slug);

        // Assert response is successful
        $response->assertOk();

        // Assert both fields are present and correct
        $payload = $response->json('data');
        $this->assertArrayHasKey('schedule_total_price', $payload);
        $this->assertArrayHasKey('schedule_total_currency', $payload);

        // Assert values match the live transfer formula: 100 + 30 + 20 = 150
        $this->assertSame(150.0, (float) $payload['schedule_total_price']);
        $this->assertSame('USD', $payload['schedule_total_currency']);
    }
}
