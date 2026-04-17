<?php

namespace Database\Seeders;

use App\Models\Place;
use App\Models\Transfer;
use App\Models\TransferPricingAvailability;
use App\Models\TransferRoute;
use App\Models\TransferVendorRoute;
use App\Models\TransferZonePrice;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminTransferSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'khawla@fanaticcoders.com'],
            ['name' => 'Khawla Admin', 'password' => Hash::make('khawla@123#'), 'role' => 'admin'],
        );

        $routes = TransferRoute::whereIn('slug', [
            'dxb-to-burj-khalifa',
            'dxb-to-palm-jumeirah',
            'dxb-to-dubai-marina',
            'dubai-mall-to-palm',
            'burj-khalifa-to-marina',
        ])->get()->keyBy('slug');

        if ($routes->isEmpty()) {
            return;
        }

        $transferDefs = [
            ['route' => 'dxb-to-burj-khalifa',    'slug' => 'admin-dxb-to-burj-khalifa',    'name' => 'Admin: DXB → Burj Khalifa',    'vehicle' => 'Sedan',        'price_type' => 'per_vehicle', 'inclusion' => 'Meet & greet, bottled water, WiFi'],
            ['route' => 'dxb-to-palm-jumeirah',   'slug' => 'admin-dxb-to-palm-jumeirah',   'name' => 'Admin: DXB → Palm Jumeirah',   'vehicle' => 'SUV',          'price_type' => 'per_vehicle', 'inclusion' => 'Meet & greet, AC vehicle, child seat available'],
            ['route' => 'dxb-to-dubai-marina',    'slug' => 'admin-dxb-to-dubai-marina',    'name' => 'Admin: DXB → Dubai Marina',    'vehicle' => 'Luxury Sedan', 'price_type' => 'per_person', 'inclusion' => 'Luxury driver, refreshments, WiFi'],
            ['route' => 'dubai-mall-to-palm',     'slug' => 'admin-dubai-mall-to-palm',     'name' => 'Admin: Dubai Mall → Palm',     'vehicle' => 'Sedan',        'price_type' => 'per_vehicle', 'inclusion' => 'AC vehicle, bottled water'],
            ['route' => 'burj-khalifa-to-marina', 'slug' => 'admin-burj-khalifa-to-marina', 'name' => 'Admin: Burj Khalifa → Marina', 'vehicle' => 'SUV',          'price_type' => 'per_vehicle', 'inclusion' => 'AC vehicle, English-speaking driver'],
        ];

        foreach ($transferDefs as $def) {
            $route = $routes[$def['route']] ?? null;
            if (! $route) {
                continue;
            }
            $originPlace = Place::find($route->origin_id);
            $destPlace   = Place::find($route->destination_id);
            $cell = TransferZonePrice::where('from_zone_id', $route->from_zone_id)
                ->where('to_zone_id', $route->to_zone_id)
                ->first();
            $basePrice = $cell ? (float) $cell->price : 50.0;

            $transfer = Transfer::updateOrCreate(
                ['slug' => $def['slug']],
                [
                    'name'              => $def['name'],
                    'description'       => $def['name'].' private admin transfer service.',
                    'transfer_type'     => 'One-way',
                    'transfer_route_id' => $route->id,
                ],
            );

            TransferVendorRoute::updateOrCreate(
                ['transfer_id' => $transfer->id],
                [
                    'is_vendor'        => false,
                    'vendor_id'        => null,
                    'route_id'         => null,
                    'pickup_location'  => $originPlace->name,
                    'dropoff_location' => $destPlace->name,
                    'pickup_place_id'  => $originPlace->id,
                    'dropoff_place_id' => $destPlace->id,
                    'vehicle_type'     => $def['vehicle'],
                    'inclusion'        => $def['inclusion'],
                ],
            );

            TransferPricingAvailability::updateOrCreate(
                ['transfer_id' => $transfer->id, 'is_vendor' => false],
                [
                    'base_price'           => $basePrice,
                    'currency'             => 'USD',
                    'price_type'           => $def['price_type'],
                    'extra_luggage_charge' => 10.00,
                    'waiting_charge'       => 5.00,
                ],
            );
        }
    }
}
