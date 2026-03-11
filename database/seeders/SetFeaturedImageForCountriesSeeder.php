<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\CountryMediaGallery;

class SetFeaturedImageForCountriesSeeder extends Seeder
{
    /**
     * Run the database seeds to set featured image for existing countries.
     *
     * @return void
     */
    public function run()
    {
        $countries = Country::all();

        foreach ($countries as $country) {
            // Get all media gallery items for this country
            $mediaGallery = CountryMediaGallery::where('country_id', $country->id)->get();

            if ($mediaGallery->isEmpty()) {
                $this->command->warn("No media found for country: {$country->name}");
                continue;
            }

            // Check if already has a featured image
            $hasFeatured = $mediaGallery->contains('is_featured', true);

            if ($hasFeatured) {
                $this->command->info("Country '{$country->name}' already has a featured image. Skipping.");
                continue;
            }

            // Mark a random image as featured
            $randomMedia = $mediaGallery->random(1);
            $randomMedia->first()->update(['is_featured' => true]);

            $this->command->info("✅ Featured image set for country: {$country->name} (Media ID: {$randomMedia->first()->media_id})");
        }

        $this->command->info('========================================');
        $this->command->info('Featured image seeding completed!');
        $this->command->info('========================================');
    }
}
