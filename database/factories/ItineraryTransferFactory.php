<?php

namespace Database\Factories;

use App\Models\ItineraryTransfer;
use App\Models\ItinerarySchedule;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryTransferFactory extends Factory
{
    protected $model = ItineraryTransfer::class;

    public function definition(): array
    {
        return [
            'schedule_id'         => ItinerarySchedule::factory(),
            'transfer_id'         => Transfer::factory(),
            'start_time'          => $this->faker->optional()->time(),
            'end_time'            => $this->faker->optional()->time(),
            'notes'               => $this->faker->optional()->paragraph(),
            'price'               => $this->faker->optional()->randomFloat(2, 20, 200),
            'included'            => false,
            'pickup_location'     => $this->faker->optional()->city(),
            'dropoff_location'    => $this->faker->optional()->city(),
            'pax'                 => $this->faker->optional()->numberBetween(1, 8),
        ];
    }
}
