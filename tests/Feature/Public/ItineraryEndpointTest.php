<?php

namespace Tests\Feature\Public;

use App\Models\Itinerary;
use App\Models\ItineraryMeta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItineraryEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_itineraries_returns_success(): void
    {
        Itinerary::factory()->count(3)->create();

        $response = $this->getJson('/api/itineraries');

        $response->assertOk();
    }

    public function test_show_itinerary_by_slug(): void
    {
        $itinerary = Itinerary::factory()->create(['slug' => 'test-itinerary']);

        $response = $this->getJson('/api/itineraries/test-itinerary');

        $response->assertOk();
    }

    public function test_show_itinerary_marks_approved_creator_itineraries(): void
    {
        $creator = User::factory()->create();
        $itinerary = Itinerary::factory()->create(['slug' => 'creator-public-itinerary']);
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/itineraries/creator-public-itinerary');

        $response->assertOk()
            ->assertJsonPath('data.is_creator_itinerary', true);
    }

    public function test_show_itinerary_returns_404_for_missing_slug(): void
    {
        $response = $this->getJson('/api/itineraries/nonexistent-slug');

        $response->assertNotFound();
    }
}
