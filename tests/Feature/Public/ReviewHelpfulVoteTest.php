<?php

namespace Tests\Feature\Public;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\ItineraryMeta;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReviewHelpfulVoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_add_a_helpful_vote(): void
    {
        [$review] = $this->activityReview();

        $this->putJson("/api/reviews/{$review->id}/helpful")
            ->assertUnauthorized();
    }

    public function test_guest_cannot_remove_a_helpful_vote(): void
    {
        [$review] = $this->activityReview();

        $this->deleteJson("/api/reviews/{$review->id}/helpful")
            ->assertUnauthorized();
    }

    public function test_guest_cannot_get_helpful_status(): void
    {
        [$review] = $this->activityReview();
        $query = $this->reviewIdsQuery([$review->id]);

        $this->getJson("/api/reviews/helpful-status?{$query}")
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_add_and_remove_a_helpful_vote_idempotently(): void
    {
        [$review] = $this->activityReview();
        $voter = User::factory()->create();

        $this->actingAs($voter, 'api')->putJson("/api/reviews/{$review->id}/helpful")
            ->assertOk()
            ->assertJsonPath('data.review_id', $review->id)
            ->assertJsonPath('data.helpful_count', 1)
            ->assertJsonPath('data.viewer_has_marked_helpful', true);

        $this->actingAs($voter, 'api')->putJson("/api/reviews/{$review->id}/helpful")
            ->assertOk()
            ->assertJsonPath('data.helpful_count', 1);

        $this->assertDatabaseCount('review_helpful_votes', 1);
        $this->assertDatabaseHas('review_helpful_votes', [
            'review_id' => $review->id,
            'user_id' => $voter->id,
        ]);

        $this->actingAs($voter, 'api')->deleteJson("/api/reviews/{$review->id}/helpful")
            ->assertOk()
            ->assertJsonPath('data.review_id', $review->id)
            ->assertJsonPath('data.helpful_count', 0)
            ->assertJsonPath('data.viewer_has_marked_helpful', false);

        $this->assertDatabaseMissing('review_helpful_votes', [
            'review_id' => $review->id,
            'user_id' => $voter->id,
        ]);

        $this->actingAs($voter, 'api')->deleteJson("/api/reviews/{$review->id}/helpful")
            ->assertOk()
            ->assertJsonPath('data.helpful_count', 0);

        $this->assertDatabaseCount('review_helpful_votes', 0);
    }

    public function test_authenticated_user_can_add_and_remove_an_itinerary_helpful_vote_idempotently(): void
    {
        [$review] = $this->itineraryReview();
        $voter = User::factory()->create();

        $this->actingAs($voter, 'api')->putJson("/api/reviews/{$review->id}/helpful")
            ->assertOk()
            ->assertJsonPath('data.review_id', $review->id)
            ->assertJsonPath('data.helpful_count', 1)
            ->assertJsonPath('data.viewer_has_marked_helpful', true);

        $this->actingAs($voter, 'api')->putJson("/api/reviews/{$review->id}/helpful")
            ->assertOk()
            ->assertJsonPath('data.helpful_count', 1)
            ->assertJsonPath('data.viewer_has_marked_helpful', true);

        $this->actingAs($voter, 'api')->deleteJson("/api/reviews/{$review->id}/helpful")
            ->assertOk()
            ->assertJsonPath('data.review_id', $review->id)
            ->assertJsonPath('data.helpful_count', 0)
            ->assertJsonPath('data.viewer_has_marked_helpful', false);

        $this->actingAs($voter, 'api')->deleteJson("/api/reviews/{$review->id}/helpful")
            ->assertOk()
            ->assertJsonPath('data.helpful_count', 0)
            ->assertJsonPath('data.viewer_has_marked_helpful', false);
    }

    public function test_database_rejects_duplicate_helpful_votes_for_the_same_review_and_user(): void
    {
        $this->assertTrue(Schema::hasTable('review_helpful_votes'));

        [$review] = $this->activityReview();
        $voter = User::factory()->create();
        $vote = [
            'review_id' => $review->id,
            'user_id' => $voter->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('review_helpful_votes')->insert($vote);

        $this->expectException(QueryException::class);

        DB::table('review_helpful_votes')->insert($vote);
    }

    public function test_review_author_cannot_mark_their_own_review_as_helpful(): void
    {
        [$review, $author] = $this->activityReview();

        $this->actingAs($author, 'api')
            ->putJson("/api/reviews/{$review->id}/helpful")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'You cannot mark your own review as helpful.');
    }

    public function test_add_vote_hides_pending_missing_and_private_itinerary_reviews(): void
    {
        $voter = User::factory()->create();
        [$pendingReview] = $this->activityReview(['status' => 'pending']);
        [$privateReview] = $this->itineraryReview([], ['private_itinerary' => true]);

        $this->actingAs($voter, 'api')
            ->putJson("/api/reviews/{$pendingReview->id}/helpful")
            ->assertNotFound();

        $this->actingAs($voter, 'api')
            ->putJson('/api/reviews/999999/helpful')
            ->assertNotFound();

        $this->actingAs($voter, 'api')
            ->putJson("/api/reviews/{$privateReview->id}/helpful")
            ->assertNotFound();
    }

    public function test_remove_vote_hides_pending_missing_and_private_itinerary_reviews(): void
    {
        $voter = User::factory()->create();
        [$pendingReview] = $this->activityReview(['status' => 'pending']);
        [$privateReview] = $this->itineraryReview([], ['private_itinerary' => true]);

        $this->actingAs($voter, 'api')
            ->deleteJson("/api/reviews/{$pendingReview->id}/helpful")
            ->assertNotFound();

        $this->actingAs($voter, 'api')
            ->deleteJson('/api/reviews/999999/helpful')
            ->assertNotFound();

        $this->actingAs($voter, 'api')
            ->deleteJson("/api/reviews/{$privateReview->id}/helpful")
            ->assertNotFound();
    }

    public function test_status_returns_only_voted_public_accessible_review_ids(): void
    {
        $voter = User::factory()->create();
        [$votedReview] = $this->activityReview();
        [$unvotedReview] = $this->itineraryReview();
        [$pendingReview] = $this->activityReview(['status' => 'pending']);
        [$privateReview] = $this->itineraryReview([], ['private_itinerary' => true]);

        foreach ([$votedReview, $pendingReview, $privateReview] as $review) {
            $this->insertVote($review, $voter);
        }

        $query = $this->reviewIdsQuery([
            $votedReview->id,
            $unvotedReview->id,
            $pendingReview->id,
            $privateReview->id,
        ]);

        $this->actingAs($voter, 'api')
            ->getJson("/api/reviews/helpful-status?{$query}")
            ->assertOk()
            ->assertJsonPath('data.review_ids', [$votedReview->id]);
    }

    public function test_status_rejects_more_than_fifty_review_ids(): void
    {
        $voter = User::factory()->create();
        $query = $this->reviewIdsQuery(range(1, 51));

        $this->actingAs($voter, 'api')
            ->getJson("/api/reviews/helpful-status?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review_ids');
    }

    public function test_status_rejects_non_integer_review_ids(): void
    {
        $voter = User::factory()->create();
        $query = $this->reviewIdsQuery([1, 'not-an-integer']);

        $this->actingAs($voter, 'api')
            ->getJson("/api/reviews/helpful-status?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review_ids.1');
    }

    public function test_status_rejects_an_empty_string_review_id_as_required(): void
    {
        $voter = User::factory()->create();
        $query = $this->reviewIdsQuery([1, '']);

        $response = $this->actingAs($voter, 'api')
            ->getJson("/api/reviews/helpful-status?{$query}")
            ->assertUnprocessable();

        $this->assertSame(
            'The review_ids.1 field is required.',
            $response->json('errors')['review_ids.1'][0]
        );
    }

    public function test_status_rejects_a_null_review_id_as_required(): void
    {
        $voter = User::factory()->create();

        $response = $this->actingAs($voter, 'api')
            ->call('GET', '/api/reviews/helpful-status', ['review_ids' => [1, null]], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ])
            ->assertUnprocessable();

        $this->assertSame(
            'The review_ids.1 field is required.',
            $response->json('errors')['review_ids.1'][0]
        );
    }

    public function test_status_rejects_an_empty_review_ids_array(): void
    {
        $voter = User::factory()->create();

        $this->actingAs($voter, 'api')
            ->call('GET', '/api/reviews/helpful-status', ['review_ids' => []], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review_ids');
    }

    public function test_status_requires_the_review_ids_array(): void
    {
        $voter = User::factory()->create();

        $this->actingAs($voter, 'api')
            ->getJson('/api/reviews/helpful-status')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review_ids');
    }

    public function test_status_rejects_duplicate_review_ids(): void
    {
        $voter = User::factory()->create();
        $query = $this->reviewIdsQuery([1, 1]);

        $this->actingAs($voter, 'api')
            ->getJson("/api/reviews/helpful-status?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review_ids.0');
    }

    public function test_status_rejects_zero_and_negative_review_ids(): void
    {
        $voter = User::factory()->create();
        $query = $this->reviewIdsQuery([0, -1]);

        $this->actingAs($voter, 'api')
            ->getJson("/api/reviews/helpful-status?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['review_ids.0', 'review_ids.1']);
    }

    public function test_mutation_response_marked_state_matches_persisted_vote_after_repeated_requests(): void
    {
        [$review] = $this->activityReview();
        $voter = User::factory()->create();

        foreach (['PUT', 'PUT', 'DELETE', 'DELETE'] as $method) {
            $response = $this->actingAs($voter, 'api')
                ->json($method, "/api/reviews/{$review->id}/helpful")
                ->assertOk();

            $voteExists = DB::table('review_helpful_votes')
                ->where('review_id', $review->id)
                ->where('user_id', $voter->id)
                ->exists();

            $this->assertSame(
                $voteExists,
                $response->json('data.viewer_has_marked_helpful')
            );
        }
    }

    public function test_deleting_a_user_cascades_their_helpful_votes(): void
    {
        [$review] = $this->activityReview();
        $voter = User::factory()->create();
        $this->insertVote($review, $voter);

        $voter->delete();

        $this->assertDatabaseMissing('review_helpful_votes', [
            'review_id' => $review->id,
            'user_id' => $voter->id,
        ]);
    }

    public function test_deleting_a_review_cascades_its_helpful_votes(): void
    {
        [$review] = $this->activityReview();
        $voter = User::factory()->create();
        $this->insertVote($review, $voter);

        $review->delete();

        $this->assertDatabaseMissing('review_helpful_votes', [
            'review_id' => $review->id,
            'user_id' => $voter->id,
        ]);
    }

    /**
     * @return array{Review, User}
     */
    private function activityReview(array $reviewAttributes = []): array
    {
        $activity = Activity::factory()->create();
        $author = User::factory()->create();
        $review = Review::factory()->create(array_merge([
            'user_id' => $author->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'item_name_snapshot' => $activity->name,
            'item_slug_snapshot' => $activity->slug,
            'status' => 'approved',
        ], $reviewAttributes));

        return [$review, $author];
    }

    /**
     * @return array{Review, User, Itinerary}
     */
    private function itineraryReview(array $reviewAttributes = [], array $itineraryAttributes = []): array
    {
        $itinerary = Itinerary::factory()->create(array_merge([
            'private_itinerary' => false,
        ], $itineraryAttributes));
        $creator = User::factory()->creator()->create();
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);
        $author = User::factory()->create();
        $review = Review::factory()->create(array_merge([
            'user_id' => $author->id,
            'item_type' => 'itinerary',
            'item_id' => $itinerary->id,
            'item_name_snapshot' => $itinerary->name,
            'item_slug_snapshot' => $itinerary->slug,
            'status' => 'approved',
        ], $reviewAttributes));

        return [$review, $author, $itinerary];
    }

    private function insertVote(Review $review, User $voter): void
    {
        DB::table('review_helpful_votes')->insert([
            'review_id' => $review->id,
            'user_id' => $voter->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function reviewIdsQuery(array $reviewIds): string
    {
        return collect($reviewIds)
            ->map(fn (int|string $reviewId): string => 'review_ids[]='.urlencode((string) $reviewId))
            ->implode('&');
    }
}
