<?php

namespace Tests\Feature\Public;

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

    public function test_regions_cities_endpoint(): void
    {
        $response = $this->getJson('/api/regions-cities');

        $response->assertOk();
    }
}
