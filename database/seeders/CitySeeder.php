<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\CityAdditionalInfo;
use App\Models\CityEvent;
use App\Models\CityFaq;
use App\Models\CityLocationDetail;
use App\Models\CityMediaGallery;
use App\Models\CitySeason;
use App\Models\CitySeo;
use App\Models\CityTravelInfo;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    /**
     * Generate a random date/datetime in 2027
     *
     * @param  bool  $dateOnly  If true, return date only (Y-m-d), otherwise datetime (Y-m-d H:i:s)
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
        // Delete all existing cities and related data
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        CityLocationDetail::query()->delete();
        CityTravelInfo::query()->delete();
        CitySeason::query()->delete();
        CityEvent::query()->delete();
        CityAdditionalInfo::query()->delete();
        CityFaq::query()->delete();
        CitySeo::query()->delete();
        CityMediaGallery::query()->delete();
        City::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        echo "All existing cities and related data deleted.\n";

        // Cities array - 58 cities across 20 states
        $cities = [
            // Île-de-France, France
            ['state_name' => 'Île-de-France', 'name' => 'Paris', 'code' => 'PAR', 'slug' => 'paris', 'description' => 'The City of Light captivates with its iconic Eiffel Tower, world-class museums like the Louvre, charming cafés, and romantic Seine River cruises. Experience the epitome of European culture, fashion, and gastronomy.'],
            ['state_name' => 'Île-de-France', 'name' => 'Versailles', 'code' => 'VER', 'slug' => 'versailles', 'description' => 'Home to the magnificent Palace of Versailles, this opulent royal residence showcases French grandeur with its Hall of Mirrors, sprawling gardens, and rich historical significance as a symbol of absolute monarchy.'],
            ['state_name' => 'Île-de-France', 'name' => 'Boulogne-Billancourt', 'code' => 'BOU', 'slug' => 'boulogne-billancourt', 'description' => 'A vibrant Parisian suburb known for its beautiful parks, the Renault museum, and excellent shopping. Experience authentic Parisian life away from the tourist crowds while remaining minutes from the capital.'],

            // Provence-Alpes-Côte d'Azur, France
            ['state_name' => 'Provence-Alpes-Côte d\'Azur', 'name' => 'Marseille', 'code' => 'MRS', 'slug' => 'marseille', 'description' => 'France\'s oldest city and Mediterranean port, Marseille offers a vibrant mix of cultures, stunning Calanques, fresh seafood, and the iconic Notre-Dame de la Garde basilica overlooking the azure Mediterranean.'],
            ['state_name' => 'Provence-Alpes-Côte d\'Azur', 'name' => 'Nice', 'code' => 'NCE', 'slug' => 'nice', 'description' => 'The glittering jewel of the French Riviera, Nice delights with its pebbly beaches, colorful Old Town, famous Promenade des Anglais, and year-round sunshine perfect for seaside relaxation and exploration.'],
            ['state_name' => 'Provence-Alpes-Côte d\'Azur', 'name' => 'Cannes', 'code' => 'CEQ', 'slug' => 'cannes', 'description' => 'Famous for its international film festival, glamorous La Croisette boulevard, luxury shopping, and beautiful Mediterranean beaches. Experience the height of Riviera sophistication and celebrity glamour.'],

            // Lombardy, Italy
            ['state_name' => 'Lombardy', 'name' => 'Milan', 'code' => 'MIL', 'slug' => 'milan', 'description' => 'Italy\'s fashion and design capital, Milan blends historic grandeur with modern sophistication. Visit the magnificent Duomo, world-class galleries, enjoy exquisite shopping, and experience authentic Italian aperitivo culture.'],
            ['state_name' => 'Lombardy', 'name' => 'Bergamo', 'code' => 'BGY', 'slug' => 'bergamo', 'description' => 'A stunning medieval city divided into historic upper town (Città Alta) and modern lower town, featuring beautiful Venetian walls, romantic piazzas, and panoramic views of the Lombardy plains.'],
            ['state_name' => 'Lombardy', 'name' => 'Brescia', 'code' => 'BSC', 'slug' => 'brescia', 'description' => 'Rich in Roman and medieval history, Brescia offers magnificent squares, ancient ruins, excellent wines, and serves as gateway to beautiful Lake Garda and the Italian Alps.'],

            // Tuscany, Italy
            ['state_name' => 'Tuscany', 'name' => 'Florence', 'code' => 'FLR', 'slug' => 'florence', 'description' => 'The cradle of Renaissance art and architecture, Florence mesmerizes with Michelangelo\'s David, the Duomo\'s magnificent dome, Uffizi Gallery masterpieces, and world-renowned Tuscan cuisine and wine.'],
            ['state_name' => 'Tuscany', 'name' => 'Pisa', 'code' => 'PSA', 'slug' => 'pisa', 'description' => 'Home to the iconic Leaning Tower and stunning Piazza dei Miracoli, Pisa combines Romanesque architecture, medieval history, and vibrant university life in the heart of Tuscany.'],
            ['state_name' => 'Tuscany', 'name' => 'Siena', 'code' => 'SIE', 'slug' => 'siena', 'description' => 'A beautifully preserved medieval city with an incredible Gothic cathedral, the famous Piazza del Campo, and the exhilarating Palio horse race. Experience authentic Tuscan culture and cuisine.'],

            // Catalonia, Spain
            ['state_name' => 'Catalonia', 'name' => 'Barcelona', 'code' => 'BCN', 'slug' => 'barcelona', 'description' => 'A vibrant metropolis blending Gaudí\'s surreal architecture, beautiful beaches, delicious tapas culture, and passionate Catalan spirit. Explore Sagrada Família, Park Güell, and the charming Gothic Quarter.'],
            ['state_name' => 'Catalonia', 'name' => 'Girona', 'code' => 'GRO', 'slug' => 'girona', 'description' => 'A beautifully preserved medieval city with a stunning cathedral, colorful houses along the Onyar River, Jewish quarter, and excellent cuisine. A perfect blend of history and modern Catalan culture.'],
            ['state_name' => 'Catalonia', 'name' => 'Tarragona', 'code' => 'TAR', 'slug' => 'tarragona', 'description' => 'Ancient Roman capital featuring impressive ruins including an amphitheater by the sea, aqueduct, and circus. Enjoy beautiful beaches, delicious Mediterranean cuisine, and rich history.'],

            // Andalusia, Spain
            ['state_name' => 'Andalusia', 'name' => 'Seville', 'code' => 'SVQ', 'slug' => 'seville', 'description' => 'The passionate heart of Andalusia, Seville enchants with its breathtaking Alcázar palace, massive Gothic cathedral, passionate flamenco, fragrant orange trees, and authentic tapas culture in sunny plazas.'],
            ['state_name' => 'Andalusia', 'name' => 'Malaga', 'code' => 'AGP', 'slug' => 'malaga', 'description' => 'Birthplace of Picasso, this sunny coastal city offers beautiful beaches, impressive Moorish castle, world-class museums, excellent seafood, and serves as gateway to the Costa del Sol.'],
            ['state_name' => 'Andalusia', 'name' => 'Granada', 'code' => 'GRX', 'slug' => 'granada', 'description' => 'Home to the magnificent Alhambra palace complex, Granada offers stunning Moorish architecture, atmospheric Albayzín quarter, free tapas culture, and breathtaking views of the Sierra Nevada mountains.'],

            // Istanbul, Turkey
            ['state_name' => 'Istanbul', 'name' => 'Istanbul', 'code' => 'IST', 'slug' => 'istanbul', 'description' => 'Where East meets West, Istanbul straddles two continents with stunning Hagia Sophia, magnificent Blue Mosque, vibrant Grand Bazaar, and unforgettable Bosphorus cruises. A mesmerizing blend of cultures and civilizations.'],
            ['state_name' => 'Istanbul', 'name' => 'Kadıköy', 'code' => 'KAD', 'slug' => 'kadikoy', 'description' => 'The vibrant Asian side of Istanbul, famous for its lively food scene, trendy cafés, traditional markets, and authentic local atmosphere away from tourist crowds. Experience real Istanbul life.'],
            ['state_name' => 'Istanbul', 'name' => 'Bakırköy', 'code' => 'BKK', 'slug' => 'bakirkoy', 'description' => 'A charming coastal district on the European side, featuring beautiful seafront promenades, excellent shopping centers, traditional Turkish baths, and easy access to Istanbul\'s historic attractions.'],

            // Antalya, Turkey
            ['state_name' => 'Antalya', 'name' => 'Antalya', 'code' => 'AYT', 'slug' => 'antalya', 'description' => 'The pearl of the Turkish Riviera, Antalya offers stunning beaches, ancient Roman ruins, magnificent waterfalls, charming old town (Kaleiçi), and serves as perfect gateway to exploring Turkey\'s beautiful Mediterranean coast.'],
            ['state_name' => 'Antalya', 'name' => 'Alanya', 'code' => 'AYL', 'slug' => 'alanya', 'description' => 'A popular resort town featuring the impressive Red Tower, spectacular castle overlooking turquoise waters, beautiful beaches, exciting nightlife, and excellent conditions for water sports and relaxation.'],
            ['state_name' => 'Antalya', 'name' => 'Belek', 'code' => 'BEK', 'slug' => 'belek', 'description' => 'A luxury tourism haven with world-class golf courses, pristine Mediterranean beaches, all-inclusive resorts, and ancient Roman ruins. Perfect for upscale relaxation and outdoor activities.'],

            // Bangkok, Thailand
            ['state_name' => 'Bangkok', 'name' => 'Bangkok', 'code' => 'BKK', 'slug' => 'bangkok', 'description' => 'Thailand\'s vibrant capital dazzles with ornate temples, floating markets, incredible street food, world-class shopping, and the magnificent Grand Palace. A sensory feast that never sleeps.'],
            ['state_name' => 'Bangkok', 'name' => 'Nonthaburi', 'code' => 'NTR', 'slug' => 'nonthaburi', 'description' => 'A bustling satellite city offering beautiful parks along the Chao Phraya River, fresh markets, excellent shopping malls, and a more relaxed alternative while maintaining easy access to Bangkok\'s attractions.'],
            ['state_name' => 'Bangkok', 'name' => 'Samut Prakan', 'code' => 'SPK', 'slug' => 'samut-prakan', 'description' => 'Home to the magnificent Ancient City and the famous Erawan Museum, this province offers cultural treasures, beautiful riverfront temples, and the impressive Crocodile Farm, perfect for day trips from Bangkok.'],

            // Phuket, Thailand
            ['state_name' => 'Phuket', 'name' => 'Phuket Town', 'code' => 'HKT', 'slug' => 'phuket-town', 'description' => 'The charming capital of Phuket island features beautiful Sino-Portuguese architecture, vibrant night markets, excellent restaurants, and authentic Thai culture away from the beach crowds.'],
            ['state_name' => 'Phuket', 'name' => 'Patong', 'code' => 'PAT', 'slug' => 'patong', 'description' => 'Phuket\'s most famous beach resort, Patong offers golden sands, crystal-clear waters, exciting nightlife, world-class dining, and endless water sports activities. The heart of island entertainment.'],
            ['state_name' => 'Phuket', 'name' => 'Kata', 'code' => 'KATA', 'slug' => 'kata', 'description' => 'A beautiful beach area perfect for relaxation and surfing, featuring pristine white sand, clear turquoise waters, excellent restaurants, and a more laid-back atmosphere than neighboring Patong.'],

            // Tokyo, Japan
            ['state_name' => 'Tokyo', 'name' => 'Tokyo', 'code' => 'TYO', 'slug' => 'tokyo', 'description' => 'A mesmerizing metropolis blending ultra-modern technology with ancient traditions, Tokyo offers incredible food, shopping, pop culture, historic temples, and the iconic Shibuya Crossing experience.'],
            ['state_name' => 'Tokyo', 'name' => 'Yokohama', 'code' => 'YOK', 'slug' => 'yokohama', 'description' => 'Japan\'s second-largest city features a beautiful waterfront, the largest Chinatown, impressive Cup Noodles Museum, historic Yamashita Park, and stunning views of Tokyo Bay.'],
            ['state_name' => 'Tokyo', 'name' => 'Kawasaki', 'code' => 'KAW', 'slug' => 'kawasaki', 'description' => 'An industrial city with a hidden charm, featuring the beautiful Kawasaki Daishi temple, traditional festivals, excellent museums, and the unique Japanese Open-Air Folk House Museum.'],

            // Osaka, Japan
            ['state_name' => 'Osaka', 'name' => 'Osaka', 'code' => 'OSA', 'slug' => 'osaka', 'description' => 'Japan\'s kitchen and entertainment capital, Osaka delights with incredible street food, historic Osaka Castle, vibrant Dotonbori district, Universal Studios Japan, and warm, friendly locals.'],
            ['state_name' => 'Osaka', 'name' => 'Kyoto', 'code' => 'KYO', 'slug' => 'kyoto', 'description' => 'Japan\'s cultural heart, Kyoto offers over 1,000 temples, stunning gardens, traditional tea ceremonies, geisha districts, and the famous Arashiyama bamboo grove. A timeless journey into authentic Japan.'],
            ['state_name' => 'Osaka', 'name' => 'Kobe', 'code' => 'KOB', 'slug' => 'kobe', 'description' => 'A sophisticated port city famous for its world-renowned beef, beautiful Chinatown, hot springs, stunning harbor views, and the atmospheric Kitano district with historic Western-style houses.'],

            // Dubai, UAE
            ['state_name' => 'Dubai', 'name' => 'Dubai', 'code' => 'DXB', 'slug' => 'dubai', 'description' => 'A futuristic oasis rising from the desert, Dubai dazzles with the world\'s tallest building, luxury shopping, man-made islands, incredible desert safaris, and unparalleled hospitality and entertainment.'],
            ['state_name' => 'Dubai', 'name' => 'Sharjah', 'code' => 'SHJ', 'slug' => 'sharjah', 'description' => 'The cultural capital of the UAE, Sharjah offers beautiful mosques, impressive museums, traditional souks, art galleries, and a more authentic glimpse into Emirati culture and heritage.'],
            ['state_name' => 'Dubai', 'name' => 'Ajman', 'code' => 'AJM', 'slug' => 'ajman', 'description' => 'A peaceful emirate featuring beautiful beaches, the impressive Ajman Museum, traditional dhow-building yards, luxury resorts, and a more relaxed pace compared to its glitzy neighbor Dubai.'],

            // Abu Dhabi, UAE
            ['state_name' => 'Abu Dhabi', 'name' => 'Abu Dhabi', 'code' => 'AUH', 'slug' => 'abu-dhabi', 'description' => 'The majestic capital of the UAE, Abu Dhabi impresses with the Sheikh Zayed Mosque, Ferrari World, pristine Corniche waterfront, Louvre Abu Dhabi museum, and authentic Arabian hospitality.'],
            ['state_name' => 'Abu Dhabi', 'name' => 'Al Ain', 'code' => 'AAN', 'slug' => 'al-ain', 'description' => 'The Garden City of the UAE, Al Ain offers beautiful oases, historic forts, the impressive Sheikh Zayed Palace Museum, Jebel Hafit mountain, and a glimpse into traditional Bedouin life and culture.'],
            ['state_name' => 'Abu Dhabi', 'name' => 'Ruwais', 'code' => 'RUW', 'slug' => 'ruwais', 'description' => 'An industrial town located in the Western Region of Abu Dhabi, serving as a gateway to the stunning Sir Bani Yas Island nature reserve and beautiful desert landscapes of the Al Dhafra region.'],

            // England, UK
            ['state_name' => 'England', 'name' => 'London', 'code' => 'LON', 'slug' => 'london', 'description' => 'A world-class metropolis blending royal tradition with modern innovation, London offers iconic landmarks like Big Ben and Tower Bridge, world-class museums, West End theater, and diverse neighborhoods.'],
            ['state_name' => 'England', 'name' => 'Manchester', 'code' => 'MAN', 'slug' => 'manchester', 'description' => 'A dynamic northern city famous for its industrial heritage, legendary music scene, world-class football, vibrant shopping, and the impressive MediaCityUK complex.'],
            ['state_name' => 'England', 'name' => 'Birmingham', 'code' => 'BHX', 'slug' => 'birmingham', 'description' => 'The UK\'s second-largest city offers world-class shopping at Bullring, impressive canalside developments, Cadbury World, diverse culinary scene, and rich industrial heritage.'],

            // Scotland, UK
            ['state_name' => 'Scotland', 'name' => 'Edinburgh', 'code' => 'EDI', 'slug' => 'edinburgh', 'description' => 'Scotland\'s magnificent capital features the iconic Edinburgh Castle, atmospheric Old Town, elegant Georgian New Town, the world-famous Royal Mile, and unforgettable summer festivals.'],
            ['state_name' => 'Scotland', 'name' => 'Glasgow', 'code' => 'GLA', 'slug' => 'glasgow', 'description' => 'Scotland\'s largest city offers stunning Victorian architecture, world-class museums, vibrant music scene, excellent shopping, and friendly locals known for their warmth and humor.'],
            ['state_name' => 'Scotland', 'name' => 'Aberdeen', 'code' => 'ABZ', 'slug' => 'aberdeen', 'description' => 'The Granite City features stunning gray architecture, beautiful sandy beaches, rich oil industry heritage, whisky distilleries, and serves as gateway to the spectacular Scottish Highlands.'],

            // Maharashtra, India
            ['state_name' => 'Maharashtra', 'name' => 'Mumbai', 'code' => 'BOM', 'slug' => 'mumbai', 'description' => 'India\'s bustling financial capital, Mumbai offers Bollywood glamour, historic Gateway of India, vibrant street food, beautiful Marine Drive, and the fascinating contrast of wealth and colonial history.'],
            ['state_name' => 'Maharashtra', 'name' => 'Pune', 'code' => 'PNQ', 'slug' => 'pune', 'description' => 'The cultural capital of Maharashtra, Pune offers historic forts, spiritual sites, pleasant climate, thriving IT industry, excellent educational institutions, and gateway to the beautiful hill stations of the Western Ghats.'],
            ['state_name' => 'Maharashtra', 'name' => 'Nagpur', 'code' => 'NAG', 'slug' => 'nagpur', 'description' => 'The geographic center of India, Nagpur offers beautiful temples, tiger reserves nearby, delicious oranges, rich cultural heritage, and serves as important trade hub for central India.'],

            // Kerala, India
            ['state_name' => 'Kerala', 'name' => 'Kochi', 'code' => 'COK', 'slug' => 'kochi', 'description' => 'The Queen of the Arabian Sea, Kochi features historic Fort Kochi with Chinese fishing nets, Dutch palace, Jewish synagogue, beautiful backwaters, and a unique blend of Portuguese, Dutch, and British influences.'],
            ['state_name' => 'Kerala', 'name' => 'Thiruvananthapuram', 'code' => 'TRV', 'slug' => 'thiruvananthapuram', 'description' => 'Kerala\'s capital offers the magnificent Padmanabhaswamy Temple, beautiful Kovalam beaches, historic palaces, rich cultural traditions, and gateway to the stunning backwaters of southern Kerala.'],
            ['state_name' => 'Kerala', 'name' => 'Munnar', 'code' => 'MUN', 'slug' => 'munnar', 'description' => 'A breathtaking hill station in the Western Ghats, Munnar offers sprawling tea plantations, misty mountains, exotic wildlife, beautiful waterfalls, and romantic getaways in nature\'s paradise.'],

            // Central, Singapore
            ['state_name' => 'Central', 'name' => 'Singapore City', 'code' => 'SIN', 'slug' => 'singapore-city', 'description' => 'A dazzling city-state where East meets West, Singapore offers Marina Bay Sands, Gardens by the Bay, incredible hawker food, multicultural neighborhoods, and the perfect blend of nature and urban sophistication.'],
            ['state_name' => 'Central', 'name' => 'Novena', 'code' => 'NOV', 'slug' => 'novena', 'description' => 'A vibrant residential and commercial district featuring excellent shopping malls, medical centers, beautiful churches, diverse dining options, and convenient connectivity to Singapore\'s main attractions.'],
            ['state_name' => 'Central', 'name' => 'Toa Payoh', 'code' => 'TPY', 'slug' => 'toa-payoh', 'description' => 'A charming neighborhood featuring traditional public housing, vibrant hawker center, beautiful parks, sports facilities, and an authentic glimpse into Singaporean everyday life and local culture.'],

            // East, Singapore
            ['state_name' => 'East', 'name' => 'Changi', 'code' => 'CGI', 'slug' => 'changi', 'description' => 'Home to the world\'s best airport, Changi offers beautiful Changi Point, beach clubs, coastal walks, World War II heritage sites, and the spectacular Jewel complex with the world\'s tallest indoor waterfall.'],
            ['state_name' => 'East', 'name' => 'Bedok', 'code' => 'BDK', 'slug' => 'bedok', 'description' => 'A vibrant residential town featuring beautiful Bedok Reservoir, excellent hawker centers, modern shopping malls, sports facilities, and a diverse community showcasing Singapore\'s multicultural harmony.'],
            ['state_name' => 'East', 'name' => 'Pasir Ris', 'code' => 'PRC', 'slug' => 'pasir-ris', 'description' => 'A charming coastal town offering beautiful beaches, Downtown East theme park, White Sands shopping, chalets for staycations, and excellent parks perfect for family recreation and nature activities.'],
        ];

        // Get state IDs from state names
        $validCities = [];
        foreach ($cities as $cityData) {
            $stateName = $cityData['state_name'];
            $state = \App\Models\State::where('name', $stateName)->first();
            if (! $state) {
                echo "WARNING: State '{$stateName}' not found for city '{$cityData['name']}' - skipping\n";

                continue;
            }
            $cityData['state_id'] = $state->id;
            unset($cityData['state_name']);
            $validCities[] = $cityData;
        }
        $cities = $validCities;

        // Create cities with all related data
        $createdCount = 0;
        foreach ($cities as $cityData) {
            try {
                // Create City
                $city = City::create([
                    'name' => $cityData['name'],
                    'code' => $cityData['code'],
                    'slug' => $cityData['slug'],
                    'state_id' => $cityData['state_id'],
                    'description' => $cityData['description'],
                    'type' => 'city',
                    'featured_destination' => false,
                ]);

                echo "Created city: {$city->name}\n";

                // Create CityLocationDetail
                CityLocationDetail::create([
                    'city_id' => $city->id,
                    'latitude' => $this->getRandomLatitude(),
                    'longitude' => $this->getRandomLongitude(),
                    'population' => rand(500000, 15000000),
                    'currency' => $this->getCurrencyForCity($city->name),
                    'timezone' => $this->getTimezoneForCity($city->name),
                    'language' => $this->getLanguagesForCity($city->name),
                    'local_cuisine' => $this->getLocalCuisineForCity($city->name),
                ]);

                // Create CityTravelInfo
                CityTravelInfo::create([
                    'city_id' => $city->id,
                    'airport' => $this->getAirportForCity($city->name),
                    'public_transportation' => $this->getPublicTransportation(),
                    'taxi_available' => true,
                    'rental_cars_available' => true,
                    'hotels' => true,
                    'hostels' => true,
                    'apartments' => true,
                    'resorts' => $this->hasResorts($city->name),
                    'visa_requirements' => $this->getVisaRequirements($city->name),
                    'best_time_to_visit' => $this->getBestTimeToVisit($city->name),
                    'travel_tips' => $this->getTravelTips($city->name),
                    'safety_information' => $this->getSafetyInfo($city->name),
                ]);

                // Create CitySeasons (2 seasons per city)
                foreach ($this->getSeasons($city->name) as $season) {
                    CitySeason::create([
                        'city_id' => $city->id,
                        'name' => $season['name'],
                        'months' => $season['months'],
                        'weather' => $season['weather'],
                        'activities' => $season['activities'],
                    ]);
                }

                // Create CityEvent (1 event per city)
                CityEvent::create([
                    'city_id' => $city->id,
                    'name' => $this->getEventName($city->name),
                    'type' => $this->getEventTypes(),
                    'date' => $this->random2027Date(true),
                    'location' => $city->name.' City Center',
                    'description' => $this->getEventDescription($city->name),
                ]);

                // Create CityAdditionalInfo (1 item per city)
                CityAdditionalInfo::create([
                    'city_id' => $city->id,
                    'title' => "Why Visit {$city->name}",
                    'content' => $this->getAdditionalInfoContent($city->name),
                ]);

                // Create CityFaqs (2 FAQs per city)
                for ($i = 1; $i <= 2; $i++) {
                    CityFaq::create([
                        'city_id' => $city->id,
                        'question_number' => $i,
                        'question' => $this->getFaqQuestion($city->name, $i),
                        'answer' => $this->getFaqAnswer($city->name, $i),
                    ]);
                }

                // Create CitySeo
                CitySeo::create([
                    'city_id' => $city->id,
                    'meta_title' => "Visit {$city->name} - Travel Guide, Things to Do & Attractions",
                    'meta_description' => "Discover {$city->name} with our comprehensive travel guide. Find top attractions, activities, hotels, restaurants, and travel tips for an unforgettable {$city->name} experience.",
                    'keywords' => "{$city->name}, visit {$city->name}, {$city->name} travel, {$city->name} tourism, things to do in {$city->name}, {$city->name} attractions",
                    'og_image_url' => url("storage/cities/{$city->slug}/og-image.jpg"),
                    'canonical_url' => url("destinations/{$city->slug}"),
                    'schema_type' => 'City',
                    'schema_data' => $this->getSchemaData($city->name, $city->slug),
                ]);

                $createdCount++;
            } catch (\Exception $e) {
                echo "ERROR creating city {$cityData['name']}: ".$e->getMessage()."\n";
            }
        }

        echo "Successfully created {$createdCount} cities with all related data.\n";
    }

    /**
     * Helper methods for generating city-specific data
     */
    private function getRandomLatitude(): float
    {
        return rand(-4000, 6000) / 100; // Roughly between -40 and 60
    }

    private function getRandomLongitude(): float
    {
        return rand(-18000, 18000) / 100; // Roughly between -180 and 180
    }

    private function getCurrencyForCity(string $cityName): string
    {
        $currencies = [
            'Paris' => 'EUR', 'Versailles' => 'EUR', 'Boulogne-Billancourt' => 'EUR',
            'Marseille' => 'EUR', 'Nice' => 'EUR', 'Cannes' => 'EUR',
            'Milan' => 'EUR', 'Bergamo' => 'EUR', 'Brescia' => 'EUR',
            'Florence' => 'EUR', 'Pisa' => 'EUR', 'Siena' => 'EUR',
            'Barcelona' => 'EUR', 'Girona' => 'EUR', 'Tarragona' => 'EUR',
            'Seville' => 'EUR', 'Malaga' => 'EUR', 'Granada' => 'EUR',
            'Istanbul' => 'TRY', 'Kadıköy' => 'TRY', 'Bakırköy' => 'TRY',
            'Antalya' => 'TRY', 'Alanya' => 'TRY', 'Belek' => 'TRY',
            'Bangkok' => 'THB', 'Nonthaburi' => 'THB', 'Samut Prakan' => 'THB',
            'Phuket Town' => 'THB', 'Patong' => 'THB', 'Kata' => 'THB',
            'Tokyo' => 'JPY', 'Yokohama' => 'JPY', 'Kawasaki' => 'JPY',
            'Osaka' => 'JPY', 'Kyoto' => 'JPY', 'Kobe' => 'JPY',
            'Dubai' => 'AED', 'Sharjah' => 'AED', 'Ajman' => 'AED',
            'Abu Dhabi' => 'AED', 'Al Ain' => 'AED', 'Ruwais' => 'AED',
            'London' => 'GBP', 'Manchester' => 'GBP', 'Birmingham' => 'GBP',
            'Edinburgh' => 'GBP', 'Glasgow' => 'GBP', 'Aberdeen' => 'GBP',
            'Mumbai' => 'INR', 'Pune' => 'INR', 'Nagpur' => 'INR',
            'Kochi' => 'INR', 'Thiruvananthapuram' => 'INR', 'Munnar' => 'INR',
            'Singapore City' => 'SGD', 'Novena' => 'SGD', 'Toa Payoh' => 'SGD',
            'Changi' => 'SGD', 'Bedok' => 'SGD', 'Pasir Ris' => 'SGD',
        ];

        return $currencies[$cityName] ?? 'USD';
    }

    private function getTimezoneForCity(string $cityName): string
    {
        $timezones = [
            'Paris' => 'Europe/Paris', 'Versailles' => 'Europe/Paris', 'Boulogne-Billancourt' => 'Europe/Paris',
            'Marseille' => 'Europe/Paris', 'Nice' => 'Europe/Paris', 'Cannes' => 'Europe/Paris',
            'Milan' => 'Europe/Rome', 'Bergamo' => 'Europe/Rome', 'Brescia' => 'Europe/Rome',
            'Florence' => 'Europe/Rome', 'Pisa' => 'Europe/Rome', 'Siena' => 'Europe/Rome',
            'Barcelona' => 'Europe/Madrid', 'Girona' => 'Europe/Madrid', 'Tarragona' => 'Europe/Madrid',
            'Seville' => 'Europe/Madrid', 'Malaga' => 'Europe/Madrid', 'Granada' => 'Europe/Madrid',
            'Istanbul' => 'Europe/Istanbul', 'Kadıköy' => 'Europe/Istanbul', 'Bakırköy' => 'Europe/Istanbul',
            'Antalya' => 'Europe/Istanbul', 'Alanya' => 'Europe/Istanbul', 'Belek' => 'Europe/Istanbul',
            'Bangkok' => 'Asia/Bangkok', 'Nonthaburi' => 'Asia/Bangkok', 'Samut Prakan' => 'Asia/Bangkok',
            'Phuket Town' => 'Asia/Bangkok', 'Patong' => 'Asia/Bangkok', 'Kata' => 'Asia/Bangkok',
            'Tokyo' => 'Asia/Tokyo', 'Yokohama' => 'Asia/Tokyo', 'Kawasaki' => 'Asia/Tokyo',
            'Osaka' => 'Asia/Tokyo', 'Kyoto' => 'Asia/Tokyo', 'Kobe' => 'Asia/Tokyo',
            'Dubai' => 'Asia/Dubai', 'Sharjah' => 'Asia/Dubai', 'Ajman' => 'Asia/Dubai',
            'Abu Dhabi' => 'Asia/Dubai', 'Al Ain' => 'Asia/Dubai', 'Ruwais' => 'Asia/Dubai',
            'London' => 'Europe/London', 'Manchester' => 'Europe/London', 'Birmingham' => 'Europe/London',
            'Edinburgh' => 'Europe/London', 'Glasgow' => 'Europe/London', 'Aberdeen' => 'Europe/London',
            'Mumbai' => 'Asia/Kolkata', 'Pune' => 'Asia/Kolkata', 'Nagpur' => 'Asia/Kolkata',
            'Kochi' => 'Asia/Kolkata', 'Thiruvananthapuram' => 'Asia/Kolkata', 'Munnar' => 'Asia/Kolkata',
            'Singapore City' => 'Asia/Singapore', 'Novena' => 'Asia/Singapore', 'Toa Payoh' => 'Asia/Singapore',
            'Changi' => 'Asia/Singapore', 'Bedok' => 'Asia/Singapore', 'Pasir Ris' => 'Asia/Singapore',
        ];

        return $timezones[$cityName] ?? 'UTC';
    }

    private function getLanguagesForCity(string $cityName): array
    {
        $languages = [
            'Paris' => ['French', 'English'], 'Versailles' => ['French', 'English'], 'Boulogne-Billancourt' => ['French', 'English'],
            'Marseille' => ['French', 'English'], 'Nice' => ['French', 'English'], 'Cannes' => ['French', 'English'],
            'Milan' => ['Italian', 'English'], 'Bergamo' => ['Italian', 'English'], 'Brescia' => ['Italian', 'English'],
            'Florence' => ['Italian', 'English'], 'Pisa' => ['Italian', 'English'], 'Siena' => ['Italian', 'English'],
            'Barcelona' => ['Spanish', 'Catalan', 'English'], 'Girona' => ['Spanish', 'Catalan', 'English'], 'Tarragona' => ['Spanish', 'Catalan', 'English'],
            'Seville' => ['Spanish', 'English'], 'Malaga' => ['Spanish', 'English'], 'Granada' => ['Spanish', 'English'],
            'Istanbul' => ['Turkish', 'English'], 'Kadıköy' => ['Turkish', 'English'], 'Bakırköy' => ['Turkish', 'English'],
            'Antalya' => ['Turkish', 'English'], 'Alanya' => ['Turkish', 'English'], 'Belek' => ['Turkish', 'English'],
            'Bangkok' => ['Thai', 'English'], 'Nonthaburi' => ['Thai', 'English'], 'Samut Prakan' => ['Thai', 'English'],
            'Phuket Town' => ['Thai', 'English'], 'Patong' => ['Thai', 'English'], 'Kata' => ['Thai', 'English'],
            'Tokyo' => ['Japanese', 'English'], 'Yokohama' => ['Japanese', 'English'], 'Kawasaki' => ['Japanese', 'English'],
            'Osaka' => ['Japanese', 'English'], 'Kyoto' => ['Japanese', 'English'], 'Kobe' => ['Japanese', 'English'],
            'Dubai' => ['Arabic', 'English'], 'Sharjah' => ['Arabic', 'English'], 'Ajman' => ['Arabic', 'English'],
            'Abu Dhabi' => ['Arabic', 'English'], 'Al Ain' => ['Arabic', 'English'], 'Ruwais' => ['Arabic', 'English'],
            'London' => ['English'], 'Manchester' => ['English'], 'Birmingham' => ['English'],
            'Edinburgh' => ['English'], 'Glasgow' => ['English'], 'Aberdeen' => ['English'],
            'Mumbai' => ['Hindi', 'Marathi', 'English'], 'Pune' => ['Marathi', 'Hindi', 'English'], 'Nagpur' => ['Marathi', 'Hindi', 'English'],
            'Kochi' => ['Malayalam', 'English'], 'Thiruvananthapuram' => ['Malayalam', 'English'], 'Munnar' => ['Malayalam', 'English'],
            'Singapore City' => ['English', 'Mandarin', 'Malay', 'Tamil'], 'Novena' => ['English', 'Mandarin', 'Malay', 'Tamil'],
            'Toa Payoh' => ['English', 'Mandarin', 'Malay', 'Tamil'], 'Changi' => ['English', 'Mandarin', 'Malay', 'Tamil'],
            'Bedok' => ['English', 'Mandarin', 'Malay', 'Tamil'], 'Pasir Ris' => ['English', 'Mandarin', 'Malay', 'Tamil'],
        ];

        return $languages[$cityName] ?? ['English'];
    }

    private function getLocalCuisineForCity(string $cityName): array
    {
        $cuisines = [
            'Paris' => ['Croissant', 'Baguette', 'Escargot', 'French Onion Soup', 'Macarons'],
            'Versailles' => ['Croissant', 'Baguette', 'French Pastries'],
            'Boulogne-Billancourt' => ['French Cuisine', 'International Cuisine'],
            'Marseille' => ['Bouillabaisse', 'Panisse', 'Navette', 'Tapenade'],
            'Nice' => ['Salade Niçoise', 'Ratatouille', 'Socca', 'Pissaladière'],
            'Cannes' => ['Mediterranean Cuisine', 'Seafood', 'French Riviera Dishes'],
            'Milan' => ['Risotto alla Milanese', 'Ossobuco', 'Panettone', 'Saffron Rice'],
            'Bergamo' => ['Polenta', 'Casoncelli', 'Local Cheese Dishes'],
            'Brescia' => ['Italian Cuisine', 'Lake Fish', 'Wine'],
            'Florence' => ['Bistecca alla Fiorentina', 'Ribollita', 'Gelato', 'Chianti Wine'],
            'Pisa' => ['Cecina', 'Pisan Cuisine', 'Tuscan Dishes'],
            'Siena' => ['Panforte', 'Ricciarelli', 'Pici Pasta', 'Tuscan Wine'],
            'Barcelona' => ['Paella', 'Tapas', 'Cava', 'Catalan Cream'],
            'Girona' => ['Catalan Cuisine', 'Seafood', 'Tapas'],
            'Tarragona' => ['Seafood', 'Roman-inspired Dishes', 'Catalan Cuisine'],
            'Seville' => ['Gazpacho', 'Tapas', 'Flamenco-style Dishes', 'Sherry Wine'],
            'Malaga' => ['Pescaito Frito', 'Gazpacho', 'Andalusian Cuisine'],
            'Granada' => ['Tapas', 'Free Tapas Culture', 'Moorish-inspired Dishes'],
            'Istanbul' => ['Kebab', 'Baklava', 'Turkish Delight', 'Turkish Tea'],
            'Kadıköy' => ['Street Food', 'Traditional Turkish', 'Seafood'],
            'Bakırköy' => ['Turkish Cuisine', 'Seafood', 'Baklava'],
            'Antalya' => ['Turkish Cuisine', 'Mediterranean Dishes', 'Pide'],
            'Alanya' => ['Turkish Food', 'Seafood', 'International Cuisine'],
            'Belek' => ['Turkish Cuisine', 'International Dishes', 'Gourmet Food'],
            'Bangkok' => ['Pad Thai', 'Tom Yum Goong', 'Green Curry', 'Mango Sticky Rice'],
            'Nonthaburi' => ['Thai Street Food', 'Local Dishes'],
            'Samut Prakan' => ['Thai Seafood', 'Local Specialties'],
            'Phuket Town' => ['Pad Thai', 'Tom Yum', 'Fresh Seafood'],
            'Patong' => ['Thai Street Food', 'Seafood', 'International Cuisine'],
            'Kata' => ['Thai Food', 'Seafood', 'Beachside Dining'],
            'Tokyo' => ['Sushi', 'Ramen', 'Tempura', 'Wagyu Beef'],
            'Yokohama' => ['Ramen', 'Chinatown Dishes', 'Sushi'],
            'Kawasaki' => ['Japanese Cuisine', 'Local Specialties'],
            'Osaka' => ['Takoyaki', 'Okonomiyaki', 'Kushikatsu'],
            'Kyoto' => ['Kaiseki', 'Matcha Sweets', 'Tofu Dishes', 'Yudofu'],
            'Kobe' => ['Kobe Beef', 'Hot Springs Cuisine'],
            'Dubai' => ['Middle Eastern Cuisine', 'International Food', 'Dates'],
            'Sharjah' => ['Arabic Cuisine', 'Traditional Dishes'],
            'Ajman' => ['Seafood', 'Arabic Cuisine'],
            'Abu Dhabi' => ['Middle Eastern Cuisine', 'Machboos', 'Dates'],
            'Al Ain' => ['Traditional Emirati Food', 'Dates', 'Camel Milk'],
            'Ruwais' => ['Arabic Cuisine', 'Seafood'],
            'London' => ['Fish and Chips', 'Sunday Roast', 'Afternoon Tea', 'Full English Breakfast'],
            'Manchester' => ['British Cuisine', 'Curry', 'Local Pies'],
            'Birmingham' => ['Balti Curry', 'British Cuisine'],
            'Edinburgh' => ['Haggis', 'Scotch Pie', 'Shortbread', 'Whisky'],
            'Glasgow' => ['Scottish Cuisine', 'Haggis', 'Local Specialties'],
            'Aberdeen' => ['Scottish Food', 'Seafood', 'Aberdeen Angus Beef'],
            'Mumbai' => ['Vada Pav', 'Biryani', 'Street Food', 'Thali'],
            'Pune' => ['Maharashtrian Thali', 'Misal Pav', 'Poha'],
            'Nagpur' => ['Saoji Cuisine', 'Oranges', 'Maharashtrian Food'],
            'Kochi' => ['Seafood', 'Appam', 'Dosa', 'Kerala Curry'],
            'Thiruvananthapuram' => ['Kerala Sadya', 'Seafood', 'Banana Chips'],
            'Munnar' => ['Kerala Tea', 'Local Cuisine', 'Spices'],
            'Singapore City' => ['Hainanese Chicken Rice', 'Laksa', 'Chilli Crab', 'Kaya Toast'],
            'Novena' => ['Singapore Hawker Food', 'Local Dishes'],
            'Toa Payoh' => ['Hawker Centre Food', 'Local Singaporean'],
            'Changi' => ['Singapore Cuisine', 'International Food'],
            'Bedok' => ['Hawker Food', 'Local Dishes'],
            'Pasir Ris' => ['Singaporean Food', 'Seafood'],
        ];

        return $cuisines[$cityName] ?? ['Local Cuisine', 'International Food'];
    }

    private function getAirportForCity(string $cityName): string
    {
        $airports = [
            'Paris' => 'Charles de Gaulle Airport (CDG)',
            'Versailles' => 'Charles de Gaulle Airport (CDG)',
            'Boulogne-Billancourt' => 'Charles de Gaulle Airport (CDG)',
            'Marseille' => 'Marseille Provence Airport (MRS)',
            'Nice' => 'Nice Côte d\'Azur Airport (NCE)',
            'Cannes' => 'Nice Côte d\'Azur Airport (NCE)',
            'Milan' => 'Malpensa Airport (MXP)',
            'Bergamo' => 'Orio al Serio Airport (BGY)',
            'Brescia' => 'Verona Airport (VRN)',
            'Florence' => 'Florence Airport (FLR)',
            'Pisa' => 'Pisa International Airport (PSA)',
            'Siena' => 'Florence Airport (FLR)',
            'Barcelona' => 'Barcelona-El Prat Airport (BCN)',
            'Girona' => 'Girona-Costa Brava Airport (GRO)',
            'Tarragona' => 'Reus Airport (REU)',
            'Seville' => 'Seville Airport (SVQ)',
            'Malaga' => 'Málaga-Costa del Sol Airport (AGP)',
            'Granada' => 'Federico García Lorca Airport (GRX)',
            'Istanbul' => 'Istanbul Airport (IST)',
            'Kadıköy' => 'Istanbul Airport (IST)',
            'Bakırköy' => 'Istanbul Airport (IST)',
            'Antalya' => 'Antalya Airport (AYT)',
            'Alanya' => 'Antalya Airport (AYT)',
            'Belek' => 'Antalya Airport (AYT)',
            'Bangkok' => 'Suvarnabhumi Airport (BKK)',
            'Nonthaburi' => 'Don Mueang Airport (DMK)',
            'Samut Prakan' => 'Suvarnabhumi Airport (BKK)',
            'Phuket Town' => 'Phuket International Airport (HKT)',
            'Patong' => 'Phuket International Airport (HKT)',
            'Kata' => 'Phuket International Airport (HKT)',
            'Tokyo' => 'Haneda Airport (HND) / Narita Airport (NRT)',
            'Yokohama' => 'Haneda Airport (HND)',
            'Kawasaki' => 'Haneda Airport (HND)',
            'Osaka' => 'Kansai International Airport (KIX)',
            'Kyoto' => 'Kansai International Airport (KIX) / Itami Airport (ITM)',
            'Kobe' => 'Kobe Airport (UKB)',
            'Dubai' => 'Dubai International Airport (DXB)',
            'Sharjah' => 'Sharjah International Airport (SHJ)',
            'Ajman' => 'Dubai International Airport (DXB)',
            'Abu Dhabi' => 'Abu Dhabi International Airport (AUH)',
            'Al Ain' => 'Al Ain International Airport (AAN)',
            'Ruwais' => 'Abu Dhabi International Airport (AUH)',
            'London' => 'Heathrow Airport (LHR)',
            'Manchester' => 'Manchester Airport (MAN)',
            'Birmingham' => 'Birmingham Airport (BHX)',
            'Edinburgh' => 'Edinburgh Airport (EDI)',
            'Glasgow' => 'Glasgow Airport (GLA)',
            'Aberdeen' => 'Aberdeen Airport (ABZ)',
            'Mumbai' => 'Chhatrapati Shivaji Maharaj International Airport (BOM)',
            'Pune' => 'Pune Airport (PNQ)',
            'Nagpur' => 'Dr. Babasaheb Ambedkar International Airport (NAG)',
            'Kochi' => 'Cochin International Airport (COK)',
            'Thiruvananthapuram' => 'Trivandrum International Airport (TRV)',
            'Munnar' => 'Cochin International Airport (COK)',
            'Singapore City' => 'Changi Airport (SIN)',
            'Novena' => 'Changi Airport (SIN)',
            'Toa Payoh' => 'Changi Airport (SIN)',
            'Changi' => 'Changi Airport (SIN)',
            'Bedok' => 'Changi Airport (SIN)',
            'Pasir Ris' => 'Changi Airport (SIN)',
        ];

        return $airports[$cityName] ?? 'International Airport';
    }

    private function getPublicTransportation(): array
    {
        return ['Metro', 'Bus', 'Taxi', 'Rideshare'];
    }

    private function hasResorts(string $cityName): bool
    {
        // Cities that typically have luxury resorts
        $resortCities = [
            'Antalya', 'Alanya', 'Belek', 'Phuket Town', 'Patong', 'Kata',
            'Dubai', 'Sharjah', 'Ajman', 'Abu Dhabi', 'Al Ain', 'Nice', 'Cannes',
            'Bali', // for future reference
        ];

        return in_array($cityName, $resortCities);
    }

    private function getVisaRequirements(string $cityName): string
    {
        return 'Visa requirements depend on your nationality. Check with the embassy for entry requirements.';
    }

    private function getBestTimeToVisit(string $cityName): string
    {
        $times = [
            'Paris' => 'April to June, September to October',
            'Nice' => 'May to September',
            'Barcelona' => 'April to June, September to October',
            'Seville' => 'March to May, September to November',
            'Istanbul' => 'April to May, September to November',
            'Antalya' => 'April to May, September to November',
            'Bangkok' => 'November to February',
            'Phuket Town' => 'November to April',
            'Tokyo' => 'March to April, October to November',
            'Osaka' => 'March to May, October to November',
            'Dubai' => 'November to March',
            'Abu Dhabi' => 'November to March',
            'London' => 'May to September',
            'Edinburgh' => 'May to September',
            'Mumbai' => 'November to February',
            'Kochi' => 'September to March',
            'Singapore City' => 'February to April',
        ];

        return $times[$cityName] ?? 'Year-round destination';
    }

    private function getTravelTips(string $cityName): string
    {
        return 'Book accommodation in advance, especially during peak season. Learn a few local phrases for a better experience.';
    }

    private function getSafetyInfo(string $cityName): string
    {
        return "{$cityName} is generally safe for tourists. Take normal precautions and be aware of your surroundings.";
    }

    private function getSeasons(string $cityName): array
    {
        $allSeasons = [
            ['name' => 'Summer', 'months' => ['June', 'July', 'August'], 'weather' => 'Hot and sunny', 'activities' => ['Beach visits', 'Outdoor festivals', 'Sightseeing']],
            ['name' => 'Winter', 'months' => ['December', 'January', 'February'], 'weather' => 'Cool with occasional rain', 'activities' => ['Museum visits', 'Indoor markets', 'Holiday festivals']],
            ['name' => 'Spring', 'months' => ['March', 'April', 'May'], 'weather' => 'Mild and pleasant', 'activities' => ['Parks and gardens', 'Outdoor dining', 'Cultural events']],
            ['name' => 'Autumn', 'months' => ['September', 'October', 'November'], 'weather' => 'Crisp and comfortable', 'activities' => ['Fall foliage', 'Wine tours', 'City walks']],
        ];

        // Return 2 seasons based on city
        if (in_array($cityName, ['Bangkok', 'Phuket Town', 'Mumbai', 'Kochi', 'Singapore City', 'Patong', 'Kata', 'Alanya', 'Belek'])) {
            return [$allSeasons[1], $allSeasons[0]]; // Dry and Wet seasons (Winter, Summer)
        }

        return [$allSeasons[2], $allSeasons[0]]; // Spring and Summer
    }

    private function getEventName(string $cityName): string
    {
        $events = [
            'Paris' => 'Paris Fashion Week',
            'Nice' => 'Carnival of Nice',
            'Barcelona' => 'La Mercè Festival',
            'Seville' => 'Feria de Abril',
            'Istanbul' => 'Istanbul Music Festival',
            'Antalya' => 'Antalya Film Festival',
            'Bangkok' => 'Songkran Festival',
            'Phuket Town' => 'Phuket Vegetarian Festival',
            'Tokyo' => 'Cherry Blossom Festival',
            'Osaka' => 'Osaka Summer Festival',
            'Dubai' => 'Dubai Shopping Festival',
            'Abu Dhabi' => 'Abu Dhabi Grand Prix',
            'London' => 'Notting Hill Carnival',
            'Edinburgh' => 'Edinburgh Festival Fringe',
            'Mumbai' => 'Ganesh Chaturthi',
            'Kochi' => 'Onam Festival',
            'Singapore City' => 'Singapore Grand Prix',
        ];

        return $events[$cityName] ?? "{$cityName} Cultural Festival";
    }

    private function getEventTypes(): array
    {
        return ['Cultural', 'Festival', 'Entertainment'];
    }

    private function getEventDescription(string $cityName): string
    {
        return "Annual celebration showcasing the rich culture, traditions, and vibrant spirit of {$cityName}. Features live performances, local cuisine, and exciting activities for all ages.";
    }

    private function getAdditionalInfoContent(string $cityName): string
    {
        return "{$cityName} offers a perfect blend of history, culture, and modern attractions. From iconic landmarks to hidden gems, there's something for every traveler. Explore local markets, sample authentic cuisine, and immerse yourself in the unique atmosphere that makes {$cityName} a must-visit destination.";
    }

    private function getFaqQuestion(string $cityName, int $number): string
    {
        $questions = [
            1 => "What is the best time of year to visit {$cityName}?",
            2 => "How many days should I spend in {$cityName}?",
        ];

        return $questions[$number] ?? "Common question about {$cityName}";
    }

    private function getFaqAnswer(string $cityName, int $number): string
    {
        $answers = [
            1 => "The best time to visit {$cityName} depends on your preferences. Spring and fall offer pleasant weather and fewer crowds. Summer is peak tourist season with warm weather, while winter can be quieter with cooler temperatures.",
            2 => "We recommend spending at least 3-4 days in {$cityName} to explore the main attractions. For a more comprehensive experience including day trips and deeper cultural immersion, plan for a week or more.",
        ];

        return $answers[$number] ?? "Answer about {$cityName}";
    }

    private function getSchemaData(string $cityName, string $slug): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'City',
            'name' => $cityName,
            'url' => url("destinations/{$slug}"),
            'description' => "Travel guide and information about {$cityName}",
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $cityName,
            ],
        ];
    }
}
