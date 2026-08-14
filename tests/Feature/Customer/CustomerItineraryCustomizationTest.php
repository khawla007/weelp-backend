<?php

namespace Tests\Feature\Customer;

use App\Models\Activity;
use App\Models\ActivityPricing;
use App\Models\City;
use App\Models\Itinerary;
use App\Models\ItineraryLocation;
use App\Models\ItineraryMeta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerItineraryCustomizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_save_route_creates_one_private_copy_from_catalog_prices(): void
    {
        $user = User::factory()->customer()->create();
        $parent = Itinerary::factory()->create();
        ItineraryMeta::create([
            'itinerary_id' => $parent->id,
            'creator_id' => User::factory()->creator()->create()->id,
            'status' => 'approved',
        ]);
        $activity = Activity::factory()->create();
        ActivityPricing::factory()->create([
            'activity_id' => $activity->id,
            'regular_price' => 75,
            'currency' => 'USD',
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/customer/itineraries/save', [
            'parent_itinerary_id' => $parent->id,
            'schedules' => [[
                'day' => 1,
                'title' => 'Edited day',
                'activities' => [[
                    'activity_id' => $activity->id,
                    'price' => 1,
                    'included' => true,
                ]],
                'transfers' => [],
            ]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.private_itinerary', true)
            ->assertJsonPath('data.schedule_total_price', 75);
        $this->assertDatabaseHas('itinerary_meta', [
            'itinerary_id' => $response->json('data.id'),
            'user_id' => $user->id,
            'parent_itinerary_id' => $parent->id,
        ]);
        $this->assertDatabaseHas('itinerary_activities', [
            'schedule_id' => $response->json('data.schedules.0.id'),
            'activity_id' => $activity->id,
            'price' => null,
        ]);
    }

    public function test_activity_search_returns_live_pricing_components(): void
    {
        $user = User::factory()->customer()->create();
        $city = City::factory()->create();
        $activity = Activity::factory()->create();
        ActivityPricing::factory()->create([
            'activity_id' => $activity->id,
            'regular_price' => 42.5,
            'currency' => 'USD',
        ]);
        ItineraryLocation::query();
        $activity->locations()->create(['city_id' => $city->id]);

        $this->actingAs($user, 'api')
            ->getJson('/api/user/itinerary-resources/activities?city_id='.$city->id)
            ->assertOk()
            ->assertJsonPath('data.0.pricing.unit_price', 42.5)
            ->assertJsonPath('data.0.pricing.price_type', 'per_person')
            ->assertJsonPath('data.0.pricing.currency', 'USD');
    }
}
