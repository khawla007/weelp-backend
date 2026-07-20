<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItineraryBasePricing;
use App\Models\ItineraryMeta;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryTransfer;
use App\Models\Transfer;
use App\Models\TransferRoute;
use App\Models\TransferZone;
use App\Models\TransferZonePrice;
use App\Models\TransferPricingAvailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CreatorItineraryExploreIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_price_is_schedule_total_not_base_pricing_variation(): void
    {
        Transfer::clearZonePriceCache();

        $creator = User::factory()->create();

        $itinerary = Itinerary::factory()->create();
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);

        // Base pricing with an intentionally wrong variation (999.99)
        $basePricing = ItineraryBasePricing::create([
            'itinerary_id' => $itinerary->id,
            'currency' => 'USD',
            'availability' => 'daily',
        ]);
        $basePricing->variations()->create([
            'name' => 'Default',
            'regular_price' => 999.99,
            'sale_price' => 888.88,
            'max_guests' => 1,
        ]);

        // Schedule sum = 50 + 75 + 30 = 155
        $day = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
            'title' => 'Day 1',
        ]);

        $activity = Activity::create([
            'name' => 'Test Activity',
            'slug' => 'test-activity-' . uniqid(),
        ]);

        // Create transfer with proper zone pricing (zone: 20 + transfer: 10 = 30)
        $transfer = Transfer::create([
            'name' => 'Test Transfer',
            'slug' => 'test-transfer-' . uniqid(),
            'description' => 'A test transfer',
            'transfer_type' => 'private',
        ]);

        // Setup zone pricing for the transfer (20 + 10 = 30)
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
            'base_price' => 20,
            'currency' => 'USD',
        ]);

        TransferPricingAvailability::create([
            'transfer_id' => $transfer->id,
            'transfer_price' => 10,
            'currency' => 'USD',
            'is_vendor' => false,
        ]);

        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $activity->id,
            'price' => 50.00,
            'included' => true,
        ]);
        ItineraryActivity::create([
            'schedule_id' => $day->id,
            'activity_id' => $activity->id,
            'price' => 75.00,
            'included' => true,
        ]);
        ItineraryTransfer::create([
            'schedule_id' => $day->id,
            'transfer_id' => $transfer->id,
            'price' => 30.00, // This stored value should be ignored; live computation is 20+10=30
            'included' => true,
        ]);

        $response = $this->getJson('/api/creator/explore');

        $response->assertOk();
        $data = $response->json('data');
        $found = collect($data)->firstWhere('id', $itinerary->id);

        $this->assertNotNull($found, 'Seeded itinerary missing from response');
        $this->assertSame(155.00, (float) $found['display_price']);
        $this->assertNotSame(999.99, (float) $found['display_price']);
    }

    public function test_public_view_endpoint_increments_creator_itinerary_seen_count(): void
    {
        $creator = User::factory()->create();

        $itinerary = Itinerary::factory()->create();
        $meta = ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $creator->id,
            'status' => 'approved',
            'views_count' => 4,
        ]);

        $response = $this->postJson("/api/creator/explore/{$itinerary->id}/view");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'views_count' => 5,
            ]);

        $this->assertSame(5, $meta->fresh()->views_count);
    }

    public function test_public_view_endpoint_has_named_throttle_middleware(): void
    {
        $route = collect(app('router')->getRoutes())
            ->first(fn ($route) => $route->uri() === 'api/creator/explore/{id}/view');

        $this->assertNotNull($route, 'Creator explore view route is not registered');
        $this->assertContains('throttle:creator_explore_view', $route->gatherMiddleware());
    }

    public function test_public_view_endpoint_throttles_repeated_hits_per_itinerary_and_ip(): void
    {
        $creator = User::factory()->create();

        $itinerary = Itinerary::factory()->create();
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $creator->id,
            'status' => 'approved',
            'views_count' => 0,
        ]);

        RateLimiter::clear($itinerary->id.'|127.0.0.1');

        for ($i = 0; $i < 10; $i++) {
            $this->postJson("/api/creator/explore/{$itinerary->id}/view")->assertOk();
        }

        $this->postJson("/api/creator/explore/{$itinerary->id}/view")->assertTooManyRequests();
    }
}
