<?php

namespace Database\Factories;

use App\Models\TransferRoute;
use App\Models\TransferZone;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferRouteFactory extends Factory
{
    protected $model = TransferRoute::class;

    public function definition(): array
    {
        return [
            'name'                => $this->faker->sentence(3),
            'slug'                => $this->faker->unique()->slug(),
            'origin_type'         => 'City',
            'origin_id'           => 1,
            'destination_type'    => 'City',
            'destination_id'      => 2,
            'from_zone_id'        => TransferZone::factory(),
            'to_zone_id'          => TransferZone::factory(),
            'distance_km'         => $this->faker->randomFloat(2, 5, 200),
            'duration_minutes'    => $this->faker->numberBetween(15, 480),
            'is_active'           => true,
            'is_popular'          => false,
        ];
    }
}
