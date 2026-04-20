<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderPaymentFactory extends Factory
{
    protected $model = OrderPayment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_status' => 'pending',
            'stripe_session_id' => 'cs_test_' . fake()->uuid(),
            'payment_intent_id' => 'pi_test_' . fake()->uuid(),
            'payment_method' => 'card',
            'amount' => fake()->randomFloat(2, 20, 500),
            'total_amount' => fake()->randomFloat(2, 20, 500),
            'currency' => 'USD',
        ];
    }
}
