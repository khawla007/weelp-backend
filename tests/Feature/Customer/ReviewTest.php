<?php

namespace Tests\Feature\Customer;

use App\Models\Activity;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
