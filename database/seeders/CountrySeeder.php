<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Country;
use App\Models\CountryLocationDetail;
use App\Models\CountryTravelInfo;
use App\Models\CountrySeason;
use App\Models\CountryEvent;
use App\Models\CountryAdditionalInfo;
use App\Models\CountryFaq;
use App\Models\CountrySeo;
use App\Models\CountryMediaGallery;
use App\Models\Region;

class CountrySeeder extends Seeder
{
    public function run()
    {
        // Truncate all country-related tables in correct order
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        CountryLocationDetail::truncate();
        CountryTravelInfo::truncate();
        CountrySeason::truncate();
        CountryEvent::truncate();
        CountryAdditionalInfo::truncate();
        CountryFaq::truncate();
        CountrySeo::truncate();
        CountryMediaGallery::truncate();
        Country::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $countries = [
            // MIDDLE EAST
            [
                'name' => 'United Arab Emirates',
                'code' => 'AE',
                'slug' => 'united-arab-emirates',
                'type' => 'country',
                'region' => 'Middle East',
                'description' => 'The United Arab Emirates is a federation of seven emirates known for its modern architecture, luxury shopping, and lively nightlife scenes. From the iconic Burj Khalifa in Dubai to the Sheikh Zayed Mosque in Abu Dhabi, the UAE offers a blend of traditional Arabian culture and futuristic innovation.',
                'featured_destination' => true,
            ],
            [
                'name' => 'Saudi Arabia',
                'code' => 'SA',
                'slug' => 'saudi-arabia',
                'type' => 'country',
                'region' => 'Middle East',
                'description' => 'Saudi Arabia is the largest country in the Middle East, home to Islam\'s two holiest cities - Mecca and Medina. From the ancient ruins of Al-Ula to the modern skyline of Riyadh, the Kingdom offers rich history, diverse landscapes, and ambitious Vision 2030 transformations.',
                'featured_destination' => true,
            ],
            [
                'name' => 'Qatar',
                'code' => 'QA',
                'slug' => 'qatar',
                'type' => 'country',
                'region' => 'Middle East',
                'description' => 'Qatar is a peninsular Arab country known for its futuristic skyscrapers, modern architecture, and cultural heritage. Doha\'s Corniche, the Museum of Islamic Art, and the Pearl-Qatar artificial island showcase the nation\'s blend of tradition and innovation.',
                'featured_destination' => false,
            ],
            [
                'name' => 'Oman',
                'code' => 'OM',
                'slug' => 'oman',
                'type' => 'country',
                'region' => 'Middle East',
                'description' => 'Oman offers pristine coastlines, dramatic mountains, and ancient forts. From the Sultan Qaboos Grand Mosque in Muscat to the frankincense trails of Salalah, Oman preserves authentic Arabian culture alongside modern development.',
                'featured_destination' => false,
            ],
            [
                'name' => 'Bahrain',
                'code' => 'BH',
                'slug' => 'bahrain',
                'type' => 'country',
                'region' => 'Middle East',
                'description' => 'Bahrain is an island nation known for its petroleum reserves, pearls, and the Bahrain International Circuit. The Manama skyline, Bahrain World Trade Center, and ancient Bahrain Fort reflect the country\'s rich heritage and modern ambitions.',
                'featured_destination' => false,
            ],
            [
                'name' => 'Kuwait',
                'code' => 'KW',
                'slug' => 'kuwait',
                'type' => 'country',
                'region' => 'Middle East',
                'description' => 'Kuwait City features the iconic Kuwait Towers, Grand Mosque, and Souq Al-Mubarakiya. Despite modern development, Kuwait preserves its maritime heritage and Bedouin traditions through museums and cultural festivals.',
                'featured_destination' => false,
            ],
            [
                'name' => 'Turkey',
                'code' => 'TR',
                'slug' => 'turkey',
                'type' => 'country',
                'region' => 'Middle East',
                'description' => 'Turkey bridges Europe and Asia with rich history spanning empires. From Istanbul\'s Hagia Sophia to Cappadocia\'s fairy chimneys and Pamukkale\'s travertine terraces, Turkey offers unparalleled cultural and natural diversity.',
                'featured_destination' => true,
            ],
            // EUROPE
            [
                'name' => 'United Kingdom',
                'code' => 'GB',
                'slug' => 'united-kingdom',
                'type' => 'country',
                'region' => 'Europe',
                'description' => 'The United Kingdom blends historic landmarks with modern culture. From London\'s Big Ben and Buckingham Palace to Edinburgh Castle and the Giant\'s Causeway, the UK offers centuries of history, vibrant cities, and stunning countryside.',
                'featured_destination' => true,
            ],
            [
                'name' => 'France',
                'code' => 'FR',
                'slug' => 'france',
                'type' => 'country',
                'region' => 'Europe',
                'description' => 'France captivates with Parisian elegance, Riviera glamour, and provincial charm. The Eiffel Tower, Louvre Museum, Palace of Versailles, and Provence\'s lavender fields exemplify French art de vivre and gastronomic excellence.',
                'featured_destination' => true,
            ],
            [
                'name' => 'Germany',
                'code' => 'DE',
                'slug' => 'germany',
                'type' => 'country',
                'region' => 'Europe',
                'description' => 'Germany offers fairy-tale castles, cutting-edge architecture, and world-class culture. From Berlin\'s Brandenburg Gate to Bavaria\'s Neuschwanstein Castle and the Romantic Road, Germany seamlessly blends tradition and innovation.',
                'featured_destination' => true,
            ],
            [
                'name' => 'Italy',
                'code' => 'IT',
                'slug' => 'italy',
                'type' => 'country',
                'region' => 'Europe',
                'description' => 'Italy is a treasure trove of art, history, and cuisine. Rome\'s Colosseum, Venice\'s canals, Florence\'s Renaissance art, and the Amalfi Coast\'s dramatic beauty make Italy one of the world\'s most beloved destinations.',
                'featured_destination' => true,
            ],
            [
                'name' => 'Spain',
                'code' => 'ES',
                'slug' => 'spain',
                'type' => 'country',
                'region' => 'Europe',
                'description' => 'Spain enchants with flamenco passion, Moorish architecture, and Mediterranean beaches. Barcelona\'s Sagrada Família, Madrid\'s Prado Museum, Andalusia\'s Alhambra, and the Balearic Islands showcase Spain\'s diverse regional cultures.',
                'featured_destination' => true,
            ],
            [
                'name' => 'Switzerland',
                'code' => 'CH',
                'slug' => 'switzerland',
                'type' => 'country',
                'region' => 'Europe',
                'description' => 'Switzerland boasts Alpine majesty, pristine lakes, and precision craftsmanship. The Matterhorn, Jungfraujoch, Lucerne, and Geneva offer breathtaking scenery, luxury watches, chocolate, and year-round outdoor adventures.',
                'featured_destination' => true,
            ],
            [
                'name' => 'Netherlands',
                'code' => 'NL',
                'slug' => 'netherlands',
                'type' => 'country',
                'region' => 'Europe',
                'description' => 'The Netherlands combines canal-ring cities, tulip fields, and windmill landscapes. Amsterdam\'s museums, Utrecht\'s medieval center, Keukenhof Gardens, and the Dutch masters reflect a nation of artistic and engineering ingenuity.',
                'featured_destination' => false,
            ],
            // ASIA
            [
                'name' => 'Japan',
                'code' => 'JP',
                'slug' => 'japan',
                'type' => 'country',
                'region' => 'Asia',
                'description' => 'Japan harmonizes ancient traditions with futuristic technology. Tokyo\'s neon streets, Kyoto\'s temples, Mount Fuji, cherry blossoms, and the art of tea ceremonies create a uniquely captivating cultural experience.',
                'featured_destination' => true,
            ],
            [
                'name' => 'Singapore',
                'code' => 'SG',
                'slug' => 'singapore',
                'type' => 'country',
                'region' => 'Asia',
                'description' => 'Singapore is a gleaming city-state where cultures converge. Gardens by the Bay, Marina Bay Sands, hawker centers, and colonial heritage reflect a multicultural society with world-class infrastructure and green urban design.',
                'featured_destination' => true,
            ],
            [
                'name' => 'Thailand',
                'code' => 'TH',
                'slug' => 'thailand',
                'type' => 'country',
                'region' => 'Asia',
                'description' => 'Thailand offers golden temples, tropical islands, and renowned hospitality. Bangkok\'s Grand Palace, Chiang Mai\'s ancient temples, Phuket\'s beaches, and Thai cuisine make Thailand the Land of Smiles.',
                'featured_destination' => true,
            ],
            [
                'name' => 'Malaysia',
                'code' => 'MY',
                'slug' => 'malaysia',
                'type' => 'country',
                'region' => 'Asia',
                'description' => 'Malaysia blends Malay, Chinese, Indian, and indigenous cultures. Kuala Lumpur\'s Petronas Towers, Penang\'s heritage streets, Borneo\'s rainforests, and Langkawi\'s islands offer diverse experiences.',
                'featured_destination' => false,
            ],
            [
                'name' => 'India',
                'code' => 'IN',
                'slug' => 'india',
                'type' => 'country',
                'region' => 'Asia',
                'description' => 'India dazzles with spiritual heritage, architectural marvels, and vibrant diversity. The Taj Mahal, Kerala backwaters, Rajasthan forts, Goa beaches, and Himalayan treks showcase India\'s incredible richness.',
                'featured_destination' => true,
            ],
            [
                'name' => 'China',
                'code' => 'CN',
                'slug' => 'china',
                'type' => 'country',
                'region' => 'Asia',
                'description' => 'China spans millennia of civilization and modern superpower status. The Great Wall, Forbidden City, Terracotta Army, Shanghai skyline, and Zhangjiajie\'s avatar mountains represent China\'s vast scale and ambition.',
                'featured_destination' => true,
            ],
        ];

        $countryLocationData = [
            'United Arab Emirates' => ['latitude' => '23.4241', 'longitude' => '53.8478', 'capital_city' => 'Abu Dhabi', 'population' => 9980000, 'currency' => 'AED', 'timezone' => 'GMT+4:00', 'language' => ['Arabic', 'English', 'Hindi'], 'local_cuisine' => ['Shawarma', 'Falafel', 'Machboos', 'Harees']],
            'Saudi Arabia' => ['latitude' => '23.8859', 'longitude' => '45.0792', 'capital_city' => 'Riyadh', 'population' => 36900000, 'currency' => 'SAR', 'timezone' => 'GMT+3:00', 'language' => ['Arabic', 'English'], 'local_cuisine' => ['Kabsa', 'Mutabbaq', 'Jareesh']],
            'Qatar' => ['latitude' => '25.3548', 'longitude' => '51.1839', 'capital_city' => 'Doha', 'population' => 2800000, 'currency' => 'QAR', 'timezone' => 'GMT+3:00', 'language' => ['Arabic', 'English'], 'local_cuisine' => ['Machboos', 'Harees', 'Luqaimat']],
            'Oman' => ['latitude' => '21.4735', 'longitude' => '55.9754', 'capital_city' => 'Muscat', 'population' => 5200000, 'currency' => 'OMR', 'timezone' => 'GMT+4:00', 'language' => ['Arabic', 'English'], 'local_cuisine' => ['Shuwa', 'Majboos', 'Harees']],
            'Bahrain' => ['latitude' => '26.0667', 'longitude' => '50.5577', 'capital_city' => 'Manama', 'population' => 1700000, 'currency' => 'BHD', 'timezone' => 'GMT+3:00', 'language' => ['Arabic', 'English'], 'local_cuisine' => ['Machboos', 'Muhammar', 'Balaleet']],
            'Kuwait' => ['latitude' => '29.3117', 'longitude' => '47.4818', 'capital_city' => 'Kuwait City', 'population' => 4300000, 'currency' => 'KWD', 'timezone' => 'GMT+3:00', 'language' => ['Arabic', 'English'], 'local_cuisine' => ['Machboos', 'Murdugayya', 'Harees']],
            'Turkey' => ['latitude' => '38.9637', 'longitude' => '35.2433', 'capital_city' => 'Ankara', 'population' => 85000000, 'currency' => 'TRY', 'timezone' => 'GMT+3:00', 'language' => ['Turkish', 'Kurdish', 'Arabic'], 'local_cuisine' => ['Kebab', 'Baklava', 'Turkish Delight', 'Lahmacun']],
            'United Kingdom' => ['latitude' => '55.3781', 'longitude' => '-3.4360', 'capital_city' => 'London', 'population' => 67300000, 'currency' => 'GBP', 'timezone' => 'GMT+0:00', 'language' => ['English'], 'local_cuisine' => ['Fish and Chips', 'Roast Beef', 'Full English Breakfast', 'Shepherd\'s Pie']],
            'France' => ['latitude' => '46.2276', 'longitude' => '2.2137', 'capital_city' => 'Paris', 'population' => 67500000, 'currency' => 'EUR', 'timezone' => 'GMT+1:00', 'language' => ['French'], 'local_cuisine' => ['Croissant', 'Baguette', 'Coq au Vin', 'Crème Brûlée']],
            'Germany' => ['latitude' => '51.1657', 'longitude' => '10.4515', 'capital_city' => 'Berlin', 'population' => 83200000, 'currency' => 'EUR', 'timezone' => 'GMT+1:00', 'language' => ['German'], 'local_cuisine' => ['Bratwurst', 'Sauerbraten', 'Pretzel', 'Black Forest Cake']],
            'Italy' => ['latitude' => '41.8719', 'longitude' => '12.5674', 'capital_city' => 'Rome', 'population' => 59500000, 'currency' => 'EUR', 'timezone' => 'GMT+1:00', 'language' => ['Italian'], 'local_cuisine' => ['Pizza', 'Pasta', 'Gelato', 'Tiramisu', 'Risotto']],
            'Spain' => ['latitude' => '40.4637', 'longitude' => '-3.7492', 'capital_city' => 'Madrid', 'population' => 47300000, 'currency' => 'EUR', 'timezone' => 'GMT+1:00', 'language' => ['Spanish'], 'local_cuisine' => ['Paella', 'Tapas', 'Gazpacho', 'Churros', 'Jamón Ibérico']],
            'Switzerland' => ['latitude' => '46.8182', 'longitude' => '8.2275', 'capital_city' => 'Bern', 'population' => 8700000, 'currency' => 'CHF', 'timezone' => 'GMT+1:00', 'language' => ['German', 'French', 'Italian', 'Romansh'], 'local_cuisine' => ['Cheese Fondue', 'Raclette', 'Swiss Chocolate', 'Rösti']],
            'Netherlands' => ['latitude' => '52.1326', 'longitude' => '5.2913', 'capital_city' => 'Amsterdam', 'population' => 17500000, 'currency' => 'EUR', 'timezone' => 'GMT+1:00', 'language' => ['Dutch'], 'local_cuisine' => ['Stroopwafel', 'Haring', 'Poffertjes', 'Dutch Cheese']],
            'Japan' => ['latitude' => '36.2048', 'longitude' => '138.2529', 'capital_city' => 'Tokyo', 'population' => 125000000, 'currency' => 'JPY', 'timezone' => 'GMT+9:00', 'language' => ['Japanese'], 'local_cuisine' => ['Sushi', 'Ramen', 'Tempura', 'Takoyaki', 'Matcha']],
            'Singapore' => ['latitude' => '1.3521', 'longitude' => '103.8198', 'capital_city' => 'Singapore', 'population' => 5900000, 'currency' => 'SGD', 'timezone' => 'GMT+8:00', 'language' => ['English', 'Mandarin', 'Malay', 'Tamil'], 'local_cuisine' => ['Hainanese Chicken Rice', 'Laksa', 'Chilli Crab', 'Satay']],
            'Thailand' => ['latitude' => '15.8700', 'longitude' => '100.9925', 'capital_city' => 'Bangkok', 'population' => 71600000, 'currency' => 'THB', 'timezone' => 'GMT+7:00', 'language' => ['Thai'], 'local_cuisine' => ['Pad Thai', 'Tom Yum Goong', 'Green Curry', 'Mango Sticky Rice']],
            'Malaysia' => ['latitude' => '4.2105', 'longitude' => '101.9758', 'capital_city' => 'Kuala Lumpur', 'population' => 33000000, 'currency' => 'MYR', 'timezone' => 'GMT+8:00', 'language' => ['Malay', 'English', 'Chinese', 'Tamil'], 'local_cuisine' => ['Nasi Lemak', 'Satay', 'Laksa', 'Roti Canai']],
            'India' => ['latitude' => '20.5937', 'longitude' => '78.9629', 'capital_city' => 'New Delhi', 'population' => 1400000000, 'currency' => 'INR', 'timezone' => 'GMT+5:30', 'language' => ['Hindi', 'English', 'Bengali', 'Tamil'], 'local_cuisine' => ['Butter Chicken', 'Biryani', 'Dosa', 'Samosa', 'Naan']],
            'China' => ['latitude' => '35.8617', 'longitude' => '104.1954', 'capital_city' => 'Beijing', 'population' => 1400000000, 'currency' => 'CNY', 'timezone' => 'GMT+8:00', 'language' => ['Mandarin', 'Cantonese'], 'local_cuisine' => ['Dim Sum', 'Peking Duck', 'Hot Pot', 'Dumplings', 'Kung Pao Chicken']],
        ];

        foreach ($countries as $data) {
            // Extract region name for attachment later
            $regionName = $data['region'] ?? null;
            unset($data['region']);

            $country = Country::create($data);

            // Set 2027 timestamps
            $country->created_at = $this->random2027Date();
            $country->updated_at = $this->random2027Date();
            $country->save();

            // Location Details - use country-specific data
            $locationData = $countryLocationData[$country->name];
            CountryLocationDetail::create([
                'country_id' => $country->id,
                'latitude' => $locationData['latitude'],
                'longitude' => $locationData['longitude'],
                'capital_city' => $locationData['capital_city'],
                'population' => $locationData['population'],
                'currency' => $locationData['currency'],
                'timezone' => $locationData['timezone'],
                'language' => $locationData['language'],
                'local_cuisine' => $locationData['local_cuisine'],
            ]);

            // Travel Info with Lorem Ipsum
            CountryTravelInfo::create([
                'country_id' => $country->id,
                'airport' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. International Airport with modern facilities.',
                'public_transportation' => ['Metro', 'Buses', 'Trains', 'Taxis'],
                'taxi_available' => true,
                'rental_cars_available' => true,
                'hotels' => true,
                'hostels' => true,
                'apartments' => true,
                'resorts' => true,
                'visa_requirements' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Visa requirements vary by nationality.',
                'best_time_to_visit' => 'Lorem ipsum dolor sit amet. Best months are spring and autumn for pleasant weather.',
                'travel_tips' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Carry local currency and respect local customs.',
                'safety_information' => 'Lorem ipsum dolor sit amet. Generally safe for tourists with normal precautions.',
            ]);

            // Seasons
            $seasons = [
                [
                    'name' => 'Winter',
                    'months' => ['December', 'January', 'February'],
                    'weather' => 'Cold',
                    'activities' => ['Skiing', 'Trekking'],
                ],
                [
                    'name' => 'Summer',
                    'months' => ['April', 'May', 'June'],
                    'weather' => 'Hot',
                    'activities' => ['Beach trips'],
                ],
            ];
            foreach ($seasons as $season) {
                CountrySeason::create(array_merge($season, [
                    'country_id' => $country->id,
                ]));
            }

            // Events with 2027 dates
            $events = [
                [
                    'name' => 'New Year Festival',
                    'type' => ['Festival', 'Holiday'],
                    'date' => $this->random2027DateOnly(),
                    'location' => 'Capital City',
                    'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. New Year celebrations with fireworks and festivities.',
                ],
                [
                    'name' => 'National Day',
                    'type' => ['Festival', 'Holiday'],
                    'date' => $this->random2027DateOnly(),
                    'location' => 'Major City',
                    'description' => 'Lorem ipsum dolor sit amet. National holiday celebrations with parades and cultural events.',
                ],
            ];
            foreach ($events as $event) {
                CountryEvent::create(array_merge($event, [
                    'country_id' => $country->id,
                ]));
            }

            // Additional Info
            $additionalInfos = [
                [
                    'title' => 'Famous Tourist Attractions',
                    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Monuments, Museums, National Parks, and historic landmarks showcase the rich cultural heritage.',
                ],
                [
                    'title' => 'Popular Food',
                    'content' => 'Lorem ipsum dolor sit amet. Street food, Traditional Dishes, and local delicacies offer a culinary journey through authentic flavors.',
                ],
                [
                    'title' => 'Culture',
                    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Music, Dance, Art, and traditions reflect the vibrant cultural tapestry.',
                ],
            ];
            foreach ($additionalInfos as $info) {
                CountryAdditionalInfo::create(array_merge($info, [
                    'country_id' => $country->id,
                ]));
            }

            // FAQs
            $faqs = [
                [
                    'question' => 'Do I need a visa to visit?',
                    'answer' => 'Lorem ipsum dolor sit amet. Visa requirements depend on your nationality and length of stay. Check with local embassy for current regulations.',
                ],
                [
                    'question' => 'What is the local currency?',
                    'answer' => 'Lorem ipsum dolor sit amet. The official currency is available at banks and exchange offices. Credit cards are widely accepted in cities.',
                ],
            ];
            $qNum = 1;
            foreach ($faqs as $faq) {
                CountryFaq::create([
                    'country_id' => $country->id,
                    'question_number' => $qNum++,
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                ]);
            }

            // SEO
            CountrySeo::create([
                'country_id' => $country->id,
                'meta_title' => 'Visit ' . $country->name . ' - Travel Guide & Tourism',
                'meta_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Explore ' . $country->name . ' with our comprehensive travel guide.',
                'keywords' => $country->name . ', Travel, Tourism, Vacation, Holiday, Trip',
                'og_image_url' => 'https://example.com/' . $country->slug . '.jpg',
                'canonical_url' => 'https://example.com/' . $country->slug,
                'schema_type' => 'TravelDestination',
                'schema_data' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'TravelDestination',
                    'name' => $country->name,
                    'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                    'image' => 'https://example.com/' . $country->slug . '.jpg',
                ],
            ]);

            // Attach region via relationship
            if ($regionName) {
                $region = Region::where('name', $regionName)->first();
                if ($region) {
                    $country->regions()->attach($region->id);
                } else {
                    $this->command->warn("Region '{$regionName}' not found for country '{$country->name}'");
                }
            }
        }
    }

    /**
     * Generate a random datetime in 2027
     */
    private function random2027Date(): string
    {
        $start = strtotime('2027-01-01 00:00:00');
        $end = strtotime('2027-12-31 23:59:59');
        $random = mt_rand($start, $end);
        return date('Y-m-d H:i:s', $random);
    }

    /**
     * Generate a random date in 2027 (no time)
     */
    private function random2027DateOnly(): string
    {
        $start = strtotime('2027-01-01');
        $end = strtotime('2027-12-31');
        $random = mt_rand($start, $end);
        return date('Y-m-d', $random);
    }
}
