<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;
use App\Models\CityMediaGallery;

class SetFeaturedImageForCitiesSeeder extends Seeder
{
    public function run()
    {
        $cities = City::all();

        foreach ($cities as $city) {
            $mediaGallery = CityMediaGallery::where('city_id', $city->id)->get();

            if ($mediaGallery->isEmpty()) {
                $this->command->warn("No media found for city: {$city->name}");
                continue;
            }

            if (!$mediaGallery->contains('is_featured', true)) {
                $mediaGallery->random(1)->first()->update(['is_featured' => true]);
                $this->command->info("Featured image set for city: {$city->name}");
            }

            if (!$city->featured_destination) {
                $city->update(['featured_destination' => true]);
                $this->command->info("Marked featured_destination for city: {$city->name}");
            }
        }

        $this->command->info('City featured data seeding completed.');
    }
}
