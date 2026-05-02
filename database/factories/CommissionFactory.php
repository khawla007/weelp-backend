<?php

namespace Database\Factories;

use App\Models\Commission;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommissionFactory extends Factory
{
    protected $model = Commission::class;

    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'order_id' => Order::factory(),
            'commission_rate' => 10.0,
            'commission_amount' => fake()->randomFloat(2, 5, 500),
            'status' => 'paid',
        ];
    }
}
