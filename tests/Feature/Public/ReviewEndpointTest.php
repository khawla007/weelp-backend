<?php

namespace Tests\Feature\Public;

use App\Models\Activity;
use App\Models\ActivityLocation;
use App\Models\City;
use App\Models\Itinerary;
use App\Models\ItineraryLocation;
use App\Models\Package;
use App\Models\PackageLocation;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
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

    public function test_featured_reviews(): void
    {
        $response = $this->getJson('/api/reviews/featured-reviews');

        $response->assertOk();
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
}
