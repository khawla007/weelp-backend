<?php

namespace Tests\Feature\Public;

use App\Models\City;
use App\Models\Country;
use App\Models\Itinerary;
use App\Models\ItineraryLocation;
use App\Models\ItineraryMeta;
use App\Models\Region;
use App\Models\State;
use App\Models\User;
use App\Services\CreatorItineraryLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_regions(): void
    {
        Region::factory()->count(2)->create();

        $response = $this->getJson('/api/region');

        $response->assertOk();
    }

    public function test_show_region_by_slug(): void
    {
        $region = Region::factory()->create(['slug' => 'test-region']);

        $response = $this->getJson('/api/region/test-region');

        $response->assertOk();
    }

    public function test_show_region_returns_404_for_missing_slug(): void
    {
        $response = $this->getJson('/api/region/nonexistent-slug');

        $response->assertNotFound();
    }

    public function test_region_lists_hide_all_creator_itineraries(): void
    {
        $region = Region::factory()->create(['slug' => 'visibility-region']);
        $country = Country::factory()->create();
        $region->countries()->attach($country);
        $state = State::factory()->create(['country_id' => $country->id]);
        $city = City::factory()->create(['state_id' => $state->id]);
        $creator = User::factory()->creator()->create();
        $original = Itinerary::factory()->create(['featured_itinerary' => true]);
        ItineraryLocation::create(['itinerary_id' => $original->id, 'city_id' => $city->id]);
        $trashed = $this->creatorItinerary($creator, $city, 'approved');
        $restored = $this->creatorItinerary($creator, $city, 'approved');
        $published = $this->creatorItinerary($creator, $city, 'approved');
        $service = app(CreatorItineraryLifecycleService::class);
        $service->trash($trashed->id);
        $service->trash($restored->id);
        $service->restoreToDraft($restored->id, $creator->id);

        foreach ([
            "/api/homesearch?location={$city->slug}&item_type=itinerary",
            "/api/toursearch?to={$city->slug}",
            "/api/cities/{$city->slug}/all-items?item_type=itinerary",
            '/api/region/visibility-region/region-itineraries',
            '/api/region/visibility-region/region-all-items?item_type=itinerary',
        ] as $url) {
            $response = $this->getJson($url)->assertOk();
            $ids = collect($response->json('data'))->pluck('id');
            $this->assertTrue($ids->contains($original->id), "Original itinerary missing from {$url}");
            $this->assertFalse($ids->contains($trashed->id));
            $this->assertFalse($ids->contains($restored->id));
            $this->assertFalse($ids->contains($published->id));
        }
    }

    private function creatorItinerary(User $creator, City $city, string $status): Itinerary
    {
        $itinerary = Itinerary::factory()->create(['featured_itinerary' => true]);
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $creator->id,
            'status' => $status,
        ]);
        ItineraryLocation::create(['itinerary_id' => $itinerary->id, 'city_id' => $city->id]);

        return $itinerary->fresh('meta');
    }
}
