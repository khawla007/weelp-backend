<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Place;
use App\Models\PlaceMediaGallery;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

        // 10 cities with 2 places each = 20 places
        // Using existing city media IDs
        $placesData = [
            // Paris (city_id: 382)
            ['city_id' => 382, 'name' => 'Eiffel Tower', 'code' => 'ET', 'slug' => 'eiffel-tower', 'description' => 'Iconic iron lattice tower and symbol of Paris.', 'media_ids' => [385]],
            ['city_id' => 382, 'name' => 'Louvre Museum', 'code' => 'LM', 'slug' => 'louvre-museum', 'description' => 'World\'s largest art museum and historic monument.', 'media_ids' => [386]],

            // Versailles (city_id: 383)
            ['city_id' => 383, 'name' => 'Palace of Versailles', 'code' => 'PV', 'slug' => 'palace-of-versailles', 'description' => 'Opulent royal residence with Hall of Mirrors.', 'media_ids' => [389]],
            ['city_id' => 383, 'name' => 'Versailles Gardens', 'code' => 'VG', 'slug' => 'versailles-gardens', 'description' => 'Stunning formal gardens and fountains.', 'media_ids' => [390]],

            // Boulogne-Billancourt (city_id: 384)
            ['city_id' => 384, 'name' => 'Albert Kahn Museum', 'code' => 'AK', 'slug' => 'albert-kahn-museum', 'description' => 'Beautiful gardens and museum of photography.', 'media_ids' => [393]],
            ['city_id' => 384, 'name' => 'Parc de Billancourt', 'code' => 'PB', 'slug' => 'parc-de-billancourt', 'description' => 'Charming urban park for relaxation.', 'media_ids' => [394]],

            // Marseille (city_id: 385)
            ['city_id' => 385, 'name' => 'Notre-Dame de la Garde', 'code' => 'ND', 'slug' => 'notre-dame-de-la-garde', 'description' => 'Iconic basilica overlooking the city.', 'media_ids' => [397]],
            ['city_id' => 385, 'name' => 'Old Port of Marseille', 'code' => 'OP', 'slug' => 'old-port-marseille', 'description' => 'Historic harbor with fresh seafood.', 'media_ids' => [398]],

            // Nice (city_id: 386)
            ['city_id' => 386, 'name' => 'Promenade des Anglais', 'code' => 'PA', 'slug' => 'promenade-des-anglais', 'description' => 'Famous waterfront promenade.', 'media_ids' => [401]],
            ['city_id' => 386, 'name' => 'Vieille Ville', 'code' => 'VV', 'slug' => 'vieille-ville-nice', 'description' => 'Charming old town with colorful streets.', 'media_ids' => [401]],

            // Cannes (city_id: 387)
            ['city_id' => 387, 'name' => 'La Croisette', 'code' => 'LC', 'slug' => 'la-croisette', 'description' => 'Glamorous waterfront boulevard.', 'media_ids' => [402]],
            ['city_id' => 387, 'name' => 'Palais des Festivals', 'code' => 'PF', 'slug' => 'palais-des-festivals', 'description' => 'Famous film festival venue.', 'media_ids' => [403]],

            // Milan (city_id: 388)
            ['city_id' => 388, 'name' => 'Milan Cathedral', 'code' => 'MC', 'slug' => 'milan-cathedral', 'description' => 'Gothic cathedral and city symbol.', 'media_ids' => [407]],
            ['city_id' => 388, 'name' => 'Galleria Vittorio Emanuele II', 'code' => 'GV', 'slug' => 'galleria-vittorio', 'description' => 'Historic shopping arcade.', 'media_ids' => [408]],

            // Bergamo (city_id: 389)
            ['city_id' => 389, 'name' => 'Città Alta', 'code' => 'CA', 'slug' => 'cita-alta', 'description' => 'Historic upper town with medieval walls.', 'media_ids' => [409]],
            ['city_id' => 389, 'name' => 'Piazza Vecchia', 'code' => 'PV', 'slug' => 'piazza-vecchia-bergamo', 'description' => 'Beautiful medieval square.', 'media_ids' => [409]],

            // Brescia (city_id: 390)
            ['city_id' => 390, 'name' => 'Brescia Castle', 'code' => 'BC', 'slug' => 'brescia-castle', 'description' => 'Medieval fortress with city views.', 'media_ids' => [410]],
            ['city_id' => 390, 'name' => 'Piazza della Loggia', 'code' => 'PL', 'slug' => 'piazza-della-loggia', 'description' => 'Renaissance square in historic center.', 'media_ids' => [411]],

            // Florence (city_id: 391)
            ['city_id' => 391, 'name' => 'Duomo', 'code' => 'DU', 'slug' => 'duomo-florence', 'description' => 'Iconic cathedral with magnificent dome.', 'media_ids' => [413]],
            ['city_id' => 391, 'name' => 'Ponte Vecchio', 'code' => 'PV', 'slug' => 'ponte-vecchio', 'description' => 'Medieval bridge with jewelry shops.', 'media_ids' => [414]],
        ];

        foreach ($placesData as $data) {
            $mediaIds = $data['media_ids'];
            unset($data['media_ids']);

            $place = Place::create([
                ...$data,
                'type' => 'place',
                'featured_destination' => false,
            ]);

            // Attach media (first as featured)
            foreach ($mediaIds as $index => $mediaId) {
                PlaceMediaGallery::create([
                    'place_id' => $place->id,
                    'media_id' => $mediaId,
                    'is_featured' => $index === 0,
                ]);
            }

            echo "Created: {$place->name}\n";
        }

        echo "\nTotal places created: ".count($placesData)."\n";
    }
}
