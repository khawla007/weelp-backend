<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityGroupDiscount;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityGroupDiscountFactory extends Factory
{
    protected $model = ActivityGroupDiscount::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'min_people' => fake()->numberBetween(2, 10),
            'discount_amount' => fake()->randomFloat(2, 5, 100),
            'discount_type' => fake()->randomElement(['fixed', 'percentage']),
        ];
    }
}
