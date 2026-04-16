<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\State;
use App\Models\StateMediaGallery;
use App\Models\StateLocationDetail;
use App\Models\StateTravelInfo;
use App\Models\StateSeason;
use App\Models\StateEvent;
use App\Models\StateAdditionalInfo;
use App\Models\StateFaq;
use App\Models\StateSeo;
use App\Models\Media;
use Illuminate\Support\Arr;

class StateSeeder extends Seeder
{
    /**
     * Generate a random date/datetime in 2027
     * @param bool $dateOnly If true, return date only (Y-m-d), otherwise datetime (Y-m-d H:i:s)
     */
    private function random2027Date(bool $dateOnly = false): string
    {
        $start = strtotime('2027-01-01');
        $end = strtotime('2027-12-31');
        $timestamp = mt_rand($start, $end);
        return date($dateOnly ? 'Y-m-d' : 'Y-m-d H:i:s', $timestamp);
    }

    public function run()
    {
        // Delete all existing states and related data
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        State::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        echo "All existing states deleted.\n";

        $mediaIds = Media::pluck('id')->toArray();

        // Insert States
        $states = [
            // France
            ['country_id' => null, 'name' => 'Île-de-France', 'code' => 'IDF', 'slug' => 'ile-de-france', 'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Île-de-France is home to Paris and offers world-renowned landmarks, cuisine, and culture.'],
            ['country_id' => null, 'name' => 'Provence-Alpes-Côte d\'Azur', 'code' => 'PACA', 'slug' => 'provence-alpes-cote-dazur', 'description' => 'Ut enim ad minim veniam, quis nostrud exercitation ullamco. The French Riviera offers stunning Mediterranean coastline, lavender fields, and charming medieval villages.'],

            // Italy
            ['country_id' => null, 'name' => 'Lombardy', 'code' => 'LOM', 'slug' => 'lombardy', 'description' => 'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum. Lombardy is Italy\'s economic powerhouse, featuring Milan\'s fashion district and the beautiful Italian Lakes region.'],
            ['country_id' => null, 'name' => 'Tuscany', 'code' => 'TOS', 'slug' => 'tuscany', 'description' => 'Excepteur sint occaecat cupidatat non proident. Tuscany is renowned for its Renaissance art, rolling vineyards, and historic cities like Florence and Siena.'],

            // Spain
            ['country_id' => null, 'name' => 'Catalonia', 'code' => 'CAT', 'slug' => 'catalonia', 'description' => 'Sunt in culpa qui officia deserunt mollit anim. Catalonia boasts Barcelona\'s architecture, the Costa Brava\'s beaches, and the Pyrenees mountains.'],
            ['country_id' => null, 'name' => 'Andalusia', 'code' => 'AND', 'slug' => 'andalusia', 'description' => 'Nemo enim ipsam voluptatem quia voluptas sit. Andalusia offers flamenco dancing, Moorish architecture in Granada, and beautiful sherry wine country.'],

            // Turkey
            ['country_id' => null, 'name' => 'Istanbul', 'code' => 'IST', 'slug' => 'istanbul', 'description' => 'Neque porro quisquam est, qui dolorem ipsum. Istanbul straddles two continents, offering rich Byzantine and Ottoman heritage with stunning mosques and palaces.'],
            ['country_id' => null, 'name' => 'Antalya', 'code' => 'ANT', 'slug' => 'antalya', 'description' => 'Ut aut reiciendis voluptatibus maiores alias. Antalya is the Turkish Riviera, featuring turquoise waters, ancient ruins, and stunning Mediterranean coastline.'],

            // Thailand
            ['country_id' => null, 'name' => 'Bangkok', 'code' => 'BKK', 'slug' => 'bangkok', 'description' => 'Nam libero tempore, cum soluta nobis est. Bangkok is a vibrant metropolis featuring ornate temples, floating markets, and world-class street food.'],
            ['country_id' => null, 'name' => 'Phuket', 'code' => 'PHU', 'slug' => 'phuket', 'description' => 'Omnis voluptas assumenda est, omnis dolor repellendus. Phuket offers pristine beaches, crystal waters, and vibrant nightlife in southern Thailand.'],

            // Japan
            ['country_id' => null, 'name' => 'Tokyo', 'code' => 'TYO', 'slug' => 'tokyo', 'description' => 'Temporibus autem quibusdam et aut officiis. Tokyo seamlessly blends ultramodern neon-lit skyscrapers with traditional temples and gardens.'],
            ['country_id' => null, 'name' => 'Osaka', 'code' => 'OSA', 'slug' => 'osaka', 'description' => 'Itaque earum rerum hic tenetur a sapiente. Osaka is known for its street food, comedy culture, and historic Osaka Castle.'],

            // UAE
            ['country_id' => null, 'name' => 'Dubai', 'code' => 'DXB', 'slug' => 'dubai', 'description' => 'Et harum quidem rerum facilis est et expedita. Dubai is a futuristic city of luxury shopping, ultramodern architecture, and lively nightlife scenes.'],
            ['country_id' => null, 'name' => 'Abu Dhabi', 'code' => 'AUH', 'slug' => 'abu-dhabi', 'description' => 'Distinctio nam libero tempore, cum soluta. Abu Dhabi combines modern marvels like the Louvre with traditional Arabian culture and stunning mosques.'],

            // UK
            ['country_id' => null, 'name' => 'England', 'code' => 'ENG', 'slug' => 'england', 'description' => 'Nisi ut aliquid ex ea commodi consequatur. England offers historic landmarks, rolling countryside, and vibrant cities from London to the Cotswolds.'],
            ['country_id' => null, 'name' => 'Scotland', 'code' => 'SCO', 'slug' => 'scotland', 'description' => 'Quis autem vel eum iure reprehenderit. Scotland features dramatic highlands, historic castles, lochs, and the cultural hub of Edinburgh.'],

            // India
            ['country_id' => null, 'name' => 'Maharashtra', 'code' => 'MH', 'slug' => 'maharashtra', 'description' => 'Velit esse quam nihil molestiae consequatur. Maharashtra is home to Mumbai\'s Bollywood, ancient Ajanta caves, and beautiful coastal Konkan region.'],
            ['country_id' => null, 'name' => 'Kerala', 'code' => 'KL', 'slug' => 'kerala', 'description' => 'Neque porro quisquam est, qui dolorem. Kerala is God\'s Own Country with backwaters, tea plantations, and pristine beaches.'],

            // Singapore
            ['country_id' => null, 'name' => 'Central', 'code' => 'CNT', 'slug' => 'central', 'description' => 'Ut aut reiciendis voluptatibus maiores. Central Singapore features Marina Bay Sands, Orchard Road shopping, and the Civic District.'],
            ['country_id' => null, 'name' => 'East', 'code' => 'EST', 'slug' => 'east', 'description' => 'Duis aute irure dolor in reprehenderit. East Singapore offers Changi Airport, East Coast Park, and beautiful coastal neighborhoods.'],
        ];

        // Get country IDs
        $countryMap = [
            'Île-de-France' => 'France',
            'Provence-Alpes-Côte d\'Azur' => 'France',
            'Lombardy' => 'Italy',
            'Tuscany' => 'Italy',
            'Catalonia' => 'Spain',
            'Andalusia' => 'Spain',
            'Istanbul' => 'Turkey',
            'Antalya' => 'Turkey',
            'Bangkok' => 'Thailand',
            'Phuket' => 'Thailand',
            'Tokyo' => 'Japan',
            'Osaka' => 'Japan',
            'Dubai' => 'United Arab Emirates',
            'Abu Dhabi' => 'United Arab Emirates',
            'England' => 'United Kingdom',
            'Scotland' => 'United Kingdom',
            'Maharashtra' => 'India',
            'Kerala' => 'India',
            'Central' => 'Singapore',
            'East' => 'Singapore',
        ];

        foreach ($states as &$stateData) {
            $countryName = $countryMap[$stateData['name']];
            $country = \App\Models\Country::where('name', $countryName)->first();
            if (!$country) {
                echo "WARNING: Country '{$countryName}' not found for state '{$stateData['name']}' - skipping\n";
                continue;
            }
            $stateData['country_id'] = $country->id;
        }
        unset($stateData);

        foreach ($states as $data) {
            $state = State::create($data);

            // Media Gallery - 3-4 random images
            $selectedMediaIds = Arr::random($mediaIds, rand(3, 5));
            foreach ($selectedMediaIds as $mediaId) {
                StateMediaGallery::create([
                    'state_id' => $state->id,
                    'media_id' => $mediaId,
                ]);
            }

            // Insert State Details
            StateLocationDetail::create([
                'state_id' => $state->id,
                'latitude' => '26.9124',
                'longitude' => '75.7873',
                'capital_city' => 'Jaipur',
                'population' => 80000000,
                'currency' => 'INR',
                'timezone' => 'GMT+5:30',
                'language' => ['Hindi', 'Rajasthani'],
                'local_cuisine' => ['Dal Baati Churma', 'Gatte ki Sabzi']
            ]);

            // Insert Travel Information
            StateTravelInfo::create([
                'state_id' => $state->id,
                'airport' => 'Jaipur International Airport',
                'public_transportation' => ['Buses', 'Rickshaws', 'Trains'],
                'taxi_available' => true,
                'rental_cars_available' => true,
                'hotels' => true,
                'hostels' => true,
                'apartments' => true,
                'resorts' => true,
                'visa_requirements' => 'No separate visa needed for domestic tourists',
                'best_time_to_visit' => 'October - March',
                'travel_tips' => 'Carry light cotton clothes during summer',
                'safety_information' => 'Safe but be cautious of local scams'
            ]);

            // Insert Seasons
            StateSeason::create([
                'state_id' => $state->id,
                'name' => 'Winter',
                'months' => ['November', 'February'],
                'weather' => 'Pleasant during the day, cold at night',
                'activities' => ['Camel Safari', 'Sightseeing']
            ]);

            // Insert Events
            StateEvent::create([
                'state_id' => $state->id,
                'name' => 'Pushkar Fair',
                'type' => ['Cultural', 'Festival'],
                'date' => '2025-11-14',
                'location' => 'Pushkar, Rajasthan',
                'description' => 'A vibrant fair with camels, cultural performances, and shopping'
            ]);

            // Insert Additional Information
            StateAdditionalInfo::create([
                'state_id' => $state->id,
                'title' => 'Must-Visit Places',
                'content' => 'Jaipur, Udaipur, Jaisalmer, Mount Abu'
            ]);

            $stateId = $state->id;

            $lastQuestion = StateFaq::where('state_id', $stateId)
            ->orderBy('question_number', 'desc')
            ->first();

            $questionNumber = $lastQuestion ? $lastQuestion->question_number + 1 : 1;

            $faqs = [
                [
                    'question' => 'Do I need a visa to visit India?',
                    'answer' => 'Yes, but Visa on arrival is available for many countries.'
                ],
                [
                    'question' => 'What is the currency in India?',
                    'answer' => 'The Indian Rupee (INR) is the official currency.'
                ]
            ];
            
            foreach ($faqs as $faq) {
                StateFaq::create([
                    'state_id' => $state->id,
                    'question_number' => $questionNumber,
                    'question' => $faq['question'],
                    'answer' => $faq['answer']
                ]);
                $questionNumber++;
            }

            // 8️⃣ Insert SEO Data
            StateSeo::create([
                'state_id' => $state->id,
                'meta_title' => 'Visit Rajasthan - Travel Guide',
                'meta_description' => 'Explore the royal heritage of Rajasthan with our ultimate travel guide.',
                'keywords' => 'Rajasthan, Travel, Jaipur, Jaisalmer, Udaipur',
                'og_image_url' => 'https://example.com/og-rajasthan.jpg',
                'canonical_url' => 'https://example.com/rajasthan',
                'schema_type' => 'TravelDestination',
                'schema_data' => [
                    "@context" => "https://schema.org",
                    "@type" => "TravelDestination",
                    "name" => "Rajasthan",
                    "description" => "The land of kings and royal heritage.",
                    "image" => "https://example.com/rajasthan.jpg"
                ],
            ]);
        }
    }
}
