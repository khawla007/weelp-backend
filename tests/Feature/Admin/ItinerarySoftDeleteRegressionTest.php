<?php

namespace Tests\Feature\Admin;

use App\Models\Itinerary;
use App\Models\ItineraryMeta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItinerarySoftDeleteRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_delete_physically_removes_only_original_itineraries(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $original = Itinerary::factory()->create();

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/admin/itineraries/{$original->id}")
            ->assertOk();

        $this->assertDatabaseMissing('itineraries', ['id' => $original->id]);
    }

    public function test_catalog_delete_cannot_bypass_creator_trash(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $creator = User::factory()->creator()->create();
        $creatorItinerary = Itinerary::factory()->create();
        ItineraryMeta::create([
            'itinerary_id' => $creatorItinerary->id,
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/admin/itineraries/{$creatorItinerary->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('itineraries', ['id' => $creatorItinerary->id]);
    }

    public function test_catalog_bulk_delete_rejects_the_entire_batch_when_it_contains_a_creator_copy(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $original = Itinerary::factory()->create();
        $creator = User::factory()->creator()->create();
        $creatorItinerary = Itinerary::factory()->create();
        ItineraryMeta::create([
            'itinerary_id' => $creatorItinerary->id,
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);

        $this->actingAs($admin, 'api')
            ->postJson('/api/admin/itineraries/bulk-delete', [
                'itinerary_ids' => [$original->id, $creatorItinerary->id],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('itineraries', ['id' => $original->id]);
        $this->assertDatabaseHas('itineraries', ['id' => $creatorItinerary->id]);
    }
}
