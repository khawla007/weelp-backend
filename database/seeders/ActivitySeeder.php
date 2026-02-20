<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityAttribute;
use App\Models\ActivityTag;
use App\Models\ActivityLocation;
use App\Models\Attribute;
use App\Models\ActivityPricing;
use App\Models\ActivitySeasonalPricing;
use App\Models\ActivityGroupDiscount;
use App\Models\ActivityEarlyBirdDiscount;
use App\Models\ActivityLastMinuteDiscount;
use App\Models\ActivityPromoCode;
use App\Models\ActivityMediaGallery;
use App\Models\ActivityAvailability;
use App\Models\Addon;
use App\Models\ActivityAddon;

class ActivitySeeder extends Seeder {
    public function run() {
        $activities = [
            [
                'name' => 'Desert Safari Adventure',
                'slug' => 'desert-safari-adventure',
                'description' => 'Experience the thrill of a desert safari with dune bashing and camel rides.',
                'short_description' => 'Desert safari with dune bashing and camel rides.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Sky Diving Experience',
                'slug' => 'sky-diving-experience',
                'description' => 'Jump from a plane at 13,000 feet and enjoy the ultimate adrenaline rush.',
                'short_description' => 'Sky diving at 13,000 feet.',
                'featured_activity' => false,
            ],
            [
                'name' => 'Scuba Diving Tour',
                'slug' => 'scuba-diving-tour',
                'description' => 'Explore the underwater world with our professional scuba diving instructors.',
                'short_description' => 'Scuba diving with professional guides.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Scuba Diving',
                'slug' => 'scuba-diving',
                'description' => 'Explore the underwater world with our professional scuba diving instructors.',
                'short_description' => 'Scuba diving with professional guides.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Nero Diving Tour',
                'slug' => 'nero-diving-tour',
                'description' => 'Explore the underwater world with our professional scuba diving instructors.',
                'short_description' => 'Scuba diving with professional guides.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Deep Diving Tour',
                'slug' => 'deep-diving-tour',
                'description' => 'Explore the underwater world with our professional scuba diving instructors.',
                'short_description' => 'Scuba diving with professional guides.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Sea Diving Tour',
                'slug' => 'sea-diving-tour',
                'description' => 'Explore the underwater world with our professional scuba diving instructors.',
                'short_description' => 'Scuba diving with professional guides.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Lake Diving Tour',
                'slug' => 'lake-diving-tour',
                'description' => 'Explore the underwater world with our professional scuba diving instructors.',
                'short_description' => 'Scuba diving with professional guides.',
                'featured_activity' => true,
            ],
            [
                'name' => 'River Kayaking Adventure',
                'slug' => 'river-kayaking-adventure',
                'description' => 'Experience the thrill of kayaking through beautiful river bends.',
                'short_description' => 'Exciting river kayaking journey.',
                'featured_activity' => false,
            ],
            [
                'name' => 'Sunset Paddle Boarding',
                'slug' => 'sunset-paddle-boarding',
                'description' => 'Paddle board on calm waters as the sun sets on the horizon.',
                'short_description' => 'Relaxing paddle board at sunset.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Jungle Trekking Expedition',
                'slug' => 'jungle-trekking-expedition',
                'description' => 'Explore dense jungles with expert guides and thrilling trails.',
                'short_description' => 'Guided jungle trekking experience.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Cave Exploration Tour',
                'slug' => 'cave-exploration-tour',
                'description' => 'Discover underground mysteries with our safe cave tours.',
                'short_description' => 'Adventure into hidden caves.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Mountain Biking Challenge',
                'slug' => 'mountain-biking-challenge',
                'description' => 'Conquer rough terrains with high-performance mountain bikes.',
                'short_description' => 'High-energy mountain biking fun.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Beach Volleyball Camp',
                'slug' => 'beach-volleyball-camp',
                'description' => 'Enjoy a fun-filled beach volleyball session with your team.',
                'short_description' => 'Team sports on the beach.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Desert Safari Tour',
                'slug' => 'desert-safari-tour',
                'description' => 'Ride across sandy dunes in a thrilling desert safari experience.',
                'short_description' => 'Exciting desert ride with scenic views.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Hot Air Balloon Ride',
                'slug' => 'hot-air-balloon-ride',
                'description' => 'Soar above landscapes and enjoy a bird’s-eye view.',
                'short_description' => 'Peaceful ride in a hot air balloon.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Snorkeling with Turtles',
                'slug' => 'snorkeling-with-turtles',
                'description' => 'Swim alongside sea turtles in crystal clear waters.',
                'short_description' => 'Underwater exploration with turtles.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Forest Ziplining Adventure',
                'slug' => 'forest-ziplining-adventure',
                'description' => 'Fly through the treetops in an adrenaline-pumping zipline ride.',
                'short_description' => 'High-speed forest ziplining.',
                'featured_activity' => false,
            ],
            [
                'name' => 'Island Hopping Cruise',
                'slug' => 'island-hopping-cruise',
                'description' => 'Sail across stunning islands and enjoy scenic stopovers.',
                'short_description' => 'Multiple island visits by boat.',
                'featured_activity' => false,
            ],
            [
                'name' => 'Rock Climbing Session',
                'slug' => 'rock-climbing-session',
                'description' => 'Test your strength with our safe and fun rock climbing tour.',
                'short_description' => 'Challenging rock climbing routes.',
                'featured_activity' => false,
            ],
            [
                'name' => 'Night Safari Experience',
                'slug' => 'night-safari-experience',
                'description' => 'Explore wildlife under moonlight with our guided night safari.',
                'short_description' => 'Wildlife spotting after dark.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Scuba Diving Certification',
                'slug' => 'scuba-diving-certification',
                'description' => 'Get trained and certified in scuba diving by professionals.',
                'short_description' => 'Complete scuba training course.',
                'featured_activity' => false,
            ],
            [
                'name' => 'Cultural Village Tour',
                'slug' => 'cultural-village-tour',
                'description' => 'Learn traditions and crafts in a local cultural village.',
                'short_description' => 'Interactive cultural exploration.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Bird Watching Walk',
                'slug' => 'bird-watching-walk',
                'description' => 'Stroll through nature and spot exotic bird species.',
                'short_description' => 'Peaceful walk with birdwatching.',
                'featured_activity' => false,
            ],
            [
                'name' => 'Waterfall Picnic Escape',
                'slug' => 'waterfall-picnic-escape',
                'description' => 'Relax by a waterfall and enjoy a peaceful picnic experience.',
                'short_description' => 'Scenic picnic by the falls.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Horseback Riding Trail',
                'slug' => 'horseback-riding-trail',
                'description' => 'Ride majestic horses through mountain trails.',
                'short_description' => 'Guided horseback adventure.',
                'featured_activity' => false,
            ],
            [
                'name' => 'Fishing Village Experience',
                'slug' => 'fishing-village-experience',
                'description' => 'Live like a local in a quaint fishing village.',
                'short_description' => 'Authentic village lifestyle tour.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Paragliding Thrill Ride',
                'slug' => 'paragliding-thrill-ride',
                'description' => 'Glide through the air with panoramic mountain views.',
                'short_description' => 'Sky-high paragliding adventure.',
                'featured_activity' => false,
            ],
            [
                'name' => 'Farm to Table Cooking Class',
                'slug' => 'farm-to-table-cooking-class',
                'description' => 'Learn to cook fresh dishes using farm-grown ingredients.',
                'short_description' => 'Hands-on culinary experience.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Stargazing Camp Night',
                'slug' => 'stargazing-camp-night',
                'description' => 'Spend a night under the stars with telescope viewing.',
                'short_description' => 'Peaceful night sky observation.',
                'featured_activity' => false,
            ],
            [
                'name' => 'Lakeside Yoga Retreat',
                'slug' => 'lakeside-yoga-retreat',
                'description' => 'Rejuvenate your soul with lakeside meditation and yoga.',
                'short_description' => 'Serene yoga by the lake.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Historical Ruins Exploration',
                'slug' => 'historical-ruins-exploration',
                'description' => 'Walk through ancient ruins and uncover their secrets.',
                'short_description' => 'Step into ancient history.',
                'featured_activity' => false,
            ],
            [
                'name' => 'Sunrise Jungle Meditation',
                'slug' => 'sunrise-jungle-meditation',
                'description' => 'Start your day with peace in the heart of nature.',
                'short_description' => 'Morning meditation in the wild.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Culinary Street Food Tour',
                'slug' => 'culinary-street-food-tour',
                'description' => 'Taste the best local street food with our guided tour.',
                'short_description' => 'Delicious street food tasting.',
                'featured_activity' => true,
            ],
            [
                'name' => 'Artisan Craft Workshop',
                'slug' => 'artisan-craft-workshop',
                'description' => 'Create your own crafts with local artisans.',
                'short_description' => 'Hands-on craft making experience.',
                'featured_activity' => false,
            ],
        ];

        foreach ($activities as $activityData) {
            $activity = Activity::create($activityData);

            ActivityCategory::create([
                'activity_id' => $activity->id,
                'category_id' => rand(1, 3) 
            ]);
            ActivityCategory::create([
                'activity_id' => $activity->id,
                'category_id' => rand(2, 4) 
            ]);
            // ActivityAttribute::create([
            //     'activity_id' => $activity->id,
            //     'attribute_id' => 1,
            //     'attribute_value' => '1 Hour'
            // ]);
            // ActivityAttribute::create([
            //     'activity_id' => $activity->id,
            //     'attribute_id' => 2,
            //     'attribute_value' => 'Medium'
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
            
                ActivityAttribute::create([
                    'activity_id' => $activity->id,
                    'attribute_id' => $attributeId,
                    'attribute_value' => $attributeValue
                ]);
            }

            ActivityTag::create([
                'activity_id' => $activity->id,
                'tag_id' => rand(1, 2),
            ]);

            ActivityTag::create([
                'activity_id' => $activity->id,
                'tag_id' => rand(3, 4),
            ]);

            ActivityLocation::create([
                'activity_id' => $activity->id,
                'city_id' => rand(1, 4),
                'location_type' => 'primary',
                'location_label' => 'Main Location',
                'duration' => null
            ]);
        
            ActivityLocation::create([
                'activity_id' => $activity->id,
                'city_id' => rand(1, 4),
                'location_type' => 'additional',
                'location_label' => 'Highlight', // Custom value allowed
                'duration' => rand(5, 20)
            ]);

            // Pricing
            $pricing = ActivityPricing::create([
                'activity_id' => $activity->id,
                'regular_price' => rand(50, 500),
                'currency' => 'USD',
            ]);

            // ⏳ Seasonal Pricing (if enabled)
            $seasons = ['winter', 'summer', 'spring', 'autumn'];
            // if ($pricing->enable_seasonal_pricing) {
                ActivitySeasonalPricing::create([
                    'activity_id' => $activity->id,
                    'enable_seasonal_pricing' => true,
                    'season_name' => $seasons[array_rand($seasons)],
                    'season_start' => '2025-12-01',
                    'season_end'    => '2026-02-28',
                    'season_price' => rand(60, 400),
                ]);
            // }

            // 👫 Group Discounts
            ActivityGroupDiscount::create([
                'activity_id' => $activity->id,
                'min_people' => rand(5, 10),
                'discount_amount' => rand(10, 50),
                'discount_type' => 'percentage'
            ]);
            ActivityGroupDiscount::create([
                'activity_id' => $activity->id,
                'min_people' => rand(11, 20),
                'discount_amount' => rand(5, 30),
                'discount_type' => 'fixed'
            ]);

            // 🎟 Early Bird Discount (if enabled)
            // if ($pricing->enable_early_bird_discount) {
                ActivityEarlyBirdDiscount::create([
                    'activity_id' => $activity->id,
                    'enabled' => true,
                    'days_before_start' => rand(10, 30),
                    'discount_amount' => rand(5, 20),
                    'discount_type' => 'percentage'
                ]);
            // }

            // ⏳ Last Minute Discount (if enabled)
            // if ($pricing->enable_last_minute_discount) {
                ActivityLastMinuteDiscount::create([
                    'activity_id' => $activity->id,
                    'enabled' => true,
                    'days_before_start' => rand(1, 5),
                    'discount_amount' => rand(5, 15),
                    'discount_type' => 'fixed'
                ]);
            // }

            // 🎁 Promo Codes
            ActivityPromoCode::create([
                'activity_id' => $activity->id,
                'promo_code' => 'NEWYEAR50',
                'max_uses' => 100,
                'discount_amount' => 50,
                'discount_type' => 'percentage',
                'valid_from' => '2025-06-01',
                'valid_to' => '2025-08-31',
            ]);
            ActivityPromoCode::create([
                'activity_id' => $activity->id,
                'promo_code' => 'SUMMER25',
                'max_uses' => 50,
                'discount_amount' => 25,
                'discount_type' => 'fixed',
                'valid_from' => '2025-06-01',
                'valid_to' => '2025-08-31',
            ]);
            ActivityMediaGallery::create([
                'activity_id' => $activity->id,
                'media_id' => rand(1, 5),
            ]);
            ActivityAvailability::create([
                'activity_id' => $activity->id,
                'date_based_activity' => $dateBased = fake()->boolean, // Random true or false
                'start_date' => $dateBased ? fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d') : null,
                'end_date' => $dateBased ? fake()->dateTimeBetween('+2 month', '+6 month')->format('Y-m-d') : null,
                'quantity_based_activity' => $quantityBased = fake()->boolean, // Random true or false
                'max_quantity' => $quantityBased ? fake()->numberBetween(1, 20) : null,
            ]);

            $addonIds = Addon::where('type', 'activity')
                ->where('active_status', true)   // ✅ sirf active addons
                ->inRandomOrder()
                ->limit(rand(2, 4))   // 2 से 4 addons random select
                ->pluck('id');
        
            foreach ($addonIds as $addonId) {
                ActivityAddon::create([
                    'activity_id' => $activity->id,
                    'addon_id'    => $addonId,
                ]);
            }
        }
    }
}
