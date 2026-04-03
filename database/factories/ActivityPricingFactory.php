<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityPricing;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityPricingFactory extends Factory
{
    protected $model = ActivityPricing::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'regular_price' => fake()->randomFloat(2, 10, 500),
            'currency' => 'USD',
        ];
    }
}
