<?php

namespace Database\Factories;

use App\Models\VendorPricingTier;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorPricingTierFactory extends Factory
{
    protected $model = VendorPricingTier::class;

    public function definition(): array
    {
        return [
            'vendor_id'                => Vendor::factory(),
            'name'                     => $this->faker->word() . ' Tier',
            'description'              => $this->faker->optional()->paragraph(),
            'base_price'               => $this->faker->randomFloat(2, 20, 200),
            'price_per_km'             => $this->faker->randomFloat(2, 1, 10),
            'min_distance'             => $this->faker->numberBetween(1, 50),
            'waiting_charge'           => $this->faker->randomFloat(2, 5, 50),
            'night_charge_multiplier'  => $this->faker->randomFloat(2, 1.0, 2.0),
            'peak_hour_multiplier'     => $this->faker->randomFloat(2, 1.0, 1.5),
            'status'                   => 'active',
        ];
    }
}
