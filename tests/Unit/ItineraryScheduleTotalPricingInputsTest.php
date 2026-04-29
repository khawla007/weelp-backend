<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\ActivityPricing;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryTransfer;
use App\Models\Transfer;
use App\Models\TransferPricingAvailability;
use App\Models\TransferRoute;
use App\Models\TransferZone;
use App\Models\TransferZonePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ItineraryScheduleTotalPricingInputsTest extends TestCase
{
    use RefreshDatabase;

    private function makeActivityWithPrice(float $regularPrice, string $currency = 'USD'): Activity
    {
        $suffix = Str::random(12);
        $activity = Activity::create([
            'name' => 'Test Activity ' . $suffix,
            'slug' => 'test-activity-' . strtolower($suffix),
            'description' => 'desc',
            'item_type' => 'activity',
            'short_description' => 'short',
            'featured_activity' => false,
        ]);

        ActivityPricing::create([
            'activity_id' => $activity->id,
            'regular_price' => $regularPrice,
            'currency' => $currency,
        ]);

        return $activity;
    }

    /**
     * Build a transfer with per_person pricing and luggage/waiting rates.
     */
    private function buildPerPersonTransfer(
        int $zoneBase,
        float $transferPrice,
        float $luggagePerBag = 0,
        float $waitingPerMin = 0,
    ): Transfer {
        $suffix = Str::random(12);
        $transfer = Transfer::create([
            'name' => 'Test Transfer ' . $suffix,
            'slug' => 'test-transfer-' . strtolower($suffix),
            'description' => 'desc',
            'item_type' => 'transfer',
            'transfer_type' => 'private',
        ]);

        $fromZone = TransferZone::factory()->create();
        $toZone = TransferZone::factory()->create();
        $route = TransferRoute::factory()->create([
            'from_zone_id' => $fromZone->id,
            'to_zone_id' => $toZone->id,
        ]);
        $transfer->update(['transfer_route_id' => $route->id]);

        TransferZonePrice::create([
            'from_zone_id' => $fromZone->id,
            'to_zone_id' => $toZone->id,
            'base_price' => $zoneBase,
            'currency' => 'USD',
        ]);

        TransferPricingAvailability::create([
            'transfer_id' => $transfer->id,
            'transfer_price' => $transferPrice,
            'currency' => 'USD',
            'is_vendor' => false,
            'price_type' => 'per_person',
            'extra_luggage_charge' => $luggagePerBag,
            'waiting_charge' => $waitingPerMin,
        ]);

        return $transfer;
    }

    public function test_activity_multiplies_by_itinerary_headcount(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create([
            'adults' => 2,
            'children' => 1,
            'infants' => 0,
        ]);

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        $activity = $this->makeActivityWithPrice(50.0);

        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $activity->id,
            'price' => null,
            'included' => true,
        ]);

        // 2 adults + 1 child = 3 pax × 50 = 150
        $this->assertSame(150.00, $itinerary->schedule_total_price);
    }

    public function test_transfer_uses_per_pax_with_luggage_and_waiting(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create([
            'adults' => 3,
            'children' => 0,
        ]);

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        // unit price = 10 + 5 = 15 per person; per_person × 3 = 45
        // luggage: 2 × 4 = 8; waiting: 10 × 0.5 = 5; total transfer = 58
        $transfer = $this->buildPerPersonTransfer(10, 5.0, luggagePerBag: 4.0, waitingPerMin: 0.5);

        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $transfer->id,
            'price' => null,
            'included' => true,
            'bag_count' => 2,
            'waiting_minutes' => 10,
        ]);

        $this->assertSame(58.00, $itinerary->schedule_total_price);
    }

    public function test_transfer_pax_override_overrides_itinerary_headcount(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create([
            'adults' => 4,
            'children' => 0,
        ]);

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        // unit = 20 per person; itinerary headcount = 4, but row pax = 2 → 40
        $transfer = $this->buildPerPersonTransfer(10, 10.0);

        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $transfer->id,
            'price' => null,
            'included' => true,
            'pax' => 2,
            'bag_count' => 0,
            'waiting_minutes' => 0,
        ]);

        $this->assertSame(40.00, $itinerary->schedule_total_price);
    }

    public function test_default_headcount_is_one_when_unset(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        $activity = $this->makeActivityWithPrice(75.0);
        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $activity->id,
            'price' => null,
            'included' => true,
        ]);

        // Default adults=1, children=0 → headcount=1
        $this->assertSame(75.00, $itinerary->schedule_total_price);
    }

    public function test_mixed_activity_and_transfer_with_headcount(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create([
            'adults' => 2,
            'children' => 0,
        ]);

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        // Activity 100 × 2 = 200
        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $this->makeActivityWithPrice(100.0)->id,
            'price' => null,
            'included' => true,
        ]);

        // Transfer per_person 25 × 2 = 50, no extras
        $transfer = $this->buildPerPersonTransfer(15, 10.0);
        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $transfer->id,
            'price' => null,
            'included' => true,
        ]);

        $this->assertSame(250.00, $itinerary->schedule_total_price);
    }
}
