<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
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

class ItineraryScheduleTotalPriceTest extends TestCase
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

    private function makeTransfer(float $totalPrice = 0): Transfer
    {
        $suffix = Str::random(12);

        $transfer = Transfer::create([
            'name' => 'Test Transfer ' . $suffix,
            'slug' => 'test-transfer-' . strtolower($suffix),
            'description' => 'Test transfer description',
            'item_type' => 'transfer',
            'transfer_type' => 'private',
        ]);

        // Set up zone pricing if needed (split as zone base + transfer price)
        if ($totalPrice > 0) {
            $fromZone = TransferZone::factory()->create();
            $toZone = TransferZone::factory()->create();

            $route = TransferRoute::factory()->create([
                'from_zone_id' => $fromZone->id,
                'to_zone_id' => $toZone->id,
            ]);
            $transfer->update(['transfer_route_id' => $route->id]);

            $zoneBase = floor($totalPrice / 2);
            $transferPrice = $totalPrice - $zoneBase;

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
            ]);
        }

        return $transfer;
    }

    public function test_sums_activity_and_transfer_prices_across_schedules(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();

        $day1 = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);
        $day2 = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 2,
        ]);

        // Day 1: two activities (50 + 75) + one transfer (30) = 155
        ItineraryActivity::create([
            'schedule_id' => $day1->id,
            'activity_id' => $this->makeActivity()->id,
            'price' => 50.00,
            'included' => true,
        ]);
        ItineraryActivity::create([
            'schedule_id' => $day1->id,
            'activity_id' => $this->makeActivity()->id,
            'price' => 75.00,
            'included' => true,
        ]);
        ItineraryTransfer::create([
            'schedule_id' => $day1->id,
            'transfer_id' => $this->makeTransfer(30.00)->id,
            'price' => 30.00,
            'included' => true,
        ]);

        // Day 2: one activity (100) + one transfer (45) = 145
        ItineraryActivity::create([
            'schedule_id' => $day2->id,
            'activity_id' => $this->makeActivity()->id,
            'price' => 100.00,
            'included' => true,
        ]);
        ItineraryTransfer::create([
            'schedule_id' => $day2->id,
            'transfer_id' => $this->makeTransfer(45.00)->id,
            'price' => 45.00,
            'included' => true,
        ]);

        $itinerary->load('schedules.activities', 'schedules.transfers.transfer.route', 'schedules.transfers.transfer.pricingAvailability');

        $this->assertSame(300.00, $itinerary->schedule_total_price);
    }

    public function test_returns_zero_when_no_schedules(): void
    {
        $itinerary = Itinerary::factory()->create();
        $itinerary->load('schedules.activities', 'schedules.transfers');

        $this->assertSame(0.0, $itinerary->schedule_total_price);
    }

    public function test_null_prices_are_coerced_to_zero(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $this->makeActivity()->id,
            'price' => null,
            'included' => true,
        ]);
        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $this->makeActivity()->id,
            'price' => 25.00,
            'included' => true,
        ]);
        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $this->makeTransfer()->id, // No price setup, will return 0
            'price' => null,
            'included' => true,
        ]);

        $itinerary->load('schedules.activities', 'schedules.transfers.transfer.route', 'schedules.transfers.transfer.pricingAvailability');

        $this->assertSame(25.00, $itinerary->schedule_total_price);
    }

    public function test_accessor_is_appended_to_array(): void
    {
        $itinerary = Itinerary::factory()->create();
        $itinerary->load('schedules.activities', 'schedules.transfers');

        $array = $itinerary->toArray();

        $this->assertArrayHasKey('schedule_total_price', $array);
        $this->assertArrayHasKey('schedule_total_currency', $array);
        $this->assertSame(0.0, $array['schedule_total_price']);
    }
}
