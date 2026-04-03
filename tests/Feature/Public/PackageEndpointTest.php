<?php

namespace Tests\Feature\Public;

use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_packages_returns_success(): void
    {
        Package::factory()->count(3)->create();

        $response = $this->getJson('/api/packages');

        $response->assertOk();
    }

    public function test_show_package_by_slug(): void
    {
        $package = Package::factory()->create(['slug' => 'test-package']);

        $response = $this->getJson('/api/packages/test-package');

        $response->assertOk();
    }

    public function test_show_package_returns_404_for_missing_slug(): void
    {
        $response = $this->getJson('/api/packages/nonexistent-slug');

        $response->assertNotFound();
    }
}
