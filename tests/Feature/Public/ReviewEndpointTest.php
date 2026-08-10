<?php

namespace Tests\Feature\Public;

use App\Models\Activity;
use App\Models\ActivityLocation;
use App\Models\City;
use App\Models\Itinerary;
use App\Models\ItineraryLocation;
use App\Models\ItineraryMeta;
use App\Models\Media;
use App\Models\Package;
use App\Models\PackageLocation;
use App\Models\Review;
use App\Models\ReviewMediaGallery;
use App\Models\Transfer;
use App\Models\User;
use App\Services\CreatorItineraryLifecycleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_list_reviews_skips_orphaned_review_items(): void
    {
        $user = User::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'activity',
            'item_id' => 999999,
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/reviews');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    public function test_list_reviews_returns_empty_for_no_reviews(): void
    {
        $response = $this->getJson('/api/reviews');

        $response->assertOk();
    }

    public function test_review_pagination_excludes_restored_draft_itinerary_reviews_before_counting(): void
    {
        $user = User::factory()->create();
        $creator = User::factory()->creator()->create();
        $activity = Activity::factory()->create();
        Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'status' => 'approved',
        ]);
        $itinerary = Itinerary::factory()->create();
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);
        $hiddenReview = Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'itinerary',
            'item_id' => $itinerary->id,
            'status' => 'approved',
        ]);
        $lifecycle = app(CreatorItineraryLifecycleService::class);
        $lifecycle->trash($itinerary->id);
        $lifecycle->restoreToDraft($itinerary->id, $creator->id);

        $this->getJson('/api/reviews?per_page=1')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonMissing(['id' => $hiddenReview->id]);
    }

    public function test_featured_reviews(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        $transfer = Transfer::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'status' => 'approved',
        ]);
        Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'transfer',
            'item_id' => $transfer->id,
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/reviews/featured-reviews');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $this->assertEqualsCanonicalizing(
            ['activity', 'transfer'],
            collect($response->json('data'))->pluck('item.type')->all()
        );
    }

    public function test_featured_reviews_can_be_filtered_to_transfers(): void
    {
        $user = User::factory()->create();
        $transfer = Transfer::factory()->create([
            'name' => 'DXB to Dubai Marina',
            'slug' => 'dxb-to-dubai-marina',
        ]);
        $activity = Activity::factory()->create();

        $transferReview = Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'transfer',
            'item_id' => $transfer->id,
            'item_name_snapshot' => $transfer->name,
            'item_slug_snapshot' => $transfer->slug,
            'status' => 'approved',
            'is_featured' => true,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'item_name_snapshot' => $activity->name,
            'item_slug_snapshot' => $activity->slug,
            'status' => 'approved',
            'is_featured' => true,
        ]);

        $pendingTransferReview = Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'transfer',
            'item_id' => $transfer->id,
            'item_name_snapshot' => $transfer->name,
            'item_slug_snapshot' => $transfer->slug,
            'status' => 'pending',
            'is_featured' => true,
        ]);

        $response = $this->getJson('/api/reviews/featured-reviews?item_type=transfer');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total_reviews', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $transferReview->id)
            ->assertJsonPath('data.0.item.type', 'transfer')
            ->assertJsonPath('data.0.item.slug', 'dxb-to-dubai-marina');

        $this->assertNotContains(
            $pendingTransferReview->id,
            collect($response->json('data'))->pluck('id')->all()
        );
    }

    public function test_featured_reviews_reject_an_invalid_item_type(): void
    {
        $this->getJson('/api/reviews/featured-reviews?item_type=hotel')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('item_type');
    }

    public function test_itinerary_reviews_include_media_gallery(): void
    {
        $itinerary = Itinerary::factory()->create(['slug' => 'adventure-tour-in-dubai']);
        $user = User::factory()->create(['name' => 'Aisha Khan']);
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'itinerary',
            'item_id' => $itinerary->id,
            'rating' => 5,
            'review_text' => 'The schedule was clear and every stop felt worth it.',
            'status' => 'approved',
            'is_featured' => true,
        ]);
        $media = Media::create([
            'name' => 'Dubai itinerary review',
            'alt_text' => 'Dubai itinerary guest photo',
            'url' => 'reviews/dubai-itinerary.jpg',
        ]);

        ReviewMediaGallery::create([
            'review_id' => $review->id,
            'media_id' => $media->id,
            'sort_order' => 0,
        ]);
        $this->addHelpfulVotes($review, 2);

        $response = $this->getJson('/api/reviews/itinerary/adventure-tour-in-dubai?photos_only=true');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total_reviews', 1)
            ->assertJsonPath('summary.total_photos', 1)
            ->assertJsonPath('data.0.id', $review->id)
            ->assertJsonPath('data.0.helpful_count', 2)
            ->assertJsonPath('data.0.user.name', 'Aisha Khan')
            ->assertJsonPath('data.0.media_gallery.0.id', $media->id)
            ->assertJsonPath('data.0.media_gallery.0.url', "/api/media/{$media->id}");
    }

    public function test_itinerary_featured_reviews_include_media_gallery(): void
    {
        $itinerary = Itinerary::factory()->create(['slug' => 'adventure-tour-in-dubai']);
        $user = User::factory()->create();
        $featuredReview = Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'itinerary',
            'item_id' => $itinerary->id,
            'status' => 'approved',
            'is_featured' => true,
        ]);
        $nonFeaturedReview = Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'itinerary',
            'item_id' => $itinerary->id,
            'status' => 'approved',
            'is_featured' => false,
        ]);
        $media = Media::create([
            'name' => 'Featured itinerary review',
            'alt_text' => 'Featured itinerary guest photo',
            'url' => 'reviews/featured-itinerary.jpg',
        ]);

        ReviewMediaGallery::create([
            'review_id' => $featuredReview->id,
            'media_id' => $media->id,
            'sort_order' => 0,
        ]);
        $this->addHelpfulVotes($featuredReview, 2);

        $response = $this->getJson('/api/reviews/itinerary/adventure-tour-in-dubai/featured');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $featuredReview->id)
            ->assertJsonPath('data.0.helpful_count', 2)
            ->assertJsonPath('data.0.media_gallery.0.url', "/api/media/{$media->id}");

        $this->assertNotSame($nonFeaturedReview->id, $response->json('data.0.id'));
    }

    public function test_activity_reviews_include_helpful_count(): void
    {
        $activity = Activity::factory()->create(['slug' => 'helpful-activity']);
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'status' => 'approved',
        ]);
        $this->addHelpfulVotes($review, 2);

        $this->getJson('/api/reviews/activity/helpful-activity')
            ->assertOk()
            ->assertJsonPath('data.0.id', $review->id)
            ->assertJsonPath('data.0.helpful_count', 2);
    }

    public function test_activity_featured_reviews_include_helpful_count(): void
    {
        $activity = Activity::factory()->create(['slug' => 'helpful-featured-activity']);
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'status' => 'approved',
            'is_featured' => true,
        ]);
        $this->addHelpfulVotes($review, 2);

        $this->getJson('/api/reviews/activity/helpful-featured-activity/featured')
            ->assertOk()
            ->assertJsonPath('data.0.id', $review->id)
            ->assertJsonPath('data.0.helpful_count', 2);
    }

    public function test_activity_reviews_return_integer_zero_for_a_review_without_helpful_votes(): void
    {
        $activity = Activity::factory()->create(['slug' => 'zero-helpful-activity']);
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/reviews/activity/zero-helpful-activity')
            ->assertOk()
            ->assertJsonPath('data.0.id', $review->id)
            ->assertJsonPath('data.0.helpful_count', 0);

        $this->assertIsInt($response->json('data.0.helpful_count'));
    }

    public function test_city_review_showcase_returns_featured_then_top_rated_reviews_with_limit(): void
    {
        $now = Carbon::parse('2026-07-07 10:00:00');

        $city = City::factory()->create(['slug' => 'dubai']);
        $otherCity = City::factory()->create(['slug' => 'paris']);
        $user = User::factory()->create();

        $featuredLowRating = $this->createCityActivityReview($city, $user, [
            'rating' => 3,
            'review_text' => 'Featured review stays first.',
            'is_featured' => true,
            'created_at' => $now->copy()->subDays(5),
        ]);

        $featuredHighRating = $this->createCityActivityReview($city, $user, [
            'rating' => 5,
            'review_text' => 'Featured high rating review.',
            'is_featured' => true,
            'created_at' => $now->copy()->subDays(4),
        ]);

        $packageReview = $this->createCityPackageReview($city, $user, [
            'rating' => 5,
            'review_text' => 'Automated package review.',
            'is_featured' => false,
            'created_at' => $now->copy()->subDay(),
        ]);

        $itineraryReview = $this->createCityItineraryReview($city, $user, [
            'rating' => 5,
            'review_text' => 'Automated itinerary review.',
            'is_featured' => false,
            'created_at' => $now->copy()->subDays(2),
        ]);

        $automatedReviews = collect(range(1, 8))->map(function (int $index) use ($city, $user, $now) {
            return $this->createCityActivityReview($city, $user, [
                'rating' => $index <= 6 ? 5 : 4,
                'review_text' => "Automated review {$index}.",
                'is_featured' => false,
                'created_at' => $now->copy()->subDays($index + 2),
            ]);
        });

        $this->createCityActivityReview($otherCity, $user, [
            'rating' => 5,
            'review_text' => 'Other city review should not appear.',
            'is_featured' => true,
            'created_at' => $now,
        ]);

        $response = $this->getJson('/api/reviews/featured-reviews?city=dubai');

        $expectedIds = collect([$featuredHighRating, $featuredLowRating, $packageReview, $itineraryReview])
            ->merge($automatedReviews->take(6))
            ->pluck('id')
            ->all();

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('summary.total_reviews', 12)
            ->assertJsonPath('data.0.id', $expectedIds[0])
            ->assertJsonPath('data.1.id', $expectedIds[1])
            ->assertJsonPath('data.9.id', $expectedIds[9]);

        $this->assertSame($expectedIds, collect($response->json('data'))->pluck('id')->all());
        $this->assertNotContains($automatedReviews->last()->id, collect($response->json('data'))->pluck('id')->all());
    }

    private function createCityActivityReview(City $city, User $user, array $reviewAttributes = []): Review
    {
        $activity = Activity::factory()->create();

        ActivityLocation::create([
            'activity_id' => $activity->id,
            'city_id' => $city->id,
            'location_type' => 'primary',
        ]);

        return Review::factory()->create(array_merge([
            'user_id' => $user->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'item_name_snapshot' => $activity->name,
            'item_slug_snapshot' => $activity->slug,
            'status' => 'approved',
        ], $reviewAttributes));
    }

    private function createCityPackageReview(City $city, User $user, array $reviewAttributes = []): Review
    {
        $package = Package::factory()->create();

        PackageLocation::create([
            'package_id' => $package->id,
            'city_id' => $city->id,
        ]);

        return Review::factory()->create(array_merge([
            'user_id' => $user->id,
            'item_type' => 'package',
            'item_id' => $package->id,
            'item_name_snapshot' => $package->name,
            'item_slug_snapshot' => $package->slug,
            'status' => 'approved',
        ], $reviewAttributes));
    }

    private function createCityItineraryReview(City $city, User $user, array $reviewAttributes = []): Review
    {
        $itinerary = Itinerary::factory()->create();

        ItineraryLocation::create([
            'itinerary_id' => $itinerary->id,
            'city_id' => $city->id,
        ]);

        return Review::factory()->create(array_merge([
            'user_id' => $user->id,
            'item_type' => 'itinerary',
            'item_id' => $itinerary->id,
            'item_name_snapshot' => $itinerary->name,
            'item_slug_snapshot' => $itinerary->slug,
            'status' => 'approved',
        ], $reviewAttributes));
    }

    private function addHelpfulVotes(Review $review, int $count): void
    {
        User::factory()->count($count)->create()->each(function (User $voter) use ($review): void {
            DB::table('review_helpful_votes')->insert([
                'review_id' => $review->id,
                'user_id' => $voter->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
