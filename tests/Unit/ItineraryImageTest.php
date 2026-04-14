<?php

namespace Tests\Unit;

use App\Models\Itinerary;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ItineraryImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_featured_image_returns_itinerary_featured_image()
    {
        $itinerary = Itinerary::factory()->withFeaturedImage()->create();
        $this->assertNotNull($itinerary->featured_image);
        $this->assertStringContainsString('http', $itinerary->featured_image);
    }

    public function test_featured_image_falls_back_to_activity_image()
    {
        $itinerary = Itinerary::factory()
            ->withScheduleAndActivity()
            ->create();

        // Ensure no itinerary featured image
        $this->assertNull($itinerary->mediaGallery->firstWhere('is_featured', true)?->media?->url);

        // Should fall back to activity image
        $this->assertNotNull($itinerary->featured_image);
    }

    public function test_featured_image_returns_null_when_no_images()
    {
        $itinerary = Itinerary::factory()->create();
        $this->assertNull($itinerary->featured_image);
    }

    public function test_gallery_images_deduplicates()
    {
        $itinerary = Itinerary::factory()
            ->withGalleryImages(['url1.jpg', 'url2.jpg'])
            ->withScheduleAndActivity()
            ->create();

        $gallery = $itinerary->gallery_images;
        $urls = array_column($gallery, 'url');

        // Check no duplicates
        $this->assertEquals(count($urls), count(array_unique($urls)));
    }

    public function test_gallery_images_includes_activity_images()
    {
        $itinerary = Itinerary::factory()
            ->withScheduleAndActivity()
            ->create();

        $gallery = $itinerary->gallery_images;
        $this->assertGreaterThan(0, count($gallery));
    }
}
