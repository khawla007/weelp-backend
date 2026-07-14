<?php

namespace Tests\Feature\Payment;

use App\Models\Activity;
use App\Models\ActivityAddon;
use App\Models\ActivityAvailability;
use App\Models\ActivityPricing;
use App\Models\Addon;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItineraryAvailability;
use App\Models\ItineraryBasePricing;
use App\Models\ItinerarySchedule;
use App\Models\Package;
use App\Models\PackageBasePricing;
use App\Models\PackagePriceVariation;
use App\Models\Transfer;
use App\Models\TransferPricingAvailability;
use App\Models\TransferRoute;
use App\Models\TransferSchedule;
use App\Models\TransferZonePrice;
use App\Services\CheckoutQuoteService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_quote_uses_database_prices_and_deduplicates_addons(): void
    {
        $activity = Activity::factory()->create();
        ActivityPricing::factory()->create([
            'activity_id' => $activity->id,
            'regular_price' => 100,
            'currency' => 'USD',
        ]);
        $addon = Addon::create([
            'name' => 'WiFi',
            'price' => 20,
            'sale_price' => 5,
            'active_status' => true,
        ]);
        ActivityAddon::create(['activity_id' => $activity->id, 'addon_id' => $addon->id]);

        $quote = app(CheckoutQuoteService::class)->quote([
            'order_type' => 'activity',
            'orderable_id' => $activity->id,
            'travel_date' => now()->addWeek()->toDateString(),
            'number_of_adults' => 2,
            'number_of_children' => 1,
            'addon_ids' => [$addon->id, $addon->id],
        ]);

        $this->assertSame(305.0, $quote['amount']);
        $this->assertSame(300.0, $quote['base_amount']);
        $this->assertSame(5.0, $quote['addons_amount']);
        $this->assertSame('USD', $quote['currency']);
        $this->assertSame([[
            'addon_id' => $addon->id,
            'addon_name' => 'WiFi',
            'price' => 5.0,
        ]], $quote['addons']);
    }

    public function test_quote_rejects_inactive_or_foreign_addons(): void
    {
        $activity = Activity::factory()->create();
        ActivityPricing::factory()->create(['activity_id' => $activity->id, 'regular_price' => 100]);
        $foreignAddon = Addon::create([
            'name' => 'Foreign',
            'price' => 10,
            'active_status' => true,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('addon_invalid');

        app(CheckoutQuoteService::class)->quote([
            'order_type' => 'activity',
            'orderable_id' => $activity->id,
            'travel_date' => now()->addWeek()->toDateString(),
            'number_of_adults' => 1,
            'number_of_children' => 0,
            'addon_ids' => [$foreignAddon->id],
        ]);
    }

    public function test_quote_rejects_zero_guests_and_missing_item(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('item_unavailable');

        app(CheckoutQuoteService::class)->quote([
            'order_type' => 'activity',
            'orderable_id' => 999999,
            'travel_date' => now()->addWeek()->toDateString(),
            'number_of_adults' => 0,
            'number_of_children' => 0,
        ]);
    }

    public function test_activity_quote_rejects_a_date_outside_catalog_availability(): void
    {
        $activity = Activity::factory()->create();
        ActivityPricing::factory()->create(['activity_id' => $activity->id, 'regular_price' => 100]);
        ActivityAvailability::create([
            'activity_id' => $activity->id,
            'date_based_activity' => true,
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'quantity_based_activity' => false,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('item_date_unavailable');

        app(CheckoutQuoteService::class)->quote([
            'order_type' => 'activity',
            'orderable_id' => $activity->id,
            'travel_date' => now()->addWeek()->toDateString(),
            'number_of_adults' => 1,
            'number_of_children' => 0,
        ]);
    }

    public function test_package_quote_uses_selected_database_variation(): void
    {
        $package = Package::factory()->create();
        $base = PackageBasePricing::create([
            'package_id' => $package->id,
            'currency' => 'AED',
            'availability' => 'available',
        ]);
        $variation = PackagePriceVariation::create([
            'base_pricing_id' => $base->id,
            'name' => 'Family',
            'regular_price' => 500,
            'sale_price' => 450,
            'max_guests' => 4,
        ]);

        $quote = app(CheckoutQuoteService::class)->quote([
            'order_type' => 'package',
            'orderable_id' => $package->id,
            'travel_date' => now()->addWeek()->toDateString(),
            'number_of_adults' => 2,
            'number_of_children' => 1,
            'variation_id' => $variation->id,
        ]);

        $this->assertSame(450.0, $quote['amount']);
        $this->assertSame('AED', $quote['currency']);
        $this->assertSame($variation->id, $quote['variation_id']);
    }

    public function test_itinerary_quote_uses_guest_pricing(): void
    {
        $itinerary = Itinerary::factory()->create();
        ItineraryBasePricing::create([
            'itinerary_id' => $itinerary->id,
            'currency' => 'USD',
            'availability' => 'year_round',
        ]);
        $schedule = ItinerarySchedule::factory()->create(['itinerary_id' => $itinerary->id]);
        $activity = Activity::factory()->create();
        ActivityPricing::factory()->create(['activity_id' => $activity->id, 'regular_price' => 50]);
        ItineraryActivity::factory()->create([
            'schedule_id' => $schedule->id,
            'activity_id' => $activity->id,
            'price' => 1,
        ]);

        $quote = app(CheckoutQuoteService::class)->quote([
            'order_type' => 'itinerary',
            'orderable_id' => $itinerary->id,
            'travel_date' => now()->addWeek()->toDateString(),
            'number_of_adults' => 2,
            'number_of_children' => 1,
        ]);

        $this->assertSame(150.0, $quote['amount']);
        $this->assertSame('USD', $quote['currency']);
    }

    public function test_itinerary_quote_rejects_catalog_quantity_overflow(): void
    {
        $itinerary = Itinerary::factory()->create();
        ItineraryAvailability::create([
            'itinerary_id' => $itinerary->id,
            'date_based_itinerary' => false,
            'quantity_based_itinerary' => true,
            'max_quantity' => 2,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('item_capacity_exceeded');

        app(CheckoutQuoteService::class)->quote([
            'order_type' => 'itinerary',
            'orderable_id' => $itinerary->id,
            'travel_date' => now()->addWeek()->toDateString(),
            'number_of_adults' => 3,
            'number_of_children' => 0,
        ]);
    }

    public function test_transfer_quote_uses_database_route_and_quantity_rates(): void
    {
        Transfer::clearZonePriceCache();
        $route = TransferRoute::factory()->create();
        TransferZonePrice::factory()->create([
            'from_zone_id' => $route->from_zone_id,
            'to_zone_id' => $route->to_zone_id,
            'base_price' => 40,
            'currency' => 'USD',
        ]);
        $transfer = Transfer::factory()->create(['transfer_route_id' => $route->id]);
        TransferPricingAvailability::factory()->create([
            'transfer_id' => $transfer->id,
            'is_vendor' => false,
            'transfer_price' => 20,
            'currency' => 'USD',
            'price_type' => 'per_vehicle',
            'extra_luggage_charge' => 5,
            'waiting_charge' => 2,
        ]);

        $quote = app(CheckoutQuoteService::class)->quote([
            'order_type' => 'transfer',
            'orderable_id' => $transfer->id,
            'travel_date' => now()->addWeek()->toDateString(),
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'bag_count' => 2,
            'waiting_minutes' => 3,
        ]);

        $this->assertSame(76.0, $quote['amount']);
        $this->assertSame(60.0, $quote['base_amount']);
        $this->assertSame(16.0, $quote['addons_amount']);
        $this->assertSame('USD', $quote['currency']);
    }

    public function test_transfer_quote_rejects_capacity_overflow(): void
    {
        $transfer = Transfer::factory()->create();
        TransferSchedule::create([
            'transfer_id' => $transfer->id,
            'availability_type' => 'always_available',
            'blackout_dates' => [now()->addWeek()->toDateString()],
            'maximum_passengers' => 2,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('item_capacity_exceeded');

        app(CheckoutQuoteService::class)->quote([
            'order_type' => 'transfer',
            'orderable_id' => $transfer->id,
            'travel_date' => now()->addWeek()->toDateString(),
            'number_of_adults' => 3,
            'number_of_children' => 0,
        ]);
    }

    public function test_transfer_quote_rejects_a_blackout_date(): void
    {
        $transfer = Transfer::factory()->create();
        TransferSchedule::create([
            'transfer_id' => $transfer->id,
            'availability_type' => 'always_available',
            'blackout_dates' => [now()->addWeek()->toDateString()],
            'maximum_passengers' => 4,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('item_date_unavailable');

        app(CheckoutQuoteService::class)->quote([
            'order_type' => 'transfer',
            'orderable_id' => $transfer->id,
            'travel_date' => now()->addWeek()->toDateString(),
            'number_of_adults' => 1,
            'number_of_children' => 0,
        ]);
    }
}
