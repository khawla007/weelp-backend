<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Transfer;
use App\Models\User;
use Database\Seeders\TransferReviewSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransferReviewSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_fixtures_idempotently_without_overwriting_existing_reviews(): void
    {
        $customers = User::factory()->count(3)->customer()->create();
        $transfers = Transfer::factory()->count(2)->create();
        $genuineReview = Review::create([
            'user_id' => $customers->first()->id,
            'order_id' => null,
            'item_type' => 'transfer',
            'item_id' => $transfers->first()->id,
            'item_name_snapshot' => $transfers->first()->name,
            'item_slug_snapshot' => $transfers->first()->slug,
            'rating' => 2,
            'review_text' => 'This is a genuine existing transfer review.',
            'status' => 'pending',
            'is_featured' => false,
        ]);
        $unrelatedReviews = collect(['activity', 'package', 'itinerary'])
            ->map(fn (string $type, int $index) => $this->createUnrelatedReview(
                $customers->last(),
                $type,
                9000 + $index
            ));

        $this->seed(TransferReviewSeeder::class);

        $fixtures = Review::query()
            ->where('item_type', 'transfer')
            ->whereIn('review_text', TransferReviewSeeder::REVIEW_TEXTS)
            ->orderBy('id')
            ->get();
        $fixtureTimestamps = $fixtures->mapWithKeys(fn (Review $review) => [
            $review->id => [
                $review->created_at?->toISOString(),
                $review->updated_at?->toISOString(),
            ],
        ]);

        $this->assertCount(2, $fixtures);
        $this->assertTrue($fixtures->every(
            fn (Review $review) => $review->status === 'approved'
        ));
        $this->assertTrue($fixtures->contains(
            fn (Review $review) => $review->is_featured
        ));
        $this->assertSame(2, $fixtures->pluck('item_id')->unique()->count());
        $this->assertSame(
            ['2026-07-20 10:00:00', '2026-07-19 10:00:00'],
            $fixtures
                ->pluck('created_at')
                ->map(fn ($timestamp) => $timestamp?->format('Y-m-d H:i:s'))
                ->all()
        );
        $this->assertDatabaseHas('reviews', [
            'id' => $genuineReview->id,
            'review_text' => 'This is a genuine existing transfer review.',
            'rating' => 2,
            'status' => 'pending',
            'is_featured' => false,
        ]);
        $unrelatedReviews->each(
            fn (Review $review) => $this->assertDatabaseHas('reviews', ['id' => $review->id])
        );

        $this->seed(TransferReviewSeeder::class);

        $fixturesAfterRerun = Review::query()
            ->where('item_type', 'transfer')
            ->whereIn('review_text', TransferReviewSeeder::REVIEW_TEXTS)
            ->orderBy('id')
            ->get();

        $this->assertCount($fixtures->count(), $fixturesAfterRerun);
        $this->assertSame(
            $fixtureTimestamps->all(),
            $fixturesAfterRerun->mapWithKeys(fn (Review $review) => [
                $review->id => [
                    $review->created_at?->toISOString(),
                    $review->updated_at?->toISOString(),
                ],
            ])->all()
        );
        $this->assertDatabaseHas('reviews', [
            'id' => $genuineReview->id,
            'review_text' => 'This is a genuine existing transfer review.',
            'rating' => 2,
            'status' => 'pending',
            'is_featured' => false,
        ]);
        $unrelatedReviews->each(
            fn (Review $review) => $this->assertDatabaseHas('reviews', ['id' => $review->id])
        );
    }

    public function test_it_skips_without_transfers_and_preserves_existing_reviews(): void
    {
        $customer = User::factory()->customer()->create();
        $existingReview = $this->createUnrelatedReview($customer, 'activity', 9100);

        $this->seed(TransferReviewSeeder::class);

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', ['id' => $existingReview->id]);
    }

    public function test_it_skips_without_customers_and_preserves_existing_reviews(): void
    {
        $admin = User::factory()->admin()->create();
        Transfer::factory()->create();
        $existingReview = $this->createUnrelatedReview($admin, 'activity', 9200);

        $this->seed(TransferReviewSeeder::class);

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', ['id' => $existingReview->id]);
    }

    public function test_it_does_not_mutate_an_existing_review_that_matches_a_fixture_identity(): void
    {
        $customer = User::factory()->customer()->create();
        $transfer = Transfer::factory()->create();
        $review = Review::create([
            'user_id' => $customer->id,
            'order_id' => null,
            'item_type' => 'transfer',
            'item_id' => $transfer->id,
            'item_name_snapshot' => 'Original customer snapshot',
            'item_slug_snapshot' => 'original-customer-snapshot',
            'rating' => 2,
            'review_text' => TransferReviewSeeder::REVIEW_TEXTS[0],
            'status' => 'pending',
            'is_featured' => false,
        ]);
        $createdAt = Carbon::parse('2026-01-02 09:00:00');
        $updatedAt = Carbon::parse('2026-01-03 10:00:00');

        Review::query()->whereKey($review->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        $this->seed(TransferReviewSeeder::class);

        $review->refresh();

        $this->assertDatabaseCount('reviews', 1);
        $this->assertSame('Original customer snapshot', $review->item_name_snapshot);
        $this->assertSame('original-customer-snapshot', $review->item_slug_snapshot);
        $this->assertSame(2, $review->rating);
        $this->assertSame('pending', $review->status);
        $this->assertFalse($review->is_featured);
        $this->assertTrue($review->created_at?->equalTo($createdAt));
        $this->assertTrue($review->updated_at?->equalTo($updatedAt));
    }

    private function createUnrelatedReview(User $user, string $type, int $itemId): Review
    {
        return Review::create([
            'user_id' => $user->id,
            'order_id' => null,
            'item_type' => $type,
            'item_id' => $itemId,
            'item_name_snapshot' => "Existing {$type}",
            'item_slug_snapshot' => "existing-{$type}",
            'rating' => 4,
            'review_text' => "Existing {$type} review.",
            'status' => 'approved',
            'is_featured' => false,
        ]);
    }
}
