<?php

namespace Database\Factories;

use App\Models\TransferZone;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferZoneFactory extends Factory
{
    protected $model = TransferZone::class;

    public function definition(): array
    {
        return [
            'name'       => $this->faker->unique()->city(),
            'slug'       => $this->faker->unique()->slug(),
            'description' => $this->faker->optional()->paragraph(),
            'sort_order' => 0,
            'is_active'  => true,
        ];
    }
}
