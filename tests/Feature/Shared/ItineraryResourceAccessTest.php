<?php

namespace Tests\Feature\Shared;

use App\Models\Activity;
use App\Models\ActivityLocation;
use App\Models\City;
use App\Models\Place;
use App\Models\Transfer;
use App\Models\TransferVendorRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItineraryResourceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_use_authenticated_itinerary_resources(): void
    {
        $city = City::factory()->create();

        $this->getJson("/api/user/itinerary-resources/activities?city_id={$city->id}")
            ->assertUnauthorized();
        $this->getJson("/api/user/itinerary-resources/transfers?city_id={$city->id}")
            ->assertUnauthorized();
    }

    public function test_all_authenticated_roles_receive_consistent_itinerary_resource_shapes(): void
    {
        $city = City::factory()->create();
        $activity = Activity::factory()->create(['name' => 'City activity']);
        ActivityLocation::create([
            'activity_id' => $activity->id,
            'city_id' => $city->id,
            'location_type' => 'primary',
        ]);
        $pickup = Place::create([
            'name' => 'City airport',
            'code' => 'AIR',
            'slug' => "city-airport-{$city->id}",
            'type' => 'airport',
            'city_id' => $city->id,
        ]);
        $dropoff = Place::create([
            'name' => 'City centre',
            'code' => 'CTR',
            'slug' => "city-centre-{$city->id}",
            'type' => 'attraction',
            'city_id' => $city->id,
        ]);
        $transfer = Transfer::factory()->create(['name' => 'City transfer']);
        TransferVendorRoute::create([
            'transfer_id' => $transfer->id,
            'is_vendor' => false,
            'pickup_place_id' => $pickup->id,
            'dropoff_place_id' => $dropoff->id,
            'vehicle_type' => 'Sedan',
        ]);
        $users = [
            User::factory()->customer()->create(),
            User::factory()->creator()->customer()->create(),
            User::factory()->admin()->create(),
            User::factory()->superAdmin()->create(),
        ];

        foreach ($users as $user) {
            $this->actingAs($user, 'api')
                ->getJson("/api/user/itinerary-resources/activities?city_id={$city->id}")
                ->assertOk()
                ->assertJsonStructure(['success', 'data'])
                ->assertJsonPath('data.0.id', $activity->id)
                ->assertJsonPath('data.0.name', 'City activity');
            $this->actingAs($user, 'api')
                ->getJson("/api/user/itinerary-resources/transfers?city_id={$city->id}")
                ->assertOk()
                ->assertJsonStructure(['success', 'data'])
                ->assertJsonPath('data.0.id', $transfer->id)
                ->assertJsonPath('data.0.name', 'City transfer')
                ->assertJsonPath('data.0.vehicle_type', 'Sedan');
        }
    }

    public function test_itinerary_resources_validate_the_city(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'api')
            ->getJson('/api/user/itinerary-resources/activities?city_id=999999')
            ->assertUnprocessable();
        $this->actingAs($admin, 'api')
            ->getJson('/api/user/itinerary-resources/transfers?city_id=999999')
            ->assertUnprocessable();
    }
}
