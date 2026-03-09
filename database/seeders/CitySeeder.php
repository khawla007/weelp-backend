<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\City;
use App\Models\CityMediaGallery;
use App\Models\CityLocationDetail;
use App\Models\CityTravelInfo;
use App\Models\CitySeason;
use App\Models\CityEvent;
use App\Models\CityAdditionalInfo;
use App\Models\CityFaq;
use App\Models\CitySeo;

class CitySeeder extends Seeder
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
        // Delete all existing cities and related data
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        City::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        echo "All existing cities deleted.\n";

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

        echo "Added " . count($cities) . " cities.\n";
    }
}
