<?php

namespace Tests\Feature\Creator;

use App\Mail\ItinerarySubmittedAdminMail;
use App\Models\Itinerary;
use App\Models\ItineraryMeta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CreatorItineraryPublishRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_can_request_publication_for_an_owned_standalone_draft(): void
    {
        Mail::fake();
        $creator = User::factory()->creator()->create();
        $admin = User::factory()->admin()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $draft = $this->creatorItinerary($creator, 'draft');

        $this->actingAs($creator, 'api')
            ->postJson("/api/creator/itineraries/{$draft->id}/request-publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('itinerary_meta', [
            'itinerary_id' => $draft->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->id,
            'type' => 'itinerary_publication_requested',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $superAdmin->id,
            'type' => 'itinerary_publication_requested',
        ]);
        Mail::assertSent(ItinerarySubmittedAdminMail::class);

        $this->actingAs($creator, 'api')
            ->postJson("/api/creator/itineraries/{$draft->id}/request-publish")
            ->assertUnprocessable();
    }

    public function test_creator_cannot_request_publication_for_another_creators_draft_or_a_linked_edit_draft(): void
    {
        $creator = User::factory()->creator()->create();
        $otherCreator = User::factory()->creator()->create();
        $otherDraft = $this->creatorItinerary($otherCreator, 'draft');
        $parent = $this->creatorItinerary($creator, 'approved');
        $linkedDraft = $this->creatorItinerary($creator, 'draft');
        $parent->meta->update(['draft_itinerary_id' => $linkedDraft->id]);

        $this->actingAs($creator, 'api')
            ->postJson("/api/creator/itineraries/{$otherDraft->id}/request-publish")
            ->assertNotFound();

        $this->actingAs($creator, 'api')
            ->postJson("/api/creator/itineraries/{$linkedDraft->id}/request-publish")
            ->assertNotFound();
    }

    public function test_admin_approval_clears_publication_request_metadata(): void
    {
        Mail::fake();
        $creator = User::factory()->creator()->create();
        $admin = User::factory()->admin()->create();
        $draft = $this->creatorItinerary($creator, 'draft');
        $this->actingAs($creator, 'api')
            ->postJson("/api/creator/itineraries/{$draft->id}/request-publish")
            ->assertOk();

        $this->actingAs($admin, 'api')
            ->putJson("/api/admin/creator-itineraries/{$draft->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('itinerary_meta', [
            'itinerary_id' => $draft->id,
            'status' => 'approved',
            'publication_requested_at' => null,
        ]);
    }

    public function test_admin_rejection_returns_a_publication_request_to_draft_with_reason(): void
    {
        Mail::fake();
        $creator = User::factory()->creator()->create();
        $admin = User::factory()->admin()->create();
        $draft = $this->creatorItinerary($creator, 'draft');
        $this->actingAs($creator, 'api')
            ->postJson("/api/creator/itineraries/{$draft->id}/request-publish")
            ->assertOk();

        $this->actingAs($admin, 'api')
            ->putJson("/api/admin/creator-itineraries/{$draft->id}/reject", ['reason' => 'Add clearer pricing.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('itinerary_meta', [
            'itinerary_id' => $draft->id,
            'status' => 'draft',
            'publication_rejection_reason' => 'Add clearer pricing.',
        ]);
    }

    public function test_admin_rejection_without_a_reason_keeps_a_durable_needs_changes_signal(): void
    {
        Mail::fake();
        $creator = User::factory()->creator()->create();
        $admin = User::factory()->admin()->create();
        $draft = $this->creatorItinerary($creator, 'draft');

        $this->actingAs($creator, 'api')
            ->postJson("/api/creator/itineraries/{$draft->id}/request-publish")
            ->assertOk();

        $this->actingAs($admin, 'api')
            ->putJson("/api/admin/creator-itineraries/{$draft->id}/reject")
            ->assertOk();

        $this->assertDatabaseHas('itinerary_meta', [
            'itinerary_id' => $draft->id,
            'status' => 'draft',
            'publication_rejection_reason' => 'Changes requested by admin.',
        ]);
    }

    public function test_standalone_draft_cannot_use_the_linked_edit_submission_endpoint(): void
    {
        $creator = User::factory()->creator()->create();
        $draft = $this->creatorItinerary($creator, 'draft');

        $this->actingAs($creator, 'api')
            ->putJson("/api/creator/itineraries/drafts/{$draft->id}/submit")
            ->assertUnprocessable();

        $this->assertDatabaseHas('itinerary_meta', [
            'itinerary_id' => $draft->id,
            'status' => 'draft',
        ]);
    }

    public function test_admin_cannot_publish_while_creator_removal_request_is_pending(): void
    {
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->creator()->create();
        $itinerary = $this->creatorItinerary($creator, 'draft');
        $itinerary->meta->update(['removal_status' => 'requested']);

        $this->actingAs($admin, 'api')
            ->putJson("/api/admin/creator-itineraries/{$itinerary->id}/publish")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['itinerary']);

        $this->assertDatabaseHas('itinerary_meta', [
            'itinerary_id' => $itinerary->id,
            'status' => 'draft',
            'removal_status' => 'requested',
        ]);
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
