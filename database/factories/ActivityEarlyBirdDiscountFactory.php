<?php

namespace Database\Factories;

use App\Models\ActivityEarlyBirdDiscount;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityEarlyBirdDiscountFactory extends Factory
{
    protected $model = ActivityEarlyBirdDiscount::class;

    public function definition(): array
    {
        return [
            'enabled' => true,
            'days_before_start' => 30,
            'discount_amount' => 10,
            'discount_type' => 'percentage',
        ];
    }
}
