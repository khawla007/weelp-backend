<?php

namespace Tests\Feature\Admin;

use App\Mail\ItineraryApprovedMail;
use App\Mail\ItineraryTrashedMail;
use App\Models\Itinerary;
use App\Models\ItineraryMeta;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CreatorItineraryTrashAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_direct_removal_moves_creator_itinerary_to_shared_trash_and_notifies_creator(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->creator()->create();
        $itinerary = $this->creatorItinerary($creator, 'approved');

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/admin/creator-itineraries/{$itinerary->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Itinerary moved to Trash.');

        $this->assertSoftDeleted('itineraries', ['id' => $itinerary->id]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $creator->id,
            'type' => 'itinerary_trashed',
        ]);
        $notification = Notification::where('user_id', $creator->id)
            ->where('type', 'itinerary_trashed')
            ->firstOrFail();
        $this->assertSame($itinerary->id, $notification->data['itinerary_id']);
        $this->assertNotEmpty($notification->data['deleted_at']);
        $this->assertNotEmpty($notification->data['purge_at']);
        Mail::assertSent(ItineraryTrashedMail::class, function (ItineraryTrashedMail $mail) use ($itinerary): bool {
            $html = $mail->render();

            return str_contains($html, "Itinerary ID: {$itinerary->id}")
                && str_contains($html, 'Moved to Trash at:');
        });

        $this->actingAs($admin, 'api')
            ->getJson('/api/admin/creator-itineraries?view=trash')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $itinerary->id);
    }

    public function test_admin_can_restore_to_draft_publish_and_permanently_delete_from_trash(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->creator()->create();
        $itinerary = $this->creatorItinerary($creator, 'approved');

        $this->actingAs($admin, 'api')->deleteJson("/api/admin/creator-itineraries/{$itinerary->id}")->assertOk();
        $this->actingAs($admin, 'api')
            ->postJson("/api/admin/creator-itineraries/{$itinerary->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $this->actingAs($admin, 'api')
            ->putJson("/api/admin/creator-itineraries/{$itinerary->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
        Mail::assertSent(ItineraryApprovedMail::class);

        $this->actingAs($admin, 'api')->deleteJson("/api/admin/creator-itineraries/{$itinerary->id}")->assertOk();
        $this->actingAs($admin, 'api')
            ->deleteJson("/api/admin/creator-itineraries/{$itinerary->id}/force")
            ->assertOk();
        $this->assertDatabaseMissing('itineraries', ['id' => $itinerary->id]);
    }

    public function test_creator_cannot_use_admin_permanent_delete(): void
    {
        $creator = User::factory()->creator()->create();
        $itinerary = $this->creatorItinerary($creator, 'approved');

        $this->actingAs($creator, 'api')
            ->deleteJson("/api/admin/creator-itineraries/{$itinerary->id}/force")
            ->assertForbidden();
    }

    public function test_admin_approval_of_creator_removal_request_moves_itinerary_to_trash(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->creator()->create();
        $itinerary = $this->creatorItinerary($creator, 'approved');
        $itinerary->meta->update(['removal_status' => 'requested']);

        $this->actingAs($admin, 'api')
            ->putJson("/api/admin/creator-itineraries/{$itinerary->id}/approve-removal")
            ->assertOk();

        $this->assertSoftDeleted('itineraries', ['id' => $itinerary->id]);
        $this->actingAs($creator, 'api')
            ->getJson('/api/customer/my-itineraries?view=trash')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $itinerary->id);
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
