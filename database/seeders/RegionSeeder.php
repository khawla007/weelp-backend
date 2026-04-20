<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionSeeder extends Seeder
{
    public function run()
    {
        // Truncate regions table first
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('region_country')->truncate();
        Region::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $regions = [
            [
                'name' => 'Asia',
                'slug' => 'asia',
                'type' => 'continent',
                'description' => 'The largest and most populous continent, home to diverse cultures, ancient civilizations, and rapidly modernizing cities.',
                'image_url' => null,
            ],
            [
                'name' => 'Europe',
                'slug' => 'europe',
                'type' => 'continent',
                'description' => 'A continent rich in history, art, and culture, known for its architectural landmarks, diverse cuisines, and scenic landscapes.',
                'image_url' => null,
            ],
            [
                'name' => 'Middle East',
                'slug' => 'middle-east',
                'type' => 'region',
                'description' => 'A crossroads of ancient civilizations, featuring deserts, historic cities, and modern developments along the Silk Road routes.',
                'image_url' => null,
            ],
            [
                'name' => 'North America',
                'slug' => 'north-america',
                'type' => 'continent',
                'description' => 'A vast continent spanning from the Arctic to the tropics, featuring diverse landscapes from mountains to beaches and metropolises.',
                'image_url' => null,
            ],
            [
                'name' => 'South America',
                'slug' => 'south-america',
                'type' => 'continent',
                'description' => 'A continent of vibrant cultures, Amazon rainforests, Andes mountains, and rich indigenous heritage.',
                'image_url' => null,
            ],
            [
                'name' => 'Africa',
                'slug' => 'africa',
                'type' => 'continent',
                'description' => 'The cradle of humanity, featuring vast savannas, deserts, rainforests, and incredible wildlife alongside ancient civilizations.',
                'image_url' => null,
            ],
            [
                'name' => 'Oceania',
                'slug' => 'oceania',
                'type' => 'continent',
                'description' => 'A region of islands in the Pacific, featuring Australia\'s outback, Polynesian paradises, and unique Maori and Aboriginal cultures.',
                'image_url' => null,
            ],
        ];

        foreach ($regions as $regionData) {
            Region::create($regionData);
        }

        $this->command->info('Seeded '.count($regions).' regions.');
    }
}
