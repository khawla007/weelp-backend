<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        $activity = Activity::factory()->create();

        return [
            'user_id' => User::factory(),
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'item_name_snapshot' => $activity->name,
            'item_slug_snapshot' => $activity->slug,
            'rating' => fake()->numberBetween(1, 5),
            'review_text' => fake()->paragraph(),
            'status' => 'approved',
            'is_featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
