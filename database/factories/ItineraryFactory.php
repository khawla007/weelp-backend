<?php

namespace Database\Factories;

use App\Models\Itinerary;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryFactory extends Factory
{
    protected $model = Itinerary::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'item_type' => 'itinerary',
            'featured_itinerary' => false,
            'private_itinerary' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured_itinerary' => true,
        ]);
    }
}
