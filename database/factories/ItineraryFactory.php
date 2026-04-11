<?php

namespace Database\Factories;

use App\Models\Itinerary;
use App\Models\ItineraryMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryFactory extends Factory
{
    protected $model = Itinerary::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'slug' => fake()->slug() . '-' . time(),
            'description' => fake()->paragraphs(2, true),
            'featured_itinerary' => false,
            'private_itinerary' => false,
        ];
    }

    public function original(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured_itinerary' => true,
        ]);
    }

    public function approved(): static
    {
        return $this->afterCreating(function (Itinerary $itinerary) {
            ItineraryMeta::create([
                'itinerary_id' => $itinerary->id,
                'status' => 'approved',
                'views_count' => 0,
                'likes_count' => 0,
            ]);
            $itinerary->load('meta');
        });
    }

    public function pending(): static
    {
        return $this->afterCreating(function (Itinerary $itinerary) {
            ItineraryMeta::create([
                'itinerary_id' => $itinerary->id,
                'status' => 'pending',
                'views_count' => 0,
                'likes_count' => 0,
            ]);
            $itinerary->load('meta');
        });
    }
}
