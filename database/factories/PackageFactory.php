<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'item_type' => 'package',
            'featured_package' => false,
            'private_package' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured_package' => true,
        ]);
    }
}
