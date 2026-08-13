<?php

namespace Database\Factories;

use App\Models\CancellationRequest;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class CancellationRequestFactory extends Factory
{
    protected $model = CancellationRequest::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'customer_id' => fn (array $attributes): int => Order::query()
                ->findOrFail($attributes['order_id'])
                ->user_id,
            'status' => CancellationRequest::STATUS_PENDING,
            'reason' => fake()->sentence(12),
            'requested_at' => now(),
            'policy_version' => 'general-v1',
            'policy_snapshot' => config('cancellation'),
            'travel_starts_at' => now()->addMonth(),
            'seconds_remaining' => 30 * 24 * 60 * 60,
            'paid_amount' => '100.00',
            'currency' => 'USD',
            'suggested_deduction_percentage' => '10.00',
            'suggested_deduction_amount' => '10.00',
            'suggested_refund_amount' => '90.00',
        ];
    }
}
