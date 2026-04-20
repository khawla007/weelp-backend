<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'orderable_type' => 'activity',
            'orderable_id' => Activity::factory(),
            'travel_date' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
            'preferred_time' => '10:00',
            'number_of_adults' => fake()->numberBetween(1, 4),
            'number_of_children' => fake()->numberBetween(0, 2),
            'status' => 'pending',
        ];
    }
}
