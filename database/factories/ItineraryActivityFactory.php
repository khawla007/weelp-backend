<?php

namespace Database\Factories;

use App\Models\ItineraryActivity;
use App\Models\ItinerarySchedule;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryActivityFactory extends Factory
{
    protected $model = ItineraryActivity::class;

    public function definition(): array
    {
        return [
            'schedule_id' => ItinerarySchedule::factory(),
            'activity_id' => Activity::factory(),
            'start_time'  => $this->faker->optional()->time(),
            'end_time'    => $this->faker->optional()->time(),
            'notes'       => $this->faker->optional()->paragraph(),
            'price'       => $this->faker->optional()->randomFloat(2, 10, 500),
            'included'    => false,
        ];
    }
}
