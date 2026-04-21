<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItineraryBasePricing;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryTransfer;
use App\Models\Transfer;
use App\Models\TransferRoute;
use App\Models\TransferZone;
use App\Models\TransferZonePrice;
use App\Models\TransferPricingAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ItineraryScheduleTotalPriceLiveTransferTest extends TestCase
{
    use RefreshDatabase;

    private function makeActivity(): Activity
    {
        $suffix = Str::random(12);

        return Activity::create([
            'name' => 'Test Activity ' . $suffix,
            'slug' => 'test-activity-' . strtolower($suffix),
            'description' => 'Test activity description',
            'item_type' => 'activity',
            'short_description' => 'short',
            'featured_activity' => false,
        ]);
    }

    private function makeTransfer(): Transfer
    {
        $suffix = Str::random(12);

        return Transfer::create([
            'name' => 'Test Transfer ' . $suffix,
            'slug' => 'test-transfer-' . strtolower($suffix),
            'description' => 'Test transfer description',
            'item_type' => 'transfer',
            'transfer_type' => 'private',
        ]);
    }

    /**
     * Helper: Build a transfer with zone pricing and pricing availability.
     * Returns [Transfer, TransferZonePrice, TransferPricingAvailability] tuple.
     */
    private function buildTransfer(int $zoneBase, float $transferPrice, string $currency): array
    {
        $transfer = $this->makeTransfer();

        // Create zones
        $fromZone = TransferZone::factory()->create();
        $toZone = TransferZone::factory()->create();

        // Create route via factory
        $route = TransferRoute::factory()->create([
            'from_zone_id' => $fromZone->id,
            'to_zone_id' => $toZone->id,
        ]);
        $transfer->update(['transfer_route_id' => $route->id]);

        // Create zone price
        $zonePrice = TransferZonePrice::create([
            'from_zone_id' => $fromZone->id,
            'to_zone_id' => $toZone->id,
            'base_price' => $zoneBase,
            'currency' => $currency,
        ]);

        // Create pricing availability (non-vendor)
        $pricingAvailability = TransferPricingAvailability::create([
            'transfer_id' => $transfer->id,
            'transfer_price' => $transferPrice,
            'currency' => $currency,
            'is_vendor' => false,
        ]);

        return [$transfer, $zonePrice, $pricingAvailability];
    }

    public function test_total_uses_live_transfer_price_not_stored_snapshot(): void
    {
        Transfer::clearZonePriceCache();

        // Build transfer: zone base 40 + transfer_price 25 = 65
        [$transfer, $zonePrice, $pricingAvailability] = $this->buildTransfer(40, 25.0, 'USD');

        $itinerary = Itinerary::factory()->create();

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        // Activity: 100
        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $this->makeActivity()->id,
            'price' => 100.00,
            'included' => true,
        ]);

        // Transfer: stored snapshot 999 (stale), but live = 40 + 25 = 65
        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $transfer->id,
            'price' => 999.00, // stale snapshot
            'included' => true,
        ]);

        // Total should be 100 + 65 = 165 (not 100 + 999)
        $this->assertSame(165.00, $itinerary->schedule_total_price);
    }

    public function test_total_when_transfer_exists_without_route_uses_live_zero(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        // Activity: 100
        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $this->makeActivity()->id,
            'price' => 100.00,
            'included' => true,
        ]);

        // Transfer exists but has no route configured, so computeRoutePrice() returns 0
        // Stored snapshot price (42) is ignored in favor of live computation
        $transfer = $this->makeTransfer();
        // Do not assign a route_id

        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $transfer->id,
            'price' => 42.00,
            'included' => true,
        ]);

        // Total should be 100 + 0 = 100 (live computation takes precedence)
        $this->assertSame(100.00, $itinerary->schedule_total_price);
    }

    public function test_currency_resolves_from_base_pricing_first(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();

        // Base pricing with EUR
        ItineraryBasePricing::create([
            'itinerary_id' => $itinerary->id,
            'currency' => 'EUR',
            'availability' => 'daily',
        ]);

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        // Build transfer with GBP (should not override EUR from base)
        [$transfer, $zonePrice, $pricingAvailability] = $this->buildTransfer(10, 5.0, 'GBP');

        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $this->makeActivity()->id,
            'price' => 50.00,
            'included' => true,
        ]);

        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $transfer->id,
            'price' => 15.00,
            'included' => true,
        ]);

        // Currency should be EUR (from basePricing, not GBP from transfer)
        $this->assertSame('EUR', $itinerary->schedule_total_currency);
    }

    public function test_currency_falls_back_to_first_transfer_when_no_base_pricing(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        // Build transfer with GBP
        [$transfer, $zonePrice, $pricingAvailability] = $this->buildTransfer(10, 5.0, 'GBP');

        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $this->makeActivity()->id,
            'price' => 50.00,
            'included' => true,
        ]);

        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $transfer->id,
            'price' => 15.00,
            'included' => true,
        ]);

        // Currency should be GBP (from first transfer's route currency)
        $this->assertSame('GBP', $itinerary->schedule_total_currency);
    }

    public function test_currency_falls_back_to_first_activity_when_no_base_pricing_or_transfer(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        // Create activity with pricing in CAD
        $activity = $this->makeActivity();
        $activity->pricing()->create([
            'regular_price' => 100.0,
            'currency' => 'CAD',
        ]);

        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $activity->id,
            'price' => 50.00,
            'included' => true,
        ]);

        // No base pricing, no transfers — currency should fall back to activity's CAD
        $this->assertSame('CAD', $itinerary->schedule_total_currency);
    }
}
