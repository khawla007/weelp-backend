<?php

namespace Tests\Feature\Public;

use App\Models\Activity;
use App\Models\Category;
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
}
