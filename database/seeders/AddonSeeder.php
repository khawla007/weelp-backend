<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Addon;

class AddonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $addons = [
            [
                'name' => 'Extra Luggage',
                'type' => 'transfer',
                'description' => 'Additional luggage allowance',
                'price' => 20.00,
                'sale_price' => 15.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'VIP Seat',
                'type' => 'package',
                'description' => 'Upgrade to VIP seating',
                'price' => 50.00,
                'sale_price' => 40.00,
                'price_calculation' => 'per_package',
                'active_status' => true,
            ],
            [
                'name' => 'Meal Package',
                'type' => 'itinerary',
                'description' => 'Includes breakfast and lunch',
                'price' => 30.00,
                'sale_price' => null,
                'price_calculation' => 'per_itinerary',
                'active_status' => true,
            ],
            [
                'name' => 'Priority Boarding',
                'type' => 'transfer',
                'description' => 'Board before other passengers',
                'price' => 10.00,
                'sale_price' => null,
                'price_calculation' => 'per_itinerary',
                'active_status' => true,
            ],
            [
                'name' => 'Insurance',
                'type' => 'package',
                'description' => 'Travel insurance coverage',
                'price' => 25.00,
                'sale_price' => 20.00,
                'price_calculation' => 'per_itinerary',
                'active_status' => true,
            ],
            [
                'name' => 'WiFi Access',
                'type' => 'activity',
                'description' => 'Onboard internet access',
                'price' => 5.00,
                'sale_price' => null,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Photography Package',
                'type' => 'activity',
                'description' => 'Professional trip photos',
                'price' => 40.00,
                'sale_price' => 30.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Child Seat',
                'type' => 'transfer',
                'description' => 'Child safety seat',
                'price' => 10.00,
                'sale_price' => 8.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Driver Guide',
                'type' => 'itinerary',
                'description' => 'Personal driver and guide',
                'price' => 100.00,
                'sale_price' => 80.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Souvenir Package',
                'type' => 'package',
                'description' => 'Gift items for travelers',
                'price' => 15.00,
                'sale_price' => 12.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Extended Warranty',
                'type' => 'package',
                'description' => 'Warranty extension for products',
                'price' => 12.00,
                'sale_price' => null,
                'price_calculation' => 'per_activity',
                'active_status' => false,
            ],
            [
                'name' => 'Spa Access',
                'type' => 'itinerary',
                'description' => 'Full day spa access',
                'price' => 70.00,
                'sale_price' => 60.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Cocktail Package',
                'type' => 'itinerary',
                'description' => 'Unlimited cocktails for 2 hours',
                'price' => 25.00,
                'sale_price' => 20.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Photography Prints',
                'type' => 'activity',
                'description' => 'Printed copies of trip photos',
                'price' => 18.00,
                'sale_price' => 15.00,
                'price_calculation' => 'per_activity',
                'active_status' => false,
            ],
            [
                'name' => 'Adventure Kit',
                'type' => 'activity',
                'description' => 'Includes trekking sticks and gear',
                'price' => 35.00,
                'sale_price' => 30.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
        ];

        foreach ($addons as $addon) {
            \App\Models\Addon::create($addon);
        }
    }

}
