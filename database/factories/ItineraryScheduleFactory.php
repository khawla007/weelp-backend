<?php

namespace Database\Factories;

use App\Models\ItinerarySchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryScheduleFactory extends Factory
{
    protected $model = ItinerarySchedule::class;

    public function definition(): array
    {
        return [
            'itinerary_id' => null, // Must be set when creating
            'day' => fake()->numberBetween(1, 14),
            'title' => fake()->optional()->sentence(4),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
