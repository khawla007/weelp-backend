<?php

namespace Database\Factories;

use App\Models\Itinerary;
use App\Models\ItinerarySchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryScheduleFactory extends Factory
{
    protected $model = ItinerarySchedule::class;

    public function definition(): array
    {
        return [
            'itinerary_id' => Itinerary::factory(),
            'day' => fake()->numberBetween(1, 14),
            'title' => fake()->optional()->sentence(4),
        ];
    }
}
