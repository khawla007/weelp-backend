<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\ActivityFaq;
use App\Models\Itinerary;
use App\Models\ItineraryFaq;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_activity_create_show_update_and_partial_delete_faqs(): void
    {
        $this->actingAs($this->adminUser(), 'api')
            ->postJson('/api/admin/activities', [
                'name' => 'FAQ Activity',
                'slug' => 'faq-activity',
                'description' => 'Activity with FAQs',
                'faqs' => [
                    ['question_number' => 1, 'question' => 'What is included?', 'answer' => 'Guide and briefing.'],
                    ['question_number' => 2, 'question' => 'Can beginners join?', 'answer' => 'Yes.'],
                ],
            ])
            ->assertCreated();

        $activity = Activity::where('slug', 'faq-activity')->firstOrFail();
        $keepFaq = $activity->faqs()->where('question_number', 1)->firstOrFail();
        $deleteFaq = $activity->faqs()->where('question_number', 2)->firstOrFail();

        $this->getJson("/api/admin/activities/{$activity->id}")
            ->assertOk()
            ->assertJsonPath('faqs.0.title', 'What is included?')
            ->assertJsonPath('faqs.0.content', 'Guide and briefing.');

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/activities/{$activity->id}", [
                'faqs' => [
                    ['id' => $keepFaq->id, 'question_number' => 1, 'question' => 'What should I bring?', 'answer' => 'Comfortable shoes.'],
                    ['question_number' => 3, 'question' => 'When should I arrive?', 'answer' => '15 minutes early.'],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('activity_faqs', [
            'id' => $keepFaq->id,
            'activity_id' => $activity->id,
            'question' => 'What should I bring?',
        ]);
        $this->assertDatabaseMissing('activity_faqs', ['id' => $deleteFaq->id]);

        $newFaq = $activity->faqs()->where('question_number', 3)->firstOrFail();

        $this->actingAs($this->adminUser(), 'api')
            ->deleteJson("/api/admin/activities/{$activity->id}/partial-delete", [
                'deleted_faq_ids' => [$newFaq->id],
            ])
            ->assertOk();

        $this->assertSame(1, ActivityFaq::where('activity_id', $activity->id)->count());
        $this->assertDatabaseMissing('activity_faqs', ['id' => $newFaq->id]);
    }

    public function test_activity_create_show_update_and_partial_delete_inclusions_exclusions(): void
    {
        $this->actingAs($this->adminUser(), 'api')
            ->postJson('/api/admin/activities', [
                'name' => 'Included Activity',
                'slug' => 'included-activity',
                'description' => 'Activity with inclusion rows',
                'inclusions_exclusions' => [
                    [
                        'type' => 'transfer',
                        'title' => 'Hotel pickup',
                        'description' => 'Shared pickup from selected hotels.',
                        'included' => true,
                    ],
                    [
                        'type' => 'other',
                        'title' => 'Tips',
                        'description' => 'Optional gratuities are not covered.',
                        'included' => false,
                    ],
                ],
            ])
            ->assertCreated();

        $activity = Activity::where('slug', 'included-activity')->firstOrFail();
        $keepRow = $activity->inclusionsExclusions()->where('title', 'Hotel pickup')->firstOrFail();
        $deleteRow = $activity->inclusionsExclusions()->where('title', 'Tips')->firstOrFail();

        $this->actingAs($this->adminUser(), 'api')
            ->getJson("/api/admin/activities/{$activity->id}")
            ->assertOk()
            ->assertJsonPath('inclusions_exclusions.0.title', 'Hotel pickup')
            ->assertJsonPath('inclusions_exclusions.0.included', true);

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/activities/{$activity->id}", [
                'inclusions_exclusions' => [
                    [
                        'id' => $keepRow->id,
                        'type' => 'transfer',
                        'title' => 'Private hotel pickup',
                        'description' => 'Pickup details are confirmed after booking.',
                        'included' => true,
                    ],
                    [
                        'type' => 'equipment',
                        'title' => 'Safety equipment',
                        'description' => 'Helmet and basic safety gear.',
                        'included' => true,
                    ],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('activity_inclusions_exclusions', [
            'id' => $keepRow->id,
            'activity_id' => $activity->id,
            'title' => 'Private hotel pickup',
            'included' => true,
        ]);
        $this->assertDatabaseMissing('activity_inclusions_exclusions', ['id' => $deleteRow->id]);

        $newRow = $activity->inclusionsExclusions()->where('title', 'Safety equipment')->firstOrFail();

        $this->actingAs($this->adminUser(), 'api')
            ->deleteJson("/api/admin/activities/{$activity->id}/partial-delete", [
                'deleted_inclusion_exclusion_ids' => [$newRow->id],
            ])
            ->assertOk();

        $this->assertSame(1, $activity->inclusionsExclusions()->count());
        $this->assertDatabaseMissing('activity_inclusions_exclusions', ['id' => $newRow->id]);
    }

    public function test_activity_show_returns_approved_reviews_for_schema_generation(): void
    {
        $activity = Activity::factory()->create();
        Review::factory()->create([
            'user_id' => User::factory()->create(['name' => 'Activity Reviewer'])->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'rating' => 4,
            'review_text' => 'Useful activity review.',
            'status' => 'approved',
        ]);
        Review::factory()->create([
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->getJson("/api/admin/activities/{$activity->id}")
            ->assertOk()
            ->assertJsonPath('review_summary.average_rating', 4)
            ->assertJsonPath('review_summary.total_reviews', 1)
            ->assertJsonPath('reviews.0.review_text', 'Useful activity review.')
            ->assertJsonPath('reviews.0.user_name', 'Activity Reviewer');
    }

    public function test_activity_update_with_stale_faq_id_creates_replacement_without_emptying_faqs(): void
    {
        $activity = Activity::factory()->create();
        $activity->faqs()->create([
            'question_number' => 1,
            'question' => 'Original activity question?',
            'answer' => 'Original activity answer.',
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/activities/{$activity->id}", [
                'faqs' => [
                    [
                        'id' => 999999,
                        'question_number' => 1,
                        'question' => 'Replacement activity question?',
                        'answer' => 'Replacement activity answer.',
                    ],
                ],
            ])
            ->assertOk();

        $this->assertSame(1, ActivityFaq::where('activity_id', $activity->id)->count());
        $this->assertDatabaseHas('activity_faqs', [
            'activity_id' => $activity->id,
            'question' => 'Replacement activity question?',
        ]);
    }

    public function test_itinerary_create_show_update_and_partial_delete_faqs(): void
    {
        $this->actingAs($this->adminUser(), 'api')
            ->postJson('/api/admin/itineraries', [
                'name' => 'FAQ Itinerary',
                'slug' => 'faq-itinerary',
                'description' => 'Itinerary with FAQs',
                'featured_itinerary' => false,
                'private_itinerary' => false,
                'faqs' => [
                    ['question_number' => 1, 'question' => 'What is included?', 'answer' => 'Daily plan.'],
                    ['question_number' => 2, 'question' => 'Can I customize it?', 'answer' => 'Yes.'],
                ],
            ])
            ->assertCreated();

        $itinerary = Itinerary::where('slug', 'faq-itinerary')->firstOrFail();
        $keepFaq = $itinerary->faqs()->where('question_number', 1)->firstOrFail();
        $deleteFaq = $itinerary->faqs()->where('question_number', 2)->firstOrFail();

        $this->getJson("/api/admin/itineraries/{$itinerary->id}")
            ->assertOk()
            ->assertJsonPath('faqs.0.title', 'What is included?')
            ->assertJsonPath('faqs.0.content', 'Daily plan.');

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/itineraries/{$itinerary->id}", [
                'faqs' => [
                    ['id' => $keepFaq->id, 'question_number' => 1, 'question' => 'What is covered?', 'answer' => 'Transfers shown in schedule.'],
                    ['question_number' => 3, 'question' => 'When should I book?', 'answer' => 'As early as possible.'],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('itinerary_faqs', [
            'id' => $keepFaq->id,
            'itinerary_id' => $itinerary->id,
            'question' => 'What is covered?',
        ]);
        $this->assertDatabaseMissing('itinerary_faqs', ['id' => $deleteFaq->id]);

        $newFaq = $itinerary->faqs()->where('question_number', 3)->firstOrFail();

        $this->actingAs($this->adminUser(), 'api')
            ->deleteJson("/api/admin/itineraries/{$itinerary->id}/partial-delete", [
                'deleted_faq_ids' => [$newFaq->id],
            ])
            ->assertOk();

        $this->assertSame(1, ItineraryFaq::where('itinerary_id', $itinerary->id)->count());
        $this->assertDatabaseMissing('itinerary_faqs', ['id' => $newFaq->id]);
    }

    public function test_itinerary_show_returns_approved_reviews_for_schema_generation(): void
    {
        $itinerary = Itinerary::factory()->create();
        Review::factory()->create([
            'user_id' => User::factory()->create(['name' => 'Itinerary Reviewer'])->id,
            'item_type' => 'itinerary',
            'item_id' => $itinerary->id,
            'rating' => 5,
            'review_text' => 'Useful itinerary review.',
            'status' => 'approved',
        ]);
        Review::factory()->create([
            'item_type' => 'itinerary',
            'item_id' => $itinerary->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->getJson("/api/admin/itineraries/{$itinerary->id}")
            ->assertOk()
            ->assertJsonPath('review_summary.average_rating', 5)
            ->assertJsonPath('review_summary.total_reviews', 1)
            ->assertJsonPath('reviews.0.review_text', 'Useful itinerary review.')
            ->assertJsonPath('reviews.0.user_name', 'Itinerary Reviewer');
    }

    public function test_itinerary_update_with_stale_faq_id_creates_replacement_without_emptying_faqs(): void
    {
        $itinerary = Itinerary::factory()->create();
        $itinerary->faqs()->create([
            'question_number' => 1,
            'question' => 'Original itinerary question?',
            'answer' => 'Original itinerary answer.',
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/itineraries/{$itinerary->id}", [
                'faqs' => [
                    [
                        'id' => 999999,
                        'question_number' => 1,
                        'question' => 'Replacement itinerary question?',
                        'answer' => 'Replacement itinerary answer.',
                    ],
                ],
            ])
            ->assertOk();

        $this->assertSame(1, ItineraryFaq::where('itinerary_id', $itinerary->id)->count());
        $this->assertDatabaseHas('itinerary_faqs', [
            'itinerary_id' => $itinerary->id,
            'question' => 'Replacement itinerary question?',
        ]);
    }

    public function test_public_activity_and_itinerary_show_return_faq_payloads(): void
    {
        $activity = Activity::factory()->create(['slug' => 'public-faq-activity']);
        $activity->faqs()->create([
            'question_number' => 1,
            'question' => 'Activity question?',
            'answer' => 'Activity answer.',
        ]);

        $itinerary = Itinerary::factory()->create(['slug' => 'public-faq-itinerary']);
        $itinerary->faqs()->create([
            'question_number' => 1,
            'question' => 'Itinerary question?',
            'answer' => 'Itinerary answer.',
        ]);

        $this->getJson('/api/activities/public-faq-activity')
            ->assertOk()
            ->assertJsonPath('data.faqs.0.title', 'Activity question?')
            ->assertJsonPath('data.faqs.0.content', 'Activity answer.');

        $this->getJson('/api/itineraries/public-faq-itinerary')
            ->assertOk()
            ->assertJsonPath('data.faqs.0.title', 'Itinerary question?')
            ->assertJsonPath('data.faqs.0.content', 'Itinerary answer.');
    }
}
