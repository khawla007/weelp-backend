<?php

namespace Tests\Feature\Customer;

use App\Models\Activity;
use App\Models\ActivityLocation;
use App\Models\ActivityMediaGallery;
use App\Models\City;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderEmergencyContact;
use App\Models\OrderPayment;
use App\Models\Package;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_own_orders(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $activity = Activity::factory()->create();

        Order::factory()->count(3)->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/customer/userorders');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_customer_orders_excludes_other_users_orders(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $otherUser = User::factory()->create(['role' => 'customer']);
        $activity = Activity::factory()->create();

        Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);
        Order::factory()->create([
            'user_id' => $otherUser->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/customer/userorders');

        $response->assertOk();
        $orders = $response->json('orders');
        $this->assertCount(1, $orders);
    }

    public function test_order_list_returns_401_without_auth(): void
    {
        $response = $this->getJson('/api/customer/userorders');

        $response->assertUnauthorized();
    }

    public function test_customer_can_view_own_order_detail_with_complete_contract(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
            'name' => 'Detail Customer',
            'email' => 'detail@example.com',
        ]);
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
            'item_snapshot_json' => json_encode([
                'name' => 'Snapshot Activity',
                'slug' => 'snapshot-activity',
                'item_type' => 'activity',
                'location' => [[
                    'location_type' => 'primary',
                    'city' => 'Snapshot City',
                    'city_slug' => 'snapshot-city',
                ]],
                'media' => [[
                    'id' => 91,
                    'name' => 'Cover',
                    'alt' => 'Activity cover',
                    'url' => 'media/activity-cover.jpg',
                ]],
            ], JSON_THROW_ON_ERROR),
            'travel_date' => '2026-09-15',
            'preferred_time' => '14:30',
            'number_of_adults' => 2,
            'number_of_children' => 1,
            'status' => 'confirmed',
            'special_requirements' => 'Window seats',
        ]);
        OrderPayment::query()->create([
            'order_id' => $order->id,
            'payment_status' => 'paid',
            'payment_method' => 'credit_card',
            'stripe_session_id' => 'cs_secret_customer_must_not_see',
            'payment_intent_id' => 'pi_secret_customer_must_not_see',
            'amount' => 240,
            'is_custom_amount' => true,
            'custom_amount' => 200,
            'total_amount' => 240,
            'currency' => 'USD',
        ]);
        OrderEmergencyContact::query()->create([
            'order_id' => $order->id,
            'contact_name' => 'Emergency Person',
            'contact_phone' => '+1555000111',
            'relationship' => 'Friend',
        ]);
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'rating' => 4,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/customer/userorders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.id', $order->id)
            ->assertJsonPath('order.created_at', $order->created_at->toJSON())
            ->assertJsonPath('order.status', 'confirmed')
            ->assertJsonPath('order.travel_date', '2026-09-15')
            ->assertJsonPath('order.preferred_time', '14:30')
            ->assertJsonPath('order.number_of_adults', 2)
            ->assertJsonPath('order.number_of_children', 1)
            ->assertJsonPath('order.special_requirements', 'Window seats')
            ->assertJsonPath('order.payment.payment_status', 'paid')
            ->assertJsonPath('order.payment.payment_method', 'credit_card')
            ->assertJsonPath('order.payment.amount', 240)
            ->assertJsonPath('order.payment.custom_amount', 200)
            ->assertJsonPath('order.payment.total_amount', 240)
            ->assertJsonPath('order.payment.currency', 'USD')
            ->assertJsonPath('order.payment.is_custom_amount', true)
            ->assertJsonPath('order.emergency_contact.contact_name', 'Emergency Person')
            ->assertJsonPath('order.user.name', 'Detail Customer')
            ->assertJsonPath('order.user.email', 'detail@example.com')
            ->assertJsonPath('order.item.name', 'Snapshot Activity')
            ->assertJsonPath('order.item.has_live_item', true)
            ->assertJsonPath('order.item.slug', 'snapshot-activity')
            ->assertJsonPath('order.item.item_type', 'activity')
            ->assertJsonPath('order.item.city', 'Snapshot City')
            ->assertJsonPath('order.item.city_slug', 'snapshot-city')
            ->assertJsonPath('order.item.media.0.url', 'media/activity-cover.jpg')
            ->assertJsonPath('order.review.id', $review->id)
            ->assertJsonMissingPath('order.payment.stripe_session_id')
            ->assertJsonMissingPath('order.payment.payment_intent_id');

        $listOrder = collect($this->actingAs($user, 'api')
            ->getJson('/api/customer/userorders')
            ->assertOk()
            ->json('orders'))->firstWhere('id', $order->id);

        $this->assertArrayNotHasKey('stripe_session_id', $listOrder['payment']);
        $this->assertArrayNotHasKey('payment_intent_id', $listOrder['payment']);
    }

    public function test_order_detail_falls_back_to_live_activity_for_incomplete_encoded_snapshot(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create([
            'name' => 'Live Activity',
            'slug' => 'live-activity',
            'item_type' => 'activity',
        ]);
        $city = City::factory()->create([
            'name' => 'Live City',
            'slug' => 'live-city',
        ]);
        ActivityLocation::query()->create([
            'activity_id' => $activity->id,
            'city_id' => $city->id,
            'location_type' => 'primary',
        ]);
        $media = Media::query()->create([
            'name' => 'Live cover',
            'alt_text' => 'Live cover alt',
            'url' => 'media/live-cover.jpg',
        ]);
        ActivityMediaGallery::query()->create([
            'activity_id' => $activity->id,
            'media_id' => $media->id,
            'is_featured' => true,
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
            'item_snapshot_json' => json_encode(['media' => []], JSON_THROW_ON_ERROR),
        ]);

        $this->actingAs($user, 'api')
            ->getJson("/api/customer/userorders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('order.item.name', 'Live Activity')
            ->assertJsonPath('order.item.slug', 'live-activity')
            ->assertJsonPath('order.item.item_type', 'activity')
            ->assertJsonPath('order.item.city', 'Live City')
            ->assertJsonPath('order.item.city_slug', 'live-city')
            ->assertJsonPath('order.item.media.0.id', $media->id)
            ->assertJsonPath('order.item.media.0.url', "/api/media/{$media->id}");
    }

    public function test_order_detail_uses_complete_snapshot_after_live_activity_is_deleted(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
            'item_snapshot_json' => json_encode([
                'name' => 'Archived Activity',
                'slug' => 'archived-activity',
                'item_type' => 'activity',
                'locations' => [[
                    'location_type' => 'primary',
                    'city' => 'Archived City',
                    'city_slug' => 'archived-city',
                ]],
                'media' => [[
                    'id' => 42,
                    'name' => 'Archived cover',
                    'alt_text' => 'Archived cover alt',
                    'url' => 'media/archived.jpg',
                ]],
            ], JSON_THROW_ON_ERROR),
        ]);
        $activity->delete();

        $this->actingAs($user, 'api')
            ->getJson("/api/customer/userorders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('order.item.name', 'Archived Activity')
            ->assertJsonPath('order.item.has_live_item', false)
            ->assertJsonPath('order.item.slug', 'archived-activity')
            ->assertJsonPath('order.item.item_type', 'activity')
            ->assertJsonPath('order.item.city', 'Archived City')
            ->assertJsonPath('order.item.city_slug', 'archived-city')
            ->assertJsonPath('order.item.locations.0.location_type', 'primary')
            ->assertJsonPath('order.item.media.0.url', 'media/archived.jpg');
    }

    public function test_customer_cannot_view_another_customers_order_detail(): void
    {
        $owner = $this->customer();
        $otherCustomer = $this->customer();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($otherCustomer, 'api')
            ->getJson("/api/customer/userorders/{$order->id}")
            ->assertNotFound();
    }

    public function test_order_detail_returns_401_without_auth(): void
    {
        $order = Order::factory()->create();

        $this->getJson("/api/customer/userorders/{$order->id}")
            ->assertUnauthorized();
    }

    public function test_order_linked_review_only_appears_on_its_booking(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create();
        $firstOrder = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);
        $secondOrder = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'order_id' => $firstOrder->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
        ]);

        $this->actingAs($user, 'api')
            ->getJson("/api/customer/userorders/{$firstOrder->id}")
            ->assertOk()
            ->assertJsonPath('order.review.id', $review->id);

        $this->actingAs($user, 'api')
            ->getJson("/api/customer/userorders/{$secondOrder->id}")
            ->assertOk()
            ->assertJsonPath('order.review', null);
    }

    public function test_legacy_review_is_returned_by_list_and_detail_without_leaking_order_linked_review(): void
    {
        $user = $this->customer();
        $legacyActivity = Activity::factory()->create();
        $legacyOrder = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $legacyActivity->id,
        ]);
        $legacyReview = Review::factory()->create([
            'user_id' => $user->id,
            'order_id' => null,
            'item_type' => Activity::class,
            'item_id' => $legacyActivity->id,
        ]);

        $linkedActivity = Activity::factory()->create();
        $reviewedOrder = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $linkedActivity->id,
        ]);
        $siblingOrder = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $linkedActivity->id,
        ]);
        $linkedReview = Review::factory()->create([
            'user_id' => $user->id,
            'order_id' => $reviewedOrder->id,
            'item_type' => 'activity',
            'item_id' => $linkedActivity->id,
        ]);

        $listResponse = $this->actingAs($user, 'api')
            ->getJson('/api/customer/userorders')
            ->assertOk();
        $orders = collect($listResponse->json('orders'))->keyBy('id');

        $this->assertSame($legacyReview->id, $orders->get($legacyOrder->id)['review']['id']);
        $this->assertSame($linkedReview->id, $orders->get($reviewedOrder->id)['review']['id']);
        $this->assertNull($orders->get($siblingOrder->id)['review']);

        $this->actingAs($user, 'api')
            ->getJson("/api/customer/userorders/{$legacyOrder->id}")
            ->assertOk()
            ->assertJsonPath('order.review.id', $legacyReview->id);

        $this->actingAs($user, 'api')
            ->getJson("/api/customer/userorders/{$siblingOrder->id}")
            ->assertOk()
            ->assertJsonPath('order.review', null);
    }

    public function test_archived_order_filters_malformed_snapshot_entries_without_500(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
            'item_snapshot_json' => json_encode([
                'name' => 'Malformed Archive',
                'slug' => 'malformed-archive',
                'item_type' => 'activity',
                'locations' => [null, 'invalid', [
                    'location_type' => 'primary',
                    'city' => 'Valid Snapshot City',
                    'city_slug' => 'valid-snapshot-city',
                ]],
                'media' => [null, 'invalid', [
                    'name' => 'Valid archived media',
                    'url' => 'media/valid-archived.jpg',
                ]],
            ], JSON_THROW_ON_ERROR),
        ]);
        $activity->delete();

        $this->actingAs($user, 'api')
            ->getJson("/api/customer/userorders/{$order->id}")
            ->assertOk()
            ->assertJsonCount(1, 'order.item.locations')
            ->assertJsonCount(1, 'order.item.media')
            ->assertJsonPath('order.item.city', 'Valid Snapshot City')
            ->assertJsonPath('order.item.media.0.url', 'media/valid-archived.jpg');
    }

    public function test_archived_order_with_invalid_snapshot_json_does_not_500(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
            'item_snapshot_json' => '{invalid-json',
        ]);
        $activity->delete();

        $this->actingAs($user, 'api')
            ->getJson("/api/customer/userorders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('order.item.name', null)
            ->assertJsonPath('order.item.locations', [])
            ->assertJsonPath('order.item.media', []);
    }

    public function test_live_order_prefers_primary_location_over_earlier_secondary_location(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create();
        $secondaryCity = City::factory()->create(['name' => 'Secondary City', 'slug' => 'secondary-city']);
        $primaryCity = City::factory()->create(['name' => 'Primary City', 'slug' => 'primary-city']);
        ActivityLocation::query()->create([
            'activity_id' => $activity->id,
            'city_id' => $secondaryCity->id,
            'location_type' => 'secondary',
        ]);
        ActivityLocation::query()->create([
            'activity_id' => $activity->id,
            'city_id' => $primaryCity->id,
            'location_type' => 'primary',
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
            'item_snapshot_json' => null,
        ]);

        $this->actingAs($user, 'api')
            ->getJson("/api/customer/userorders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('order.item.city', 'Primary City')
            ->assertJsonPath('order.item.city_slug', 'primary-city')
            ->assertJsonPath('order.item.locations.0.location_type', 'primary');
    }

    public function test_order_list_rejects_invalid_pagination_types_and_lower_bounds(): void
    {
        $this->actingAs($this->customer(), 'api')
            ->getJson('/api/customer/userorders?per_page=0&page=not-a-number')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page', 'page']);
    }

    public function test_legacy_reviews_do_not_collide_for_different_item_types_with_same_id(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create(['id' => 77]);
        $package = Package::factory()->create(['id' => 77]);
        $activityOrder = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => 77,
        ]);
        $packageOrder = Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'package',
            'orderable_id' => 77,
        ]);
        $activityReview = Review::factory()->create([
            'user_id' => $user->id,
            'order_id' => null,
            'item_type' => 'activity',
            'item_id' => $activity->id,
        ]);
        $packageReview = Review::factory()->create([
            'user_id' => $user->id,
            'order_id' => null,
            'item_type' => 'package',
            'item_id' => $package->id,
        ]);

        $orders = collect($this->actingAs($user, 'api')
            ->getJson('/api/customer/userorders')
            ->assertOk()
            ->json('orders'))->keyBy('id');

        $this->assertSame($activityReview->id, $orders->get($activityOrder->id)['review']['id']);
        $this->assertSame($packageReview->id, $orders->get($packageOrder->id)['review']['id']);
    }

    public function test_order_list_loads_reviews_in_one_bulk_query(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create();

        Order::factory()->count(3)->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);
        Review::factory()->create([
            'user_id' => $user->id,
            'order_id' => null,
            'item_type' => 'activity',
            'item_id' => $activity->id,
        ]);

        $reviewQueries = 0;
        DB::listen(function (QueryExecuted $query) use (&$reviewQueries): void {
            if (preg_match('/\bfrom\s+["`]?reviews["`]?\b/i', $query->sql) === 1) {
                $reviewQueries++;
            }
        });

        $this->actingAs($user, 'api')
            ->getJson('/api/customer/userorders')
            ->assertOk();

        $this->assertSame(1, $reviewQueries);
    }

    private function customer(): User
    {
        return User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
    }
}
