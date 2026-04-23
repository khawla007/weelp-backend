<?php

namespace Database\Factories;

use App\Models\ActivityLastMinuteDiscount;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLastMinuteDiscountFactory extends Factory
{
    protected $model = ActivityLastMinuteDiscount::class;

    public function definition(): array
    {
        return [
            'enabled' => true,
            'days_before_start' => 7,
            'discount_amount' => 10,
            'discount_type' => 'percentage',
        ];
    }
}
