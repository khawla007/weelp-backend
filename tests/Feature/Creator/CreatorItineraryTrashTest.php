<?php

namespace Tests\Feature\Creator;

use App\Models\Itinerary;
use App\Models\ItineraryMeta;
use App\Models\User;
use App\Services\CreatorItineraryLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreatorItineraryTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_lifecycle_service_moves_an_itinerary_to_trash_and_restores_it_as_draft(): void
    {
        $creator = User::factory()->creator()->create();
        $itinerary = $this->creatorItinerary($creator, 'approved');

        $service = app(CreatorItineraryLifecycleService::class);
        $service->trash($itinerary->id);

        $this->assertSoftDeleted('itineraries', ['id' => $itinerary->id]);
        $this->assertDatabaseHas('itinerary_meta', [
            'itinerary_id' => $itinerary->id,
            'status' => 'deleted',
            'removal_status' => 'approved',
        ]);

        $service->restoreToDraft($itinerary->id, $creator->id);

        $this->assertNotSoftDeleted('itineraries', ['id' => $itinerary->id]);
        $this->assertDatabaseHas('itinerary_meta', [
            'itinerary_id' => $itinerary->id,
            'status' => 'draft',
            'removal_status' => null,
            'removal_reason' => null,
        ]);
    }

    public function test_linked_edit_draft_cannot_enter_creator_trash(): void
    {
        $creator = User::factory()->creator()->create();
        $parent = $this->creatorItinerary($creator, 'approved');
        $draft = $this->creatorItinerary($creator, 'draft');
        $parent->meta->update(['draft_itinerary_id' => $draft->id]);

        $this->expectException(ValidationException::class);

        app(CreatorItineraryLifecycleService::class)->trash($parent->id);
    }

    public function test_creator_can_list_and_restore_only_their_trashed_itinerary(): void
    {
        $creator = User::factory()->creator()->create();
        $otherCreator = User::factory()->creator()->create();
        $owned = $this->creatorItinerary($creator, 'approved');
        $other = $this->creatorItinerary($otherCreator, 'approved');
        $service = app(CreatorItineraryLifecycleService::class);
        $service->trash($owned->id);
        $service->trash($other->id);

        $this->actingAs($creator, 'api')
            ->getJson('/api/customer/my-itineraries?view=trash')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $owned->id)
            ->assertJsonPath('data.data.0.days_until_purge', 30)
            ->assertJsonMissing(['id' => $other->id]);

        $this->actingAs($creator, 'api')
            ->postJson("/api/creator/itineraries/{$owned->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $this->actingAs($creator, 'api')
            ->postJson("/api/creator/itineraries/{$other->id}/restore")
            ->assertNotFound();
    }

    public function test_restored_draft_cannot_be_booked(): void
    {
        $creator = User::factory()->creator()->customer()->create();
        $itinerary = $this->creatorItinerary($creator, 'approved');
        $service = app(CreatorItineraryLifecycleService::class);
        $service->trash($itinerary->id);
        $service->restoreToDraft($itinerary->id, $creator->id);

        $this->actingAs($creator, 'api')
            ->postJson('/api/customer/itineraries/book', [
                'itinerary_id' => $itinerary->id,
                'travel_date' => now()->addWeek()->toDateString(),
                'number_of_travelers' => 2,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['itinerary_id']);
    }

    public function test_trash_countdown_uses_calendar_days_and_never_becomes_negative(): void
    {
        Carbon::setTestNow('2026-08-08 23:30:00');
        $creator = User::factory()->creator()->create();
        $itinerary = $this->creatorItinerary($creator, 'approved');
        app(CreatorItineraryLifecycleService::class)->trash($itinerary->id);

        Itinerary::onlyTrashed()->whereKey($itinerary->id)->update([
            'deleted_at' => now()->subDays(29)->startOfDay(),
        ]);
        $this->actingAs($creator, 'api')
            ->getJson('/api/customer/my-itineraries?view=trash')
            ->assertJsonPath('data.data.0.days_until_purge', 1);

        Itinerary::onlyTrashed()->whereKey($itinerary->id)->update([
            'deleted_at' => now()->subDays(31),
        ]);
        $this->actingAs($creator, 'api')
            ->getJson('/api/customer/my-itineraries?view=trash')
            ->assertJsonPath('data.data.0.days_until_purge', 0);
    }

    private function creatorItinerary(User $creator, string $status): Itinerary
    {
        $itinerary = Itinerary::factory()->create();
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $creator->id,
            'status' => $status,
        ]);

        return $itinerary->fresh('meta');
    }
}
