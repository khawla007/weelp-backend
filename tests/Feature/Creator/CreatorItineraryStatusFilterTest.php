<?php

namespace Tests\Feature\Creator;

use App\Models\Itinerary;
use App\Models\ItineraryMeta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorItineraryStatusFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_status_groups_filter_owned_itineraries_without_hiding_saved_copies_from_all(): void
    {
        $creator = User::factory()->creator()->create();
        $otherCreator = User::factory()->creator()->create();

        $draft = $this->creatorItinerary($creator, 'draft');
        $pending = $this->creatorItinerary($creator, 'pending');
        $approved = $this->creatorItinerary($creator, 'approved');
        $rejected = $this->creatorItinerary($creator, 'rejected');
        $needsChanges = $this->creatorItinerary($creator, 'draft', 'Add clearer pricing.');

        $editParent = $this->creatorItinerary($creator, 'approved');
        $editDraft = $this->creatorItinerary($creator, 'edit_pending');
        $editParent->meta->update(['draft_itinerary_id' => $editDraft->id]);

        $foreignPending = $this->creatorItinerary($otherCreator, 'pending');
        $foreignParent = $this->creatorItinerary($otherCreator, 'approved');
        $foreignEditDraft = $this->creatorItinerary($otherCreator, 'edit_pending');
        $foreignParent->meta->update(['draft_itinerary_id' => $foreignEditDraft->id]);

        $saved = $this->savedItinerary($creator);
        $foreignSaved = $this->savedItinerary($otherCreator);

        $allIds = $this->responseIds($creator, '/api/customer/my-itineraries');
        $this->assertContains($saved->id, $allIds);
        $this->assertContains($draft->id, $allIds);
        $this->assertNotContains($editDraft->id, $allIds);
        $this->assertNotContains($foreignPending->id, $allIds);
        $this->assertNotContains($foreignParent->id, $allIds);
        $this->assertNotContains($foreignEditDraft->id, $allIds);
        $this->assertNotContains($foreignSaved->id, $allIds);

        $this->assertSame([$draft->id], $this->responseIds($creator, '/api/customer/my-itineraries?status=draft'));
        $this->assertEqualsCanonicalizing(
            [$pending->id, $editParent->id],
            $this->responseIds($creator, '/api/customer/my-itineraries?status=under_review'),
        );
        $this->assertEqualsCanonicalizing(
            [$approved->id, $editParent->id],
            $this->responseIds($creator, '/api/customer/my-itineraries?status=published'),
        );
        $this->assertEqualsCanonicalizing(
            [$rejected->id, $needsChanges->id],
            $this->responseIds($creator, '/api/customer/my-itineraries?status=needs_changes'),
        );
    }

    /** @return array<int, int> */
    private function responseIds(User $user, string $url): array
    {
        $response = $this->actingAs($user, 'api')->getJson($url)->assertOk();

        return collect($response->json('data.data'))->pluck('id')->all();
    }

    private function creatorItinerary(User $creator, string $status, ?string $rejectionReason = null): Itinerary
    {
        $itinerary = Itinerary::factory()->create();
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $creator->id,
            'status' => $status,
            'publication_rejection_reason' => $rejectionReason,
        ]);

        return $itinerary->fresh('meta');
    }

    private function savedItinerary(User $owner): Itinerary
    {
        $itinerary = Itinerary::factory()->create();
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'user_id' => $owner->id,
        ]);

        return $itinerary->fresh('meta');
    }
}
