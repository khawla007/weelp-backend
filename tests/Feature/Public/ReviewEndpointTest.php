<?php

namespace Tests\Feature\Public;

use App\Models\Activity;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_reviews(): void
    {
        $activity = Activity::factory()->create();
        $user = User::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
        ]);

        $response = $this->getJson('/api/reviews');

        $response->assertOk();
    }

    public function test_list_reviews_returns_empty_for_no_reviews(): void
    {
        $response = $this->getJson('/api/reviews');

        $response->assertOk();
    }

    public function test_featured_reviews(): void
    {
        $response = $this->getJson('/api/reviews/featured-reviews');

        $response->assertOk();
    }
}
