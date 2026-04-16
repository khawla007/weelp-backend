<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Place;
use App\Models\PlaceMediaGallery;
use App\Models\City;
use App\Models\Media;
use Illuminate\Support\Arr;

class PlaceSeeder extends Seeder
{
    public function run()
    {
        // Delete all existing places and media
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        PlaceMediaGallery::query()->delete();
        Place::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        echo "All existing places deleted.\n";

        // Get city IDs by slug (dynamic lookup instead of hardcoded)
        $citySlugs = [
            'paris', 'versailles', 'boulogne-billancourt', 'marseille', 'nice',
            'cannes', 'milan', 'bergamo', 'brescia', 'florence', 'dubai'
        ];

        $cityIds = [];
        foreach ($citySlugs as $slug) {
            $city = City::where('slug', $slug)->first();
            if ($city) {
                $cityIds[$slug] = $city->id;
            }
        }

        // 10 cities with 2 places each = 20 places
        $placesData = [
            // Paris
            ['city_slug' => 'paris', 'name' => 'Eiffel Tower', 'code' => 'ET', 'slug' => 'eiffel-tower', 'description' => 'Iconic iron lattice tower and symbol of Paris.'],
            ['city_slug' => 'paris', 'name' => 'Louvre Museum', 'code' => 'LM', 'slug' => 'louvre-museum', 'description' => 'World\'s largest art museum and historic monument.'],

            // Versailles
            ['city_slug' => 'versailles', 'name' => 'Palace of Versailles', 'code' => 'PV', 'slug' => 'palace-of-versailles', 'description' => 'Opulent royal residence with Hall of Mirrors.'],
            ['city_slug' => 'versailles', 'name' => 'Versailles Gardens', 'code' => 'VG', 'slug' => 'versailles-gardens', 'description' => 'Stunning formal gardens and fountains.'],

            // Boulogne-Billancourt
            ['city_slug' => 'boulogne-billancourt', 'name' => 'Albert Kahn Museum', 'code' => 'AK', 'slug' => 'albert-kahn-museum', 'description' => 'Beautiful gardens and museum of photography.'],
            ['city_slug' => 'boulogne-billancourt', 'name' => 'Parc de Billancourt', 'code' => 'PB', 'slug' => 'parc-de-billancourt', 'description' => 'Charming urban park for relaxation.'],

            // Marseille
            ['city_slug' => 'marseille', 'name' => 'Notre-Dame de la Garde', 'code' => 'ND', 'slug' => 'notre-dame-de-la-garde', 'description' => 'Iconic basilica overlooking the city.'],
            ['city_slug' => 'marseille', 'name' => 'Old Port of Marseille', 'code' => 'OP', 'slug' => 'old-port-marseille', 'description' => 'Historic harbor with fresh seafood.'],

            // Nice
            ['city_slug' => 'nice', 'name' => 'Promenade des Anglais', 'code' => 'PA', 'slug' => 'promenade-des-anglais', 'description' => 'Famous waterfront promenade.'],
            ['city_slug' => 'nice', 'name' => 'Vieille Ville', 'code' => 'VV', 'slug' => 'vieille-ville-nice', 'description' => 'Charming old town with colorful streets.'],

            // Cannes
            ['city_slug' => 'cannes', 'name' => 'La Croisette', 'code' => 'LC', 'slug' => 'la-croisette', 'description' => 'Glamorous waterfront boulevard.'],
            ['city_slug' => 'cannes', 'name' => 'Palais des Festivals', 'code' => 'PF', 'slug' => 'palais-des-festivals', 'description' => 'Famous film festival venue.'],

            // Milan
            ['city_slug' => 'milan', 'name' => 'Milan Cathedral', 'code' => 'MC', 'slug' => 'milan-cathedral', 'description' => 'Gothic cathedral and city symbol.'],
            ['city_slug' => 'milan', 'name' => 'Galleria Vittorio Emanuele II', 'code' => 'GV', 'slug' => 'galleria-vittorio', 'description' => 'Historic shopping arcade.'],

            // Bergamo
            ['city_slug' => 'bergamo', 'name' => 'Città Alta', 'code' => 'CA', 'slug' => 'cita-alta', 'description' => 'Historic upper town with medieval walls.'],
            ['city_slug' => 'bergamo', 'name' => 'Piazza Vecchia', 'code' => 'PV', 'slug' => 'piazza-vecchia-bergamo', 'description' => 'Beautiful medieval square.'],

            // Brescia
            ['city_slug' => 'brescia', 'name' => 'Brescia Castle', 'code' => 'BC', 'slug' => 'brescia-castle', 'description' => 'Medieval fortress with city views.'],
            ['city_slug' => 'brescia', 'name' => 'Piazza della Loggia', 'code' => 'PL', 'slug' => 'piazza-della-loggia', 'description' => 'Renaissance square in historic center.'],

            // Florence
            ['city_slug' => 'florence', 'name' => 'Duomo', 'code' => 'DU', 'slug' => 'duomo-florence', 'description' => 'Iconic cathedral with magnificent dome.'],
            ['city_slug' => 'florence', 'name' => 'Ponte Vecchio', 'code' => 'PV', 'slug' => 'ponte-vecchio', 'description' => 'Medieval bridge with jewelry shops.'],

            // Dubai
            ['city_slug' => 'dubai', 'name' => 'Burj Khalifa', 'code' => 'BK', 'slug' => 'burj-khalifa', 'description' => 'The world\'s tallest building at 828 meters, offering breathtaking observation decks on the 124th and 148th floors with panoramic views of the city, desert, and ocean.'],
            ['city_slug' => 'dubai', 'name' => 'Dubai Mall', 'code' => 'DM', 'slug' => 'dubai-mall', 'description' => 'One of the world\'s largest shopping malls featuring over 1,200 stores, an indoor aquarium, ice rink, cinema complex, and the famous Dubai Fountain show outside.'],
            ['city_slug' => 'dubai', 'name' => 'Palm Jumeirah', 'code' => 'PJ', 'slug' => 'palm-jumeirah', 'description' => 'Iconic man-made island shaped like a palm tree, home to luxury resorts, beachfront villas, fine dining restaurants, and the Atlantis Aquaventure waterpark.'],
            ['city_slug' => 'dubai', 'name' => 'Dubai Marina', 'code' => 'DMR', 'slug' => 'dubai-marina', 'description' => 'Vibrant waterfront district lined with towering skyscrapers, a scenic promenade, luxury yachts, trendy cafes, and a bustling nightlife scene along the marina walk.'],
            ['city_slug' => 'dubai', 'name' => 'Dubai Creek', 'code' => 'DC', 'slug' => 'dubai-creek', 'description' => 'Historic saltwater creek dividing the city into Deira and Bur Dubai, offering traditional abra boat rides, gold and spice souks, and a glimpse into old Dubai heritage.'],
        ];

        $mediaIds = Media::pluck('id')->toArray();

        $createdCount = 0;
        foreach ($placesData as $data) {
            $citySlug = $data['city_slug'];
            if (!isset($cityIds[$citySlug])) {
                echo "Skipping place for missing city: {$citySlug}\n";
                continue;
            }

            unset($data['city_slug']);
            $data['city_id'] = $cityIds[$citySlug];

            $place = Place::create([
                ...$data,
                'type' => 'place',
                'featured_destination' => false,
            ]);

            echo "Created: {$place->name}\n";
            $createdCount++;

            // Attach random media to place (3-5 random media items)
            if (!empty($mediaIds)) {
                $mediaCount = min(rand(3, 5), count($mediaIds));
                $randomMediaIds = Arr::random($mediaIds, $mediaCount);
                foreach ($randomMediaIds as $mediaId) {
                    PlaceMediaGallery::create([
                        'place_id' => $place->id,
                        'media_id' => $mediaId,
                    ]);
                }
            }
        }

        echo "\nTotal places created: {$createdCount}\n";
    }
}
