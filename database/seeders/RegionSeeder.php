<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;
use App\Models\Country;

class RegionSeeder extends Seeder
{
    public function run()
    {
        $regions = [
            [
                'name' => 'Europe',
                'slug' => 'europe',
                'type' => 'region',
                'description' => 'European region',
                'image_url' => 'https://example.com/europe.jpg',
                'countries' => ['France', 'Germany', 'Spain']
            ],
            [
                'name' => 'Asia',
                'slug' => 'asia',
                'type' => 'region',
                'description' => 'Asian region',
                'image_url' => 'https://example.com/asia.jpg',
                'countries' => ['India', 'China', 'Japan']
            ]
        ];

        foreach ($regions as $regionData) {
            $region = Region::create([
                'name' => $regionData['name'],
                'slug' => $regionData['slug'],
                'type' => $regionData['type'],
                'description' => $regionData['description'],
                'image_url' => $regionData['image_url']
            ]);

            // Assign Countries
            $countryIds = Country::whereIn('name', $regionData['countries'])->pluck('id');
            $region->countries()->attach($countryIds);
        }
    }
}
