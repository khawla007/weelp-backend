<?php

namespace Database\Factories;

use App\Models\TransferPricingAvailability;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferPricingAvailabilityFactory extends Factory
{
    protected $model = TransferPricingAvailability::class;

    public function definition(): array
    {
        return [
            'transfer_id'            => Transfer::factory(),
            'is_vendor'              => true,
            'pricing_tier_id'        => null,
            'availability_id'        => null,
            'transfer_price'         => null,
            'currency'               => null,
            'price_type'             => null,
            'extra_luggage_charge'   => null,
            'waiting_charge'         => null,
        ];
    }
}
