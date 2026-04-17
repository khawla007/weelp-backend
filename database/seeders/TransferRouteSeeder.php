<?php

namespace Database\Seeders;

use App\Models\Place;
use App\Models\TransferRoute;
use App\Models\TransferZone;
use Illuminate\Database\Seeder;

class TransferRouteSeeder extends Seeder
{
    public function run(): void
    {
        $places = Place::whereIn('slug', [
            'dxb-airport', 'burj-khalifa', 'palm-jumeirah', 'dubai-mall', 'dubai-marina',
        ])->get()->keyBy('slug');

        $zones = TransferZone::whereIn('slug', [
            'zone-a', 'zone-b', 'zone-c', 'zone-d', 'zone-e',
        ])->get()->keyBy('slug');

        if ($places->isEmpty() || $zones->isEmpty()) {
            return;
        }

        $routeDefs = [
            ['slug' => 'dxb-to-burj-khalifa',    'name' => 'DXB Airport → Burj Khalifa',  'origin' => 'dxb-airport',  'dest' => 'burj-khalifa',  'from_zone' => 'zone-a', 'to_zone' => 'zone-b', 'distance' => 15.2, 'duration' => 25, 'popular' => true],
            ['slug' => 'dxb-to-palm-jumeirah',   'name' => 'DXB Airport → Palm Jumeirah', 'origin' => 'dxb-airport',  'dest' => 'palm-jumeirah', 'from_zone' => 'zone-a', 'to_zone' => 'zone-c', 'distance' => 28.5, 'duration' => 40, 'popular' => true],
            ['slug' => 'dxb-to-dubai-marina',    'name' => 'DXB Airport → Dubai Marina',  'origin' => 'dxb-airport',  'dest' => 'dubai-marina',  'from_zone' => 'zone-a', 'to_zone' => 'zone-e', 'distance' => 30.0, 'duration' => 45, 'popular' => false],
            ['slug' => 'dubai-mall-to-palm',     'name' => 'Dubai Mall → Palm Jumeirah',  'origin' => 'dubai-mall',   'dest' => 'palm-jumeirah', 'from_zone' => 'zone-d', 'to_zone' => 'zone-c', 'distance' => 22.0, 'duration' => 35, 'popular' => false],
            ['slug' => 'burj-khalifa-to-marina', 'name' => 'Burj Khalifa → Dubai Marina', 'origin' => 'burj-khalifa', 'dest' => 'dubai-marina',  'from_zone' => 'zone-b', 'to_zone' => 'zone-e', 'distance' => 18.4, 'duration' => 28, 'popular' => false],
        ];

        foreach ($routeDefs as $def) {
            TransferRoute::updateOrCreate(
                ['slug' => $def['slug']],
                [
                    'name'             => $def['name'],
                    'origin_type'      => 'place',
                    'origin_id'        => $places[$def['origin']]->id,
                    'destination_type' => 'place',
                    'destination_id'   => $places[$def['dest']]->id,
                    'from_zone_id'     => $zones[$def['from_zone']]->id,
                    'to_zone_id'       => $zones[$def['to_zone']]->id,
                    'distance_km'      => $def['distance'],
                    'duration_minutes' => $def['duration'],
                    'is_active'        => true,
                    'is_popular'       => $def['popular'],
                ],
            );
        }
    }
}
