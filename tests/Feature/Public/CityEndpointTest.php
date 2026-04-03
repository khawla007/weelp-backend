<?php

namespace Tests\Feature\Public;

use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_cities(): void
    {
        City::factory()->count(2)->create();

        $response = $this->getJson('/api/cities');

        $response->assertOk();
    }

    public function test_show_city_by_slug(): void
    {
        $city = City::factory()->create(['slug' => 'test-city']);

        $response = $this->getJson('/api/city/test-city');

        $response->assertOk();
    }

    public function test_show_city_returns_404_for_missing_slug(): void
    {
        $response = $this->getJson('/api/city/nonexistent-slug');

        $response->assertNotFound();
    }
}
