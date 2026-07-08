<?php

namespace Tests\Feature\Customer;

use App\Models\Activity;
use App\Models\ActivityLocation;
use App\Models\City;
use App\Models\Itinerary;
use App\Models\ItineraryLocation;
use App\Models\ItineraryMeta;
use App\Models\Package;
use App\Models\PackageLocation;
use App\Models\Place;
use App\Models\Transfer;
use App\Models\TransferVendorRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_wishlist_requires_authentication(): void
    {
        $response = $this->getJson('/api/customer/wishlist');

        $response->assertUnauthorized();
    }

    public function test_customer_can_add_and_list_wishlist_items(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create([
            'name' => 'Dubai Desert Safari',
            'slug' => 'dubai-desert-safari',
        ]);

        $createResponse = $this->actingAs($user, 'api')
            ->postJson('/api/customer/wishlist', [
                'item_type' => 'activity',
                'item_id' => $activity->id,
            ]);

        $createResponse->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'item_type' => 'activity',
                    'item_id' => $activity->id,
                    'title' => 'Dubai Desert Safari',
                    'slug' => 'dubai-desert-safari',
                ],
            ]);

        $listResponse = $this->actingAs($user, 'api')
            ->getJson('/api/customer/wishlist');

        $listResponse->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'item_type' => 'activity',
                        'item_id' => $activity->id,
                    ],
                ],
            ]);
    }

    public function test_wishlist_index_is_paginated_with_a_sane_default_limit(): void
    {
        $user = $this->customer();

        Activity::factory()->count(2)->create()->each(function (Activity $activity) use ($user): void {
            $this->insertWishlistItem($user, 'activity', $activity);
        });

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/customer/wishlist?per_page=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_duplicate_saves_update_existing_wishlist_item(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create();

        $payload = [
            'item_type' => 'activity',
            'item_id' => $activity->id,
        ];

        $this->actingAs($user, 'api')->postJson('/api/customer/wishlist', $payload)->assertOk();
        $this->actingAs($user, 'api')->postJson('/api/customer/wishlist', $payload)->assertOk();

        $this->assertDatabaseCount('wishlist_items', 1);
        $this->assertDatabaseHas('wishlist_items', [
            'user_id' => $user->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
        ]);
    }

    public function test_customer_can_save_wishlist_snapshot_array(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create();
        $snapshot = [
            'source' => 'mini_cart',
            'travelers' => [
                'adults' => 2,
                'children' => 1,
            ],
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/customer/wishlist', [
                'item_type' => 'activity',
                'item_id' => $activity->id,
                'snapshot' => $snapshot,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.snapshot', $snapshot);

        $storedSnapshot = DB::table('wishlist_items')
            ->where('user_id', $user->id)
            ->where('item_type', 'activity')
            ->where('item_id', $activity->id)
            ->value('snapshot');

        $this->assertSame($snapshot, json_decode($storedSnapshot, true));
    }

    public function test_customer_can_remove_own_wishlist_row(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create();
        $wishlistItemId = $this->insertWishlistItem($user, 'activity', $activity);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/customer/wishlist/{$wishlistItemId}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('wishlist_items', [
            'id' => $wishlistItemId,
        ]);
    }

    public function test_customer_cannot_remove_another_users_wishlist_row(): void
    {
        $owner = $this->customer();
        $otherUser = $this->customer();
        $activity = Activity::factory()->create();
        $wishlistItemId = $this->insertWishlistItem($owner, 'activity', $activity);

        $response = $this->actingAs($otherUser, 'api')
            ->deleteJson("/api/customer/wishlist/{$wishlistItemId}");

        $response->assertForbidden();

        $this->assertDatabaseHas('wishlist_items', [
            'id' => $wishlistItemId,
            'user_id' => $owner->id,
        ]);
    }

    public function test_customer_can_remove_wishlist_item_by_identity(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create();
        $this->insertWishlistItem($user, 'activity', $activity);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/customer/wishlist/item/activity/{$activity->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('wishlist_items', [
            'user_id' => $user->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
        ]);
    }

    public function test_customer_can_remove_by_identity_after_catalog_item_becomes_private(): void
    {
        $user = $this->customer();
        $package = Package::factory()->create([
            'private_package' => false,
        ]);
        $this->insertWishlistItem($user, 'package', $package);

        $package->update(['private_package' => true]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/customer/wishlist/item/package/{$package->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('wishlist_items', [
            'user_id' => $user->id,
            'item_type' => 'package',
            'item_id' => $package->id,
        ]);
    }

    public function test_remove_by_identity_rejects_non_numeric_item_ids_without_type_error(): void
    {
        $user = $this->customer();

        $response = $this->actingAs($user, 'api')
            ->deleteJson('/api/customer/wishlist/item/activity/not-a-number');

        $this->assertNotSame(500, $response->status());
        $response->assertNotFound();
    }

    public function test_client_price_must_fit_wishlist_decimal_column(): void
    {
        $user = $this->customer();
        $activity = Activity::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/customer/wishlist', [
                'item_type' => 'activity',
                'item_id' => $activity->id,
                'price' => 100000000,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    public function test_private_itineraries_cannot_be_added_to_wishlist(): void
    {
        $user = $this->customer();
        $itinerary = Itinerary::factory()->create([
            'private_itinerary' => true,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/customer/wishlist', [
                'item_type' => 'itinerary',
                'item_id' => $itinerary->id,
            ]);

        $response->assertUnprocessable()
            ->assertJson(['success' => false]);
    }

    public function test_unapproved_itineraries_cannot_be_added_to_wishlist(): void
    {
        $user = $this->customer();
        $itinerary = Itinerary::factory()->create();
        ItineraryMeta::query()->create([
            'itinerary_id' => $itinerary->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/customer/wishlist', [
                'item_type' => 'itinerary',
                'item_id' => $itinerary->id,
            ]);

        $response->assertUnprocessable()
            ->assertJson(['success' => false]);
    }

    public function test_draft_itineraries_cannot_be_added_to_wishlist(): void
    {
        $user = $this->customer();
        $itinerary = Itinerary::factory()->create();
        ItineraryMeta::query()->create([
            'itinerary_id' => $itinerary->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/customer/wishlist', [
                'item_type' => 'itinerary',
                'item_id' => $itinerary->id,
            ]);

        $response->assertUnprocessable()
            ->assertJson(['success' => false]);
    }

    public function test_private_packages_cannot_be_added_to_wishlist(): void
    {
        $user = $this->customer();
        $package = Package::factory()->create([
            'private_package' => true,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/customer/wishlist', [
                'item_type' => 'package',
                'item_id' => $package->id,
            ]);

        $response->assertUnprocessable()
            ->assertJson(['success' => false]);
    }

    public function test_activity_wishlist_item_is_enriched_with_route_fields(): void
    {
        $user = $this->customer();
        $city = City::factory()->create(['name' => 'Dubai', 'slug' => 'dubai']);
        $activity = Activity::factory()->create([
            'name' => 'Desert Safari',
            'slug' => 'desert-safari',
        ]);
        ActivityLocation::query()->create([
            'activity_id' => $activity->id,
            'city_id' => $city->id,
            'location_type' => 'primary',
        ]);

        $this->assertEnrichedItem($user, 'activity', $activity, [
            'title' => 'Desert Safari',
            'slug' => 'desert-safari',
            'city_slug' => 'dubai',
            'city_name' => 'Dubai',
        ]);
    }

    public function test_itinerary_wishlist_item_is_enriched_with_route_fields(): void
    {
        $user = $this->customer();
        $city = City::factory()->create(['name' => 'Paris', 'slug' => 'paris']);
        $itinerary = Itinerary::factory()->create([
            'name' => 'Paris Weekend',
            'slug' => 'paris-weekend',
        ]);
        ItineraryLocation::query()->create([
            'itinerary_id' => $itinerary->id,
            'city_id' => $city->id,
        ]);

        $this->assertEnrichedItem($user, 'itinerary', $itinerary, [
            'title' => 'Paris Weekend',
            'slug' => 'paris-weekend',
            'city_slug' => 'paris',
            'city_name' => 'Paris',
        ]);
    }

    public function test_package_wishlist_item_is_enriched_with_route_fields(): void
    {
        $user = $this->customer();
        $city = City::factory()->create(['name' => 'Marseille', 'slug' => 'marseille']);
        $package = Package::factory()->create([
            'name' => 'Marseille Food Tour',
            'slug' => 'marseille-food-tour',
        ]);
        PackageLocation::query()->create([
            'package_id' => $package->id,
            'city_id' => $city->id,
        ]);

        $this->assertEnrichedItem($user, 'package', $package, [
            'title' => 'Marseille Food Tour',
            'slug' => 'marseille-food-tour',
            'city_slug' => 'marseille',
            'city_name' => 'Marseille',
        ]);
    }

    public function test_transfer_wishlist_item_prefers_pickup_city_for_route_fields(): void
    {
        $user = $this->customer();
        $pickupCity = City::factory()->create(['name' => 'Abu Dhabi', 'slug' => 'abu-dhabi']);
        $dropoffCity = City::factory()->create(['name' => 'Dubai', 'slug' => 'dubai']);
        $pickupPlace = $this->place($pickupCity, 'Abu Dhabi Airport');
        $dropoffPlace = $this->place($dropoffCity, 'Dubai Marina');
        $transfer = Transfer::factory()->create([
            'name' => 'Airport Transfer',
            'slug' => 'airport-transfer',
        ]);
        TransferVendorRoute::query()->create([
            'transfer_id' => $transfer->id,
            'is_vendor' => false,
            'pickup_place_id' => $pickupPlace->id,
            'dropoff_place_id' => $dropoffPlace->id,
        ]);

        $this->assertEnrichedItem($user, 'transfer', $transfer, [
            'title' => 'Airport Transfer',
            'slug' => 'airport-transfer',
            'city_slug' => 'abu-dhabi',
            'city_name' => 'Abu Dhabi',
        ]);
    }

    public function test_transfer_wishlist_item_uses_dropoff_city_when_pickup_city_is_missing(): void
    {
        $user = $this->customer();
        $dropoffCity = City::factory()->create(['name' => 'Dubai', 'slug' => 'dubai']);
        $dropoffPlace = $this->place($dropoffCity, 'Dubai Marina');
        $transfer = Transfer::factory()->create([
            'name' => 'Marina Transfer',
            'slug' => 'marina-transfer',
        ]);
        TransferVendorRoute::query()->create([
            'transfer_id' => $transfer->id,
            'is_vendor' => false,
            'dropoff_place_id' => $dropoffPlace->id,
        ]);

        $this->assertEnrichedItem($user, 'transfer', $transfer, [
            'title' => 'Marina Transfer',
            'slug' => 'marina-transfer',
            'city_slug' => 'dubai',
            'city_name' => 'Dubai',
        ]);
    }

    private function customer(): User
    {
        return User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
    }

    private function place(City $city, string $name): Place
    {
        return Place::query()->create([
            'name' => $name,
            'code' => str($name)->slug('-')->upper()->limit(20, ''),
            'slug' => str($name)->slug(),
            'type' => 'place',
            'city_id' => $city->id,
        ]);
    }

    private function insertWishlistItem(User $user, string $itemType, object $item): int
    {
        return (int) DB::table('wishlist_items')->insertGetId([
            'user_id' => $user->id,
            'item_type' => $itemType,
            'item_id' => $item->id,
            'title' => $item->name,
            'slug' => $item->slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assertEnrichedItem(User $user, string $itemType, object $item, array $expected): void
    {
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/customer/wishlist', [
                'item_type' => $itemType,
                'item_id' => $item->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => array_merge([
                    'item_type' => $itemType,
                    'item_id' => $item->id,
                ], $expected),
            ]);
    }
}
