<?php

namespace Tests\Feature\Public;

use App\Models\Region;
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
}
