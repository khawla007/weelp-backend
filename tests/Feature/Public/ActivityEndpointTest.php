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

    public function test_activity_can_have_inclusions_and_exclusions_relation(): void
    {
        $activity = Activity::factory()->create();

        $activity->inclusionsExclusions()->create([
            'type' => 'transfer',
            'title' => 'Hotel pickup',
            'description' => 'Shared transfer from selected hotels.',
            'included' => true,
        ]);

        $this->assertDatabaseHas('activity_inclusions_exclusions', [
            'activity_id' => $activity->id,
            'type' => 'transfer',
            'title' => 'Hotel pickup',
            'included' => true,
        ]);
    }

    public function test_show_activity_includes_inclusions_exclusions(): void
    {
        $activity = Activity::factory()->create(['slug' => 'activity-with-inclusions']);
        $activity->inclusionsExclusions()->create([
            'type' => 'meal',
            'title' => 'Lunch',
            'description' => 'Local lunch during the activity.',
            'included' => true,
        ]);

        $this->getJson('/api/activities/activity-with-inclusions')
            ->assertOk()
            ->assertJsonPath('data.inclusions_exclusions.0.type', 'meal')
            ->assertJsonPath('data.inclusions_exclusions.0.title', 'Lunch')
            ->assertJsonPath('data.inclusions_exclusions.0.included', true);
    }
}
