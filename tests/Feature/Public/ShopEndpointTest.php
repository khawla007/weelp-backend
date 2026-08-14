<?php

namespace Tests\Feature\Public;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Itinerary;
use App\Models\ItineraryMeta;
use App\Models\Media;
use App\Models\Package;
use App\Models\User;
use App\Services\CreatorItineraryLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_returns_combined_listing(): void
    {
        Activity::factory()->count(2)->create();

        $response = $this->getJson('/api/shop');

        $response->assertOk();
    }

    public function test_shop_filters_by_category(): void
    {
        $category = Category::factory()->create(['slug' => 'adventure']);

        // The shop controller uses 'categories' param (not 'category')
        // When a valid category exists but no items match, it returns 404
        $response = $this->getJson('/api/shop?categories=adventure');

        $response->assertNotFound();
    }

    public function test_shop_returns_media_proxy_urls_for_every_item_type(): void
    {
        $items = [
            'activity' => Activity::factory()->create(),
            'itinerary' => Itinerary::factory()->create(),
            'package' => Package::factory()->create(),
        ];

        foreach ($items as $type => $item) {
            $media = Media::create([
                'name' => "Featured {$type} image",
                'url' => "countries/random-tourist-places/test/{$type}.jpg",
            ]);
            $item->mediaGallery()->create([
                'media_id' => $media->id,
                'is_featured' => true,
            ]);
        }

        $response = $this->getJson('/api/shop');

        $response->assertOk();
        $data = collect($response->json('data'));

        foreach ($items as $type => $item) {
            $shopItem = $data->firstWhere('item_type', $type);

            $this->assertNotNull($shopItem);
            $this->assertSame(
                $item->mediaGallery()->first()->media->url,
                $shopItem['featured_image'] ?? null,
            );
        }
    }

    public function test_shop_hides_all_creator_itineraries(): void
    {
        $creator = User::factory()->creator()->create();
        $original = Itinerary::factory()->create();
        $trashed = $this->creatorItinerary($creator, 'approved');
        $restored = $this->creatorItinerary($creator, 'approved');
        $published = $this->creatorItinerary($creator, 'approved');
        $service = app(CreatorItineraryLifecycleService::class);
        $service->trash($trashed->id);
        $service->trash($restored->id);
        $service->restoreToDraft($restored->id, $creator->id);

        $response = $this->getJson('/api/shop')->assertOk();

        $ids = collect($response->json('data'))->where('item_type', 'itinerary')->pluck('id');
        $this->assertTrue($ids->contains($original->id));
        $this->assertFalse($ids->contains($trashed->id));
        $this->assertFalse($ids->contains($restored->id));
        $this->assertFalse($ids->contains($published->id));
    }

    private function creatorItinerary(User $creator, string $status): Itinerary
    {
        $itinerary = Itinerary::factory()->create();
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $creator->id,
            'status' => $status,
        ]);

        return $itinerary->fresh('meta');
    }
}
