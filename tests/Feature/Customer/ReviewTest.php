<?php

namespace Tests\Feature\Customer;

use App\Models\Activity;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_review(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/customer/review', [
                'item_type' => 'activity',
                'item_id' => $activity->id,
                'order_id' => $order->id,
                'rating' => 5,
                'review_text' => 'Amazing experience!',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'rating' => 5,
        ]);
    }

    public function test_customer_can_list_own_reviews(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $activity = Activity::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/customer/review');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_customer_can_show_review(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $activity = Activity::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/customer/review/{$review->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_customer_can_delete_review(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $activity = Activity::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/customer/review/{$review->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_review_create_fails_with_invalid_rating(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $activity = Activity::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/customer/review', [
                'item_type' => 'activity',
                'item_id' => $activity->id,
                'rating' => 10,
                'review_text' => 'Great!',
            ]);

        $response->assertUnprocessable();
    }

    public function test_review_stores_item_snapshot(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $activity = Activity::factory()->create(['name' => 'Snapshot Test Activity']);

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'item_name_snapshot' => $activity->name,
            'item_slug_snapshot' => $activity->slug,
        ]);

        $this->assertEquals('Snapshot Test Activity', $review->item_name_snapshot);
        $this->assertEquals($activity->slug, $review->item_slug_snapshot);
    }

    public function test_review_returns_401_without_auth(): void
    {
        $response = $this->getJson('/api/customer/review');

        $response->assertUnauthorized();
    }

    public function test_customer_cannot_create_review_for_another_customers_order(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $otherCustomer->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);

        $this->actingAs($customer, 'api')
            ->postJson('/api/customer/review', [
                'item_type' => 'activity',
                'item_id' => $activity->id,
                'order_id' => $order->id,
                'rating' => 5,
                'review_text' => 'This order is not mine.',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_cannot_create_review_when_owned_order_item_mismatches_submission(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $orderedActivity = Activity::factory()->create();
        $submittedActivity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'orderable_type' => 'activity',
            'orderable_id' => $orderedActivity->id,
        ]);

        $this->actingAs($customer, 'api')
            ->postJson('/api/customer/review', [
                'item_type' => 'activity',
                'item_id' => $submittedActivity->id,
                'order_id' => $order->id,
                'rating' => 5,
                'review_text' => 'The submitted item does not match.',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_cannot_create_review_with_zero_order_id(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $activity = Activity::factory()->create();

        $this->actingAs($customer, 'api')
            ->postJson('/api/customer/review', [
                'item_type' => 'activity',
                'item_id' => $activity->id,
                'order_id' => 0,
                'rating' => 5,
                'review_text' => 'Invalid booking reference.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_id');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_cannot_create_review_without_order_id(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $activity = Activity::factory()->create();

        $this->actingAs($customer, 'api')
            ->postJson('/api/customer/review', [
                'item_type' => 'activity',
                'item_id' => $activity->id,
                'rating' => 5,
                'review_text' => 'Missing booking reference.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_id');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_can_create_review_with_form_string_identifiers(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);

        $this->actingAs($customer, 'api')
            ->post('/api/customer/review', [
                'item_type' => 'activity',
                'item_id' => (string) $activity->id,
                'order_id' => (string) $order->id,
                'rating' => '5',
                'review_text' => 'Submitted like FormData.',
            ], [
                'Accept' => 'application/json',
                'CONTENT_TYPE' => 'multipart/form-data',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $customer->id,
            'order_id' => $order->id,
            'item_id' => $activity->id,
        ]);
    }

    public function test_archived_item_is_rejected_before_review_media_is_uploaded(): void
    {
        Storage::fake('minio');
        $customer = $this->customer();
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);
        $activity->delete();

        $this->actingAs($customer, 'api')
            ->post('/api/customer/review', [
                'item_type' => 'activity',
                'item_id' => $order->orderable_id,
                'order_id' => $order->id,
                'rating' => 5,
                'review_text' => 'Archived booking review.',
                'file' => [UploadedFile::fake()->image('review.jpg')],
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('item_id');

        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('minio')->allFiles());
    }

    public function test_customer_cannot_reassign_review_to_another_customers_order(): void
    {
        $customer = $this->customer();
        $otherCustomer = $this->customer();
        $activity = Activity::factory()->create();
        $ownedOrder = Order::factory()->create([
            'user_id' => $customer->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);
        $otherOrder = Order::factory()->create([
            'user_id' => $otherCustomer->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);
        $review = Review::factory()->create([
            'user_id' => $customer->id,
            'order_id' => $ownedOrder->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
        ]);

        $this->actingAs($customer, 'api')
            ->postJson("/api/customer/review/{$review->id}", [
                'order_id' => $otherOrder->id,
                'rating' => 4,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_id');

        $this->assertSame($ownedOrder->id, $review->fresh()->order_id);
    }

    public function test_customer_cannot_reassign_review_to_owned_order_for_different_item(): void
    {
        $customer = $this->customer();
        $reviewedActivity = Activity::factory()->create();
        $otherActivity = Activity::factory()->create();
        $reviewedOrder = Order::factory()->create([
            'user_id' => $customer->id,
            'orderable_type' => 'activity',
            'orderable_id' => $reviewedActivity->id,
        ]);
        $otherOrder = Order::factory()->create([
            'user_id' => $customer->id,
            'orderable_type' => 'activity',
            'orderable_id' => $otherActivity->id,
        ]);
        $review = Review::factory()->create([
            'user_id' => $customer->id,
            'order_id' => $reviewedOrder->id,
            'item_type' => 'activity',
            'item_id' => $reviewedActivity->id,
        ]);

        $this->actingAs($customer, 'api')
            ->postJson("/api/customer/review/{$review->id}", [
                'order_id' => $otherOrder->id,
                'rating' => 4,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_id');

        $this->assertSame($reviewedOrder->id, $review->fresh()->order_id);
    }

    public function test_review_update_validation_returns_422_without_trace(): void
    {
        $customer = $this->customer();
        $review = Review::factory()->create(['user_id' => $customer->id]);

        $this->actingAs($customer, 'api')
            ->postJson("/api/customer/review/{$review->id}", ['rating' => 10])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rating')
            ->assertJsonMissingPath('trace');
    }

    public function test_customer_cannot_create_duplicate_review_for_booking(): void
    {
        $customer = $this->customer();
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);
        $payload = [
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'order_id' => $order->id,
            'rating' => 5,
            'review_text' => 'One review per booking.',
        ];

        $this->actingAs($customer, 'api')->postJson('/api/customer/review', $payload)->assertOk();
        $this->actingAs($customer, 'api')
            ->postJson('/api/customer/review', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_id');

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_database_enforces_one_review_per_non_null_order(): void
    {
        $order = Order::factory()->create();
        Review::factory()->create(['order_id' => $order->id]);

        $this->expectException(QueryException::class);
        Review::factory()->create(['order_id' => $order->id]);
    }

    public function test_customer_can_link_legacy_morph_review_to_matching_booking(): void
    {
        $customer = $this->customer();
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);
        $review = Review::factory()->create([
            'user_id' => $customer->id,
            'order_id' => null,
            'item_type' => Activity::class,
            'item_id' => $activity->id,
        ]);

        $this->actingAs($customer, 'api')
            ->postJson("/api/customer/review/{$review->id}", [
                'order_id' => $order->id,
                'rating' => 4,
            ])
            ->assertOk()
            ->assertJsonPath('review.rating', 4);

        $this->assertSame($order->id, $review->fresh()->order_id);
    }

    public function test_unique_order_migration_preserves_newest_duplicate_as_booking_review(): void
    {
        $migration = require database_path('migrations/2026_08_07_000000_add_unique_order_id_to_reviews_table.php');
        $migration->down();

        $order = Order::factory()->create();
        $olderReview = Review::factory()->create(['order_id' => $order->id]);
        $newerReview = Review::factory()->create(['order_id' => $order->id]);

        $migration->up();

        $this->assertNull($olderReview->fresh()->order_id);
        $this->assertSame($order->id, $newerReview->fresh()->order_id);
        $this->assertSame(1, DB::table('reviews')->where('order_id', $order->id)->count());
        $this->assertTrue(Schema::hasIndex('reviews', 'reviews_order_id_unique'));
    }

    private function customer(): User
    {
        return User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
    }
}
