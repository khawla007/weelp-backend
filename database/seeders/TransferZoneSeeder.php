<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\State;
use App\Models\TransferZone;
use App\Models\TransferZoneLocation;
use App\Models\TransferZonePrice;
use Illuminate\Database\Seeder;

class TransferZoneSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::where('name', 'United Arab Emirates')->orWhere('slug', 'uae')->first()
            ?? Country::create(['slug' => 'uae', 'name' => 'United Arab Emirates', 'code' => 'AE']);

        $state = State::where('country_id', $country->id)
            ->where(fn ($q) => $q->where('name', 'Dubai')->orWhere('slug', 'dubai-state'))
            ->first()
            ?? State::create(['slug' => 'dubai-state', 'name' => 'Dubai', 'country_id' => $country->id]);

        $city = City::where('state_id', $state->id)
            ->where(fn ($q) => $q->where('name', 'Dubai')->orWhere('slug', 'dubai'))
            ->first()
            ?? City::create(['slug' => 'dubai', 'name' => 'Dubai', 'state_id' => $state->id]);

        $placeDefs = [
            ['slug' => 'dxb-airport',   'name' => 'Dubai International Airport', 'type' => 'airport', 'code' => 'DXB'],
            ['slug' => 'burj-khalifa',  'name' => 'Burj Khalifa',                'type' => 'place',   'code' => 'BRJ'],
            ['slug' => 'palm-jumeirah', 'name' => 'Palm Jumeirah',               'type' => 'place',   'code' => 'PLM'],
            ['slug' => 'dubai-mall',    'name' => 'Dubai Mall',                  'type' => 'place',   'code' => 'DBM'],
            ['slug' => 'dubai-marina',  'name' => 'Dubai Marina',                'type' => 'place',   'code' => 'MRN'],
        ];
        foreach ($placeDefs as $def) {
            Place::firstOrCreate(
                ['slug' => $def['slug']],
                ['name' => $def['name'], 'type' => $def['type'], 'code' => $def['code'], 'city_id' => $city->id],
            );
        }
        $places = Place::whereIn('slug', array_column($placeDefs, 'slug'))->get()->keyBy('slug');

        $zoneDefs = [
            ['slug' => 'zone-a', 'name' => 'Zone A', 'sort_order' => 1, 'place_slug' => 'dxb-airport'],
            ['slug' => 'zone-b', 'name' => 'Zone B', 'sort_order' => 2, 'place_slug' => 'burj-khalifa'],
            ['slug' => 'zone-c', 'name' => 'Zone C', 'sort_order' => 3, 'place_slug' => 'palm-jumeirah'],
            ['slug' => 'zone-d', 'name' => 'Zone D', 'sort_order' => 4, 'place_slug' => 'dubai-mall'],
            ['slug' => 'zone-e', 'name' => 'Zone E', 'sort_order' => 5, 'place_slug' => 'dubai-marina'],
        ];
        foreach ($zoneDefs as $def) {
            $zone = TransferZone::updateOrCreate(
                ['sort_order' => $def['sort_order']],
                ['name' => $def['name'], 'slug' => $def['slug'], 'is_active' => true],
            );
            TransferZoneLocation::firstOrCreate([
                'transfer_zone_id' => $zone->id,
                'locatable_type'   => 'place',
                'locatable_id'     => $places[$def['place_slug']]->id,
            ]);
        }
        $zones = TransferZone::whereIn('slug', array_column($zoneDefs, 'slug'))->get()->keyBy('slug');

        TransferZoneLocation::firstOrCreate([
            'transfer_zone_id' => $zones['zone-b']->id,
            'locatable_type'   => 'city',
            'locatable_id'     => $city->id,
        ]);

        $basePrices = [
            'zone-a' => ['zone-a' => 20, 'zone-b' => 55, 'zone-c' => 75, 'zone-d' => 60, 'zone-e' => 70],
            'zone-b' => ['zone-a' => 55, 'zone-b' => 15, 'zone-c' => 50, 'zone-d' => 18, 'zone-e' => 45],
            'zone-c' => ['zone-a' => 75, 'zone-b' => 50, 'zone-c' => 20, 'zone-d' => 55, 'zone-e' => 35],
            'zone-d' => ['zone-a' => 60, 'zone-b' => 18, 'zone-c' => 55, 'zone-d' => 15, 'zone-e' => 50],
            'zone-e' => ['zone-a' => 70, 'zone-b' => 45, 'zone-c' => 35, 'zone-d' => 50, 'zone-e' => 15],
        ];
        foreach ($basePrices as $fromSlug => $row) {
            foreach ($row as $toSlug => $price) {
                TransferZonePrice::updateOrCreate(
                    ['from_zone_id' => $zones[$fromSlug]->id, 'to_zone_id' => $zones[$toSlug]->id],
                    ['price' => $price, 'currency' => 'USD'],
                );
            }
        }
    }
}
