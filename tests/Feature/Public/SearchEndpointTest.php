<?php

namespace Tests\Feature\Public;

use App\Models\Activity;
use App\Models\ActivityLocation;
use App\Models\City;
use App\Models\Package;
use App\Models\PackageLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_search_returns_results(): void
    {
        $response = $this->getJson('/api/homesearch?location=test');

        // Controller returns 200 with validation passing (location is provided)
        // It may return empty results, but should not return 422
        $response->assertOk();
    }

    public function test_home_search_excludes_packages_and_limits_preview_results_to_five(): void
    {
        $city = City::factory()->create(['slug' => 'dubai']);

        Activity::factory()
            ->count(6)
            ->sequence(fn ($sequence) => [
                'name' => 'Activity '.$sequence->index,
                'slug' => 'activity-'.$sequence->index,
            ])
            ->create()
            ->each(fn (Activity $activity) => ActivityLocation::create([
                'activity_id' => $activity->id,
                'city_id' => $city->id,
                'location_type' => 'primary',
            ]));

        $package = Package::factory()->create([
            'name' => 'Hidden Package',
            'slug' => 'hidden-package',
        ]);

        PackageLocation::create([
            'package_id' => $package->id,
            'city_id' => $city->id,
        ]);

        $response = $this->getJson('/api/homesearch?location=dubai');

        $response->assertOk()
            ->assertJsonPath('success', 'true')
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonCount(5, 'data');

        $itemTypes = collect($response->json('data'))->pluck('item_type');

        $this->assertFalse($itemTypes->contains('package'));
        $this->assertEquals(['activity'], $itemTypes->unique()->values()->all());
    }

    public function test_regions_cities_endpoint(): void
    {
        $response = $this->getJson('/api/regions-cities');

        $response->assertOk();
    }
}
