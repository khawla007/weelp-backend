<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->company(),
            'description' => $this->faker->optional()->paragraph(),
            'email'       => $this->faker->unique()->companyEmail(),
            'phone'       => $this->faker->phoneNumber(),
            'address'     => $this->faker->address(),
            'status'      => 'active',
        ];
    }
}
