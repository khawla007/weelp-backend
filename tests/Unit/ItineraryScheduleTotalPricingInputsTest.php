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
use App\Models\TransferSchedule;
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
     * Build a transfer with optional capacity, pricing, and luggage/waiting rates.
     */
    private function buildTransfer(
        int $zoneBase,
        float $transferPrice,
        ?int $maxPassengers = null,
        float $luggagePerBag = 0,
        float $waitingPerMin = 0,
        string $priceType = 'per_person',
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
            'price_type' => $priceType,
            'extra_luggage_charge' => $luggagePerBag,
            'waiting_charge' => $waitingPerMin,
        ]);

        if ($maxPassengers !== null) {
            TransferSchedule::create([
                'transfer_id' => $transfer->id,
                'is_vendor' => false,
                'maximum_passengers' => $maxPassengers,
            ]);
        }

        return $transfer;
    }

    public function test_activity_uses_unit_base_price_no_headcount_multiplier(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $this->makeActivityWithPrice(50.0)->id,
            'price' => null,
            'included' => true,
        ]);

        // Unit base only — no × pax multiplication
        $this->assertSame(50.00, $itinerary->schedule_total_price);
    }

    public function test_transfer_uses_unit_base_plus_extras_ignoring_price_type(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        // unit = 10 + 5 = 15 (per_person price_type ignored at itinerary level)
        // luggage: 2 × 4 = 8; waiting: 10 × 0.5 = 5; total transfer = 28
        $transfer = $this->buildTransfer(10, 5.0, luggagePerBag: 4.0, waitingPerMin: 0.5);

        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $transfer->id,
            'price' => null,
            'included' => true,
            'bag_count' => 2,
            'waiting_minutes' => 10,
        ]);

        $this->assertSame(28.00, $itinerary->schedule_total_price);
    }

    public function test_max_guests_is_min_of_transfer_capacities(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        $transferA = $this->buildTransfer(10, 5.0, maxPassengers: 6);
        $transferB = $this->buildTransfer(10, 5.0, maxPassengers: 4);

        ItineraryTransfer::create(['schedule_id' => $day->id, 'transfer_id' => $transferA->id, 'included' => true]);
        ItineraryTransfer::create(['schedule_id' => $day->id, 'transfer_id' => $transferB->id, 'included' => true]);

        $this->assertSame(4, $itinerary->max_guests);
    }

    public function test_max_guests_is_null_when_no_transfers_have_capacity(): void
    {
        $itinerary = Itinerary::factory()->create();

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        // Transfer with no schedule row → no capacity
        $transfer = $this->buildTransfer(10, 5.0);

        ItineraryTransfer::create(['schedule_id' => $day->id, 'transfer_id' => $transfer->id, 'included' => true]);

        $this->assertNull($itinerary->max_guests);
    }

    public function test_max_guests_is_null_when_no_transfers(): void
    {
        $itinerary = Itinerary::factory()->create();
        ItinerarySchedule::create(['itinerary_id' => $itinerary->id, 'day' => 1]);

        $this->assertNull($itinerary->max_guests);
    }

    public function test_priceForGuests_multiplies_activity_per_pax(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();
        $day = ItinerarySchedule::create(['itinerary_id' => $itinerary->id, 'day' => 1]);

        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $this->makeActivityWithPrice(50.0)->id,
            'price' => null,
            'included' => true,
        ]);

        // 2 adults + 1 child = 3 pax × 50 = 150
        $this->assertSame(150.00, $itinerary->priceForGuests(2, 1));
    }

    public function test_priceForGuests_per_person_transfer_multiplies(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();
        $day = ItinerarySchedule::create(['itinerary_id' => $itinerary->id, 'day' => 1]);

        // unit = 10 + 5 = 15 per_person; pax=4 → 60
        $transfer = $this->buildTransfer(10, 5.0, priceType: 'per_person');
        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $transfer->id,
            'included' => true,
            'bag_count' => 0,
            'waiting_minutes' => 0,
        ]);

        $this->assertSame(60.00, $itinerary->priceForGuests(3, 1));
    }

    public function test_priceForGuests_per_vehicle_transfer_does_not_multiply(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();
        $day = ItinerarySchedule::create(['itinerary_id' => $itinerary->id, 'day' => 1]);

        // unit = 10 + 5 = 15 per_vehicle; pax=4 → 15 (flat)
        $transfer = $this->buildTransfer(10, 5.0, priceType: 'per_vehicle');
        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $transfer->id,
            'included' => true,
        ]);

        $this->assertSame(15.00, $itinerary->priceForGuests(3, 1));
    }

    public function test_priceForGuests_extras_stay_flat(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();
        $day = ItinerarySchedule::create(['itinerary_id' => $itinerary->id, 'day' => 1]);

        // per_vehicle unit 10; bag 2×4=8; waiting 10×0.5=5; total 23 (no pax multiplier)
        $transfer = $this->buildTransfer(5, 5.0, luggagePerBag: 4.0, waitingPerMin: 0.5, priceType: 'per_vehicle');
        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $transfer->id,
            'included' => true,
            'bag_count' => 2,
            'waiting_minutes' => 10,
        ]);

        $this->assertSame(23.00, $itinerary->priceForGuests(5, 0));
    }

    public function test_pricingBreakdown_separates_per_pax_and_flat(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();
        $day = ItinerarySchedule::create(['itinerary_id' => $itinerary->id, 'day' => 1]);

        // Activity 100 (per-pax)
        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $this->makeActivityWithPrice(100.0)->id,
            'price' => null,
            'included' => true,
        ]);

        // Per-person transfer unit 20 (per-pax)
        $tA = $this->buildTransfer(10, 10.0, priceType: 'per_person');
        ItineraryTransfer::create(['schedule_id' => $day->id, 'transfer_id' => $tA->id, 'included' => true]);

        // Per-vehicle transfer unit 30 + 8 luggage + 5 waiting (all flat)
        $tB = $this->buildTransfer(20, 10.0, luggagePerBag: 4.0, waitingPerMin: 0.5, priceType: 'per_vehicle');
        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $tB->id,
            'included' => true,
            'bag_count' => 2,
            'waiting_minutes' => 10,
        ]);

        $b = $itinerary->pricingBreakdown();
        $this->assertSame(120.00, $b['per_pax_total']); // 100 activity + 20 per_person
        $this->assertSame(43.00, $b['flat_total']);     // 30 + 8 + 5

        // priceForGuests(2, 0) = 120 × 2 + 43 = 283
        $this->assertSame(283.00, $itinerary->priceForGuests(2, 0));
    }

    public function test_mixed_unit_total(): void
    {
        Transfer::clearZonePriceCache();

        $itinerary = Itinerary::factory()->create();

        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
        ]);

        // Activity 100, no pax mult
        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $this->makeActivityWithPrice(100.0)->id,
            'price' => null,
            'included' => true,
        ]);

        // Transfer unit 25, no extras
        $transfer = $this->buildTransfer(15, 10.0);
        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $transfer->id,
            'price' => null,
            'included' => true,
        ]);

        $this->assertSame(125.00, $itinerary->schedule_total_price);
    }
}
