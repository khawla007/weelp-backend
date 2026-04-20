<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

class StateFactory extends Factory
{
    protected $model = State::class;

    public function definition(): array
    {
        return [
            'name' => fake()->state(),
            'slug' => fake()->unique()->slug(),
            'code' => fake()->unique()->lexify('??'),
            'country_id' => Country::factory(),
        ];
    }
}
