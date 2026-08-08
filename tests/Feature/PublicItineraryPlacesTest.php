<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Place;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicItineraryPlacesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_places_for_the_requested_city(): void
    {
        $city = City::factory()->create();
        $otherCity = City::factory()->create();

        $pickup = Place::create([
            'name' => 'Airport Terminal',
            'code' => 'APT',
            'slug' => 'airport-terminal',
            'type' => 'airport',
            'city_id' => $city->id,
        ]);

        Place::create([
            'name' => 'Other City Hotel',
            'code' => 'HTL',
            'slug' => 'other-city-hotel',
            'type' => 'hotel',
            'city_id' => $otherCity->id,
        ]);

        $this->getJson("/api/places?city_id={$city->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pickup->id)
            ->assertJsonPath('data.0.name', $pickup->name)
            ->assertJsonPath('data.0.city_id', $city->id);
    }
}
