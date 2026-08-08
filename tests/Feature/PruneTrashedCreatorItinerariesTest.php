<?php

namespace Tests\Feature;

use App\Models\Itinerary;
use App\Models\ItineraryMeta;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\CreatorItineraryLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PruneTrashedCreatorItinerariesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_is_dry_run_by_default_and_execute_prunes_only_expired_trash(): void
    {
        Carbon::setTestNow('2026-08-08 12:00:00');
        $creator = User::factory()->creator()->create();
        $customer = User::factory()->customer()->create();
        $expired = $this->creatorItinerary($creator);
        $recent = $this->creatorItinerary($creator);
        $service = app(CreatorItineraryLifecycleService::class);
        $service->trash($expired->id);
        $service->trash($recent->id);
        Itinerary::onlyTrashed()->whereKey($expired->id)->update(['deleted_at' => now()->subDays(30)]);
        Itinerary::onlyTrashed()->whereKey($recent->id)->update(['deleted_at' => now()->subDays(29)]);
        WishlistItem::create([
            'user_id' => $customer->id,
            'item_type' => WishlistItem::TYPE_ITINERARY,
            'item_id' => $expired->id,
            'title' => $expired->name,
            'slug' => $expired->slug,
        ]);
        $review = Review::create([
            'user_id' => $customer->id,
            'item_type' => 'itinerary',
            'item_id' => $expired->id,
            'rating' => 5,
            'status' => 'approved',
        ]);
        $historicalOrder = Order::factory()->create([
            'user_id' => $customer->id,
            'orderable_type' => Itinerary::class,
            'orderable_id' => $expired->id,
        ]);
        $historicalReview = Review::create([
            'user_id' => $customer->id,
            'order_id' => $historicalOrder->id,
            'item_type' => 'itinerary',
            'item_id' => $expired->id,
            'rating' => 4,
            'status' => 'approved',
        ]);

        $this->artisan('itineraries:prune-trash')->assertSuccessful();
        $this->assertDatabaseHas('itineraries', ['id' => $expired->id]);

        $this->artisan('itineraries:prune-trash --execute --days=30')->assertSuccessful();

        $this->assertDatabaseMissing('itineraries', ['id' => $expired->id]);
        $this->assertDatabaseHas('itineraries', ['id' => $recent->id]);
        $this->assertDatabaseMissing('wishlist_items', ['item_id' => $expired->id, 'item_type' => 'itinerary']);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseHas('orders', ['id' => $historicalOrder->id]);
        $this->assertDatabaseHas('reviews', ['id' => $historicalReview->id, 'order_id' => $historicalOrder->id]);

        $this->artisan('itineraries:prune-trash --execute --days=30')->assertSuccessful();
    }

    private function creatorItinerary(User $creator): Itinerary
    {
        $itinerary = Itinerary::factory()->create();
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);

        return $itinerary->fresh('meta');
    }
}
