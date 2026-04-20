<?php

namespace Tests\Feature\Public;

use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_activities_returns_success(): void
    {
        Activity::factory()->count(3)->create();

        $response = $this->getJson('/api/activities');

        $response->assertOk();
    }

    public function test_show_activity_by_slug(): void
    {
        $activity = Activity::factory()->create(['slug' => 'test-activity']);

        $response = $this->getJson('/api/activities/test-activity');

        $response->assertOk();
    }

    public function test_show_activity_returns_404_for_missing_slug(): void
    {
        $response = $this->getJson('/api/activities/nonexistent-slug');

        $response->assertNotFound();
    }
}
