<?php

namespace Database\Factories;

use App\Models\TransferZonePrice;
use App\Models\TransferZone;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferZonePriceFactory extends Factory
{
    protected $model = TransferZonePrice::class;

    public function definition(): array
    {
        return [
            'from_zone_id' => TransferZone::factory(),
            'to_zone_id'   => TransferZone::factory(),
            'base_price'   => $this->faker->randomFloat(2, 10, 500),
            'currency'     => 'USD',
        ];
    }
}
