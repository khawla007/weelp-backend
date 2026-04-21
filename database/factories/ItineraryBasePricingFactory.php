<?php

namespace Database\Factories;

use App\Models\ItineraryBasePricing;
use App\Models\Itinerary;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryBasePricingFactory extends Factory
{
    protected $model = ItineraryBasePricing::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('+1 day', '+30 days');
        $endDate = (clone $startDate)->modify('+7 days');

        return [
            'itinerary_id' => Itinerary::factory(),
            'currency'     => 'USD',
            'availability' => 'available',
            'start_date'   => $startDate,
            'end_date'     => $endDate,
        ];
    }
}
