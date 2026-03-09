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

        // Insert States
        $states = [
            // France
            ['country_id' => null, 'name' => 'Île-de-France', 'code' => 'IDF', 'slug' => 'ile-de-france'],
            ['country_id' => null, 'name' => 'Provence-Alpes-Côte d\'Azur', 'code' => 'PACA', 'slug' => 'provence-alpes-cote-dazur'],

            // Italy
            ['country_id' => null, 'name' => 'Lombardy', 'code' => 'LOM', 'slug' => 'lombardy'],
            ['country_id' => null, 'name' => 'Tuscany', 'code' => 'TOS', 'slug' => 'tuscany'],

            // Spain
            ['country_id' => null, 'name' => 'Catalonia', 'code' => 'CAT', 'slug' => 'catalonia'],
            ['country_id' => null, 'name' => 'Andalusia', 'code' => 'AND', 'slug' => 'andalusia'],

            // Turkey
            ['country_id' => null, 'name' => 'Istanbul', 'code' => 'IST', 'slug' => 'istanbul'],
            ['country_id' => null, 'name' => 'Antalya', 'code' => 'ANT', 'slug' => 'antalya'],

            // Thailand
            ['country_id' => null, 'name' => 'Bangkok', 'code' => 'BKK', 'slug' => 'bangkok'],
            ['country_id' => null, 'name' => 'Phuket', 'code' => 'PHU', 'slug' => 'phuket'],

            // Japan
            ['country_id' => null, 'name' => 'Tokyo', 'code' => 'TYO', 'slug' => 'tokyo'],
            ['country_id' => null, 'name' => 'Osaka', 'code' => 'OSA', 'slug' => 'osaka'],

            // UAE
            ['country_id' => null, 'name' => 'Dubai', 'code' => 'DXB', 'slug' => 'dubai'],
            ['country_id' => null, 'name' => 'Abu Dhabi', 'code' => 'AUH', 'slug' => 'abu-dhabi'],

            // UK
            ['country_id' => null, 'name' => 'England', 'code' => 'ENG', 'slug' => 'england'],
            ['country_id' => null, 'name' => 'Scotland', 'code' => 'SCO', 'slug' => 'scotland'],

            // India
            ['country_id' => null, 'name' => 'Maharashtra', 'code' => 'MH', 'slug' => 'maharashtra'],
            ['country_id' => null, 'name' => 'Kerala', 'code' => 'KL', 'slug' => 'kerala'],

            // Singapore
            ['country_id' => null, 'name' => 'Central', 'code' => 'CNT', 'slug' => 'central'],
            ['country_id' => null, 'name' => 'East', 'code' => 'EST', 'slug' => 'east'],
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

        $mediaIds = range(1, 5);

        foreach ($states as $data) {
            $state = State::create($data);

            // Country_Media (Array of Objects )
            // DISABLED: Media will be imported via auto-import feature
            // $randomMedias = collect($mediaIds)->random(3); // ek state ko 3 random media milega
            // foreach ($randomMedias as $mediaId) {
            //     StateMediaGallery::create([
            //         'state_id' => $state->id,
            //         'media_id'   => $mediaId,
            //     ]);
            // }

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
