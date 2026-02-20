<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Itinerary;
use App\Models\ItineraryLocation;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryActivity;
use App\Models\ItineraryTransfer;
use App\Models\ItineraryBasePricing;
use App\Models\ItineraryPriceVariation;
use App\Models\ItineraryBlackoutDate;
use App\Models\ItineraryInclusionExclusion;
use App\Models\ItineraryMediaGallery;
use App\Models\ItinerarySeo;
use App\Models\ItineraryCategory;
use App\Models\ItineraryAttribute;
use App\Models\ItineraryTag;
use App\Models\ItineraryAvailability;
use App\Models\Addon;
use App\Models\ItineraryAddon;

class ItinerarySeeder extends Seeder
{
    public function run()
    {

        // Foreign key checks ko disable karo
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Tables ko truncate ki jagah delete karo
        Itinerary::query()->delete();
        ItinerarySchedule::query()->delete();
        ItineraryLocation::query()->delete();
        ItineraryActivity::query()->delete();
        ItineraryTransfer::query()->delete();
        ItineraryBasePricing::query()->delete();
        ItineraryPriceVariation::query()->delete();
        ItineraryBlackoutDate::query()->delete();
        ItineraryInclusionExclusion::query()->delete();
        ItineraryMediaGallery::query()->delete();
        ItinerarySeo::query()->delete();
        ItineraryCategory::query()->delete();
        ItineraryAttribute::query()->delete();
        ItineraryTag::query()->delete();

        // Foreign key checks ko wapas enable karo
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $itineraries = [
            [
                'name' => 'Luxury Safari in Kenya',
                'slug' => 'luxury-safari-in-kenya',
                'description' => 'Explore the luxury of Kenyas wild safari.',
                'featured_itinerary' => true,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Adventure Trek in Nepal',
                'slug' => 'adventure-trek-in-nepal',
                'description' => 'Experience the thrill of the Himalayas.',
                'featured_itinerary' => true,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Beach Vacation in Maldives',
                'slug' => 'beach-vacation-in-maldives',
                'description' => 'Relax on the white sands of Maldives.',
                'featured_itinerary' => false,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Cultural Tour in Japan',
                'slug' => 'cultural-tour-in-japan',
                'description' => 'Explore the rich culture of Japan.',
                'featured_itinerary' => true,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Cultural Tour in Kangra',
                'slug' => 'cultural-tour-in-kangra',
                'description' => 'Explore the rich culture of Japan.',
                'featured_itinerary' => true,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Cultural Tour in Lama Temple',
                'slug' => 'cultural-tour-in-lama-temple',
                'description' => 'Explore the rich culture of Japan.',
                'featured_itinerary' => true,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Cultural Tour in Dharamshala',
                'slug' => 'cultural-tour-in-dharamshala',
                'description' => 'Explore the rich culture of Japan.',
                'featured_itinerary' => true,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Cultural Tour in Rehan',
                'slug' => 'cultural-tour-in-rehan',
                'description' => 'Explore the rich culture of Japan.',
                'featured_itinerary' => true,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Market Tour in Greece',
                'slug' => 'market-tour-in-greece',
                'description' => 'Experience the beauty and culture of Greece.',
                'featured_itinerary' => false,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Beach Tour in Costa Rica',
                'slug' => 'beach-tour-in-costa-rica',
                'description' => 'Experience the beauty and culture of Costa Rica.',
                'featured_itinerary' => false,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Park Tour in Portugal',
                'slug' => 'park-tour-in-portugal',
                'description' => 'Experience the beauty and culture of Portugal.',
                'featured_itinerary' => false,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Village Tour in Iceland',
                'slug' => 'village-tour-in-iceland',
                'description' => 'Experience the beauty and culture of Iceland.',
                'featured_itinerary' => false,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Camp Tour in Cuba',
                'slug' => 'camp-tour-in-cuba',
                'description' => 'Experience the beauty and culture of Cuba.',
                'featured_itinerary' => true,
                'private_itinerary' => true,
            ],
            [
                'name' => 'River Tour in Sri Lanka',
                'slug' => 'river-tour-in-sri-lanka',
                'description' => 'Experience the beauty and culture of Sri Lanka.',
                'featured_itinerary' => true,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Museum Tour in Argentina',
                'slug' => 'museum-tour-in-argentina',
                'description' => 'Experience the beauty and culture of Argentina.',
                'featured_itinerary' => false,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Church Tour in Canada',
                'slug' => 'church-tour-in-canada',
                'description' => 'Experience the beauty and culture of Canada.',
                'featured_itinerary' => false,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Hill Tour in Himalayas',
                'slug' => 'hill-tour-in-himalayas',
                'description' => 'Experience the beauty and culture of Himalayas.',
                'featured_itinerary' => false,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Villa Tour in Bhutan',
                'slug' => 'villa-tour-in-bhutan',
                'description' => 'Experience the beauty and culture of Bhutan.',
                'featured_itinerary' => true,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Tower Tour in Germany',
                'slug' => 'tower-tour-in-germany',
                'description' => 'Experience the beauty and culture of Germany.',
                'featured_itinerary' => true,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Forest Tour in Brazil',
                'slug' => 'forest-tour-in-brazil',
                'description' => 'Experience the beauty and culture of Brazil.',
                'featured_itinerary' => false,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Lake Tour in Morocco',
                'slug' => 'lake-tour-in-morocco',
                'description' => 'Experience the beauty and culture of Morocco.',
                'featured_itinerary' => false,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Valley Tour in Peru',
                'slug' => 'valley-tour-in-peru',
                'description' => 'Experience the beauty and culture of Peru.',
                'featured_itinerary' => false,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Garden Tour in Austria',
                'slug' => 'garden-tour-in-austria',
                'description' => 'Experience the beauty and culture of Austria.',
                'featured_itinerary' => true,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Beach Tour in Bali',
                'slug' => 'beach-tour-in-bali',
                'description' => 'Experience the beauty and culture of Bali.',
                'featured_itinerary' => true,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Island Tour in Turkey',
                'slug' => 'island-tour-in-turkey',
                'description' => 'Experience the beauty and culture of Turkey.',
                'featured_itinerary' => false,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Field Tour in South Africa',
                'slug' => 'field-tour-in-south-africa',
                'description' => 'Experience the beauty and culture of South Africa.',
                'featured_itinerary' => true,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Temple Tour in Vietnam',
                'slug' => 'temple-tour-in-vietnam',
                'description' => 'Experience the beauty and culture of Vietnam.',
                'featured_itinerary' => false,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Ruins Tour in Egypt',
                'slug' => 'ruins-tour-in-egypt',
                'description' => 'Experience the beauty and culture of Egypt.',
                'featured_itinerary' => false,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Festival Tour in Cambodia',
                'slug' => 'festival-tour-in-cambodia',
                'description' => 'Experience the beauty and culture of Cambodia.',
                'featured_itinerary' => true,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Historic Tour in Tuscany',
                'slug' => 'historic-tour-in-tuscany',
                'description' => 'Experience the beauty and culture of Tuscany.',
                'featured_itinerary' => false,
                'private_itinerary' => true,
            ],
            [
                'name' => 'Sightseeing Tour in Spain',
                'slug' => 'sightseeing-tour-in-spain',
                'description' => 'Experience the beauty and culture of Spain.',
                'featured_itinerary' => true,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Bridge Tour in Machu Picchu',
                'slug' => 'bridge-tour-in-machu-picchu',
                'description' => 'Experience the beauty and culture of Machu Picchu.',
                'featured_itinerary' => false,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Adventure Tour in Dubai',
                'slug' => 'adventure-tour-in-dubai',
                'description' => 'Experience the beauty and culture of Dubai.',
                'featured_itinerary' => true,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Museum Tour in Norway',
                'slug' => 'museum-tour-in-norway',
                'description' => 'Experience the beauty and culture of Norway.',
                'featured_itinerary' => true,
                'private_itinerary' => false,
            ],
            [
                'name' => 'Pilgrimage Tour in China',
                'slug' => 'pilgrimage-tour-in-china',
                'description' => 'Experience the beauty and culture of China.',
                'featured_itinerary' => false,
                'private_itinerary' => false,
            ],
        ];

        foreach ($itineraries as $itineraryData) {
            $itinerary = Itinerary::create($itineraryData);

            ItineraryLocation::create([
                'itinerary_id' => $itinerary->id,
                'city_id'      => rand(1, 4),
            ]);

            for ($day = 1; $day <= 3; $day++) {
                $schedule = ItinerarySchedule::create([
                    'itinerary_id' => $itinerary->id,
                    'day'          => $day,
                ]);

                ItineraryActivity::create([
                    'schedule_id' => $schedule->id,
                    'activity_id' => rand(1, 8),
                    'start_time'  => '09:00:00',
                    'end_time'    => '11:00:00',
                    'notes'       => 'Sample activity note',
                    'price'       => 100.00,
                    'included'    => true,
                ]);

                ItineraryTransfer::create([
                    'schedule_id'      => $schedule->id,
                    'transfer_id'      => rand(1, 4),
                    'start_time'       => '12:00:00',
                    'end_time'         => '14:00:00',
                    'notes'            => 'Sample transfer note',
                    'price'            => 50.00,
                    'included'         => true,
                    'pickup_location'  => 'Airport',
                    'dropoff_location' => 'Hotel',
                    'pax'              => 2,
                ]);
            }

            $basePricing = ItineraryBasePricing::create([
                'itinerary_id' => $itinerary->id,
                'currency'     => 'USD',
                'availability' => 'Available',
                'start_date'   => now(),
                'end_date'     => now()->addMonth(),
            ]);

            ItineraryPriceVariation::create([
                'base_pricing_id' => $basePricing->id,
                'name'            => 'Standard Package',
                'regular_price'   => 1000.00,
                'sale_price'      => 800.00,
                'max_guests'      => 4,
                'description'     => 'Standard package with discount',
            ]);

            ItineraryBlackoutDate::create([
                'base_pricing_id' => $basePricing->id,
                'date'            => now()->addDays(7),
                'reason'          => 'Holiday season',
            ]);

            ItineraryInclusionExclusion::create([
                'itinerary_id' => $itinerary->id,
                'type'         => 'meal',
                'title'        => 'Breakfast included',
                'description'  => 'Breakfast included in the package',
                'included'     => true,
            ]);

            ItineraryMediaGallery::create([
                'itinerary_id' => $itinerary->id,
                'media_id'     => rand(1, 5),
            ]);

            ItinerarySeo::create([
                'itinerary_id'     => $itinerary->id,
                'meta_title'       => 'Sample Itinerary',
                'meta_description' => 'This is a sample itinerary for SEO testing.',
                'keywords'         => 'itinerary, travel, sample',
                'og_image_url'     => 'https://example.com/sample-og.jpg',
                'canonical_url'    => 'https://example.com/sample',
                'schema_type'      => 'Travel',
                'schema_data'      => json_encode([
                    'type' => 'Travel',
                    'name' => $itinerary->name,
                ]),
            ]);

            ItineraryCategory::create([
                'itinerary_id' => $itinerary->id,
                'category_id'  => 1,
            ]);

            // ItineraryAttribute::create([
            //     'itinerary_id'    => $itinerary->id,
            //     'attribute_id'    => 1,
            //     'attribute_value' => '1 Hour'
            // ]);

            $attributeValues = [
                1 => ['1 Hour', '2 Hours', 'Half Day', 'Full Day'],
                2 => ['Easy', 'Medium', 'Hard'],
                3 => ['1-5', '6-10', '11-20', '20+'],
            ];
            
            $usedAttributeIds = [];
            
            $randomAttributeIds = array_rand($attributeValues, rand(2, 3)); // 2 ya 3 attributes assign kare
            
            // Ensure it's array
            $randomAttributeIds = is_array($randomAttributeIds) ? $randomAttributeIds : [$randomAttributeIds];
            
            foreach ($randomAttributeIds as $attributeId) {
                $valueOptions = $attributeValues[$attributeId];
                $attributeValue = $valueOptions[array_rand($valueOptions)];
            
                ItineraryAttribute::create([
                    'itinerary_id' => $itinerary->id,
                    'attribute_id' => $attributeId,
                    'attribute_value' => $attributeValue
                ]);
            }

            ItineraryTag::create([
                'itinerary_id' => $itinerary->id,
                'tag_id'       => 1,
            ]);

            ItineraryAvailability::create([
                'itinerary_id'             => $itinerary->id,
                'date_based_itinerary'     => $dateBased = fake()->boolean,
                'start_date'               => $dateBased ? fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d') : null,
                'end_date'                 => $dateBased ? fake()->dateTimeBetween('+2 month', '+6 month')->format('Y-m-d') : null,
                'quantity_based_itinerary' => $quantityBased = fake()->boolean,
                'max_quantity'             => $quantityBased ? fake()->numberBetween(1, 100) : null,
            ]);

            $addonIds = Addon::where('type', 'itinerary')
                ->where('active_status', true)   // ✅ sirf active addons
                ->inRandomOrder()
                ->limit(rand(2, 4))              // 2 se 4 addons random select
                ->pluck('id');

            foreach ($addonIds as $addonId) {
                ItineraryAddon::create([
                    'itinerary_id' => $itinerary->id,   // ✅ correct foreign key
                    'addon_id'     => $addonId,
                ]);
            }
        }
    }

}
