<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Package;
use App\Models\PackageLocation;
use App\Models\PackageInformation;
use App\Models\PackageSchedule;
use App\Models\PackageActivity;
use App\Models\PackageTransfer;
use App\Models\PackageItinerary;
use App\Models\PackageBasePricing;
use App\Models\PackagePriceVariation;
use App\Models\PackageBlackoutDate;
use App\Models\PackageInclusionExclusion;
use App\Models\PackageMediaGallery;
use App\Models\PackageFaq;
use App\Models\PackageSeo;
use App\Models\PackageCategory;
use App\Models\PackageAttribute;
use App\Models\PackageTag;
use App\Models\PackageAvailability;
use App\Models\Addon;
use App\Models\PackageAddon;

class PackageSeeder extends Seeder
{
    public function run()
    {

        // Foreign key checks ko disable karo
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Tables ko truncate ki jagah delete karo
        Package::query()->delete();
        PackageInformation::query()->delete();
        PackageSchedule::query()->delete();
        PackageActivity::query()->delete();
        PackageTransfer::query()->delete();
        PackageItinerary::query()->delete();
        PackageBasePricing::query()->delete();
        PackagePriceVariation::query()->delete();
        PackageBlackoutDate::query()->delete();
        PackageInclusionExclusion::query()->delete();
        PackageMediaGallery::query()->delete();
        PackageSeo::query()->delete();
        PackageCategory::query()->delete();
        PackageAttribute::query()->delete();
        PackageTag::query()->delete();

        // Foreign key checks ko wapas enable karo
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $packages = [
            [
                'name'             => 'Holiday In Goa',
                'slug'             => Str::slug('Holiday In Goa'),
                'description'      => 'Explore the luxury of Kenyas wild safari.',
                'featured_package' => true,
                'private_package'  => false,
            ],
            [
                'name'             => 'Holidays In Kerala',
                'slug'             => Str::slug('Holidays In Kerala'),
                'description'      => 'Experience the thrill of the Himalayas.',
                'featured_package' => true,
                'private_package'  => false,
            ],
            [
                'name'             => 'Honeymoon Package in Udisa',
                'slug'             => Str::slug('Honeymoon Package in Udisa'),
                'description'      => 'Relax on the white sands of Maldives.',
                'featured_package' => false,
                'private_package'  => false,
            ],
            [
                'name'             => 'Vacation In Pathankot',
                'slug'             => Str::slug('Vacation In Pathankot'),
                'description'      => 'Explore the rich culture of Japan.',
                'featured_package' => true,
                'private_package'  => true,
            ],
            [
                'name'             => 'Holidays In Karnatka',
                'slug'             => Str::slug('Holidays In Karnatka'),
                'description'      => 'Explore the rich culture of Japan.',
                'featured_package' => true,
                'private_package'  => true,
            ],
            [
                'name'             => 'Holidays in Dharamshala',
                'slug'             => Str::slug('Holidays in Dharamshala'),
                'description'      => 'Explore the rich culture of Japan.',
                'featured_package' => true,
                'private_package'  => true,
            ],
            [
                'name'             => 'Holidays Package in Kashmir',
                'slug'             => Str::slug('Holidays Package in Kashmir'),
                'description'      => 'Explore the rich culture of Japan.',
                'featured_package' => true,
                'private_package'  => true,
            ],
            [
                'name'             => 'Holidays Package in Triund',
                'slug'             => Str::slug('Holidays Package in Triund'),
                'description'      => 'Explore the rich culture of Japan.',
                'featured_package' => true,
                'private_package'  => true,
            ],
        
            // --- 27 New Packages ---
            [
                'name' => 'Beach Escape in Andaman',
                'slug' => Str::slug('Beach Escape in Andaman'),
                'description' => 'Discover the blue waters of Andaman Islands.',
                'featured_package' => false,
                'private_package' => false,
            ],
            [
                'name' => 'Spiritual Journey in Rishikesh',
                'slug' => Str::slug('Spiritual Journey in Rishikesh'),
                'description' => 'Experience peace and yoga in Rishikesh.',
                'featured_package' => true,
                'private_package' => true,
            ],
            [
                'name' => 'Hill Station Tour in Manali',
                'slug' => Str::slug('Hill Station Tour in Manali'),
                'description' => 'Snow capped mountains and serene views.',
                'featured_package' => true,
                'private_package' => false,
            ],
            [
                'name' => 'Cultural Trail in Jaipur',
                'slug' => Str::slug('Cultural Trail in Jaipur'),
                'description' => 'Explore forts and palaces of Rajasthan.',
                'featured_package' => true,
                'private_package' => false,
            ],
            [
                'name' => 'Backwater Bliss in Alleppey',
                'slug' => Str::slug('Backwater Bliss in Alleppey'),
                'description' => 'Relax in houseboats of Kerala.',
                'featured_package' => false,
                'private_package' => false,
            ],
            [
                'name' => 'Wildlife Safari in Jim Corbett',
                'slug' => Str::slug('Wildlife Safari in Jim Corbett'),
                'description' => 'See tigers and elephants in the wild.',
                'featured_package' => true,
                'private_package' => false,
            ],
            [
                'name' => 'Snow Adventure in Gulmarg',
                'slug' => Str::slug('Snow Adventure in Gulmarg'),
                'description' => 'Enjoy skiing and snowboarding in Gulmarg.',
                'featured_package' => false,
                'private_package' => true,
            ],
            [
                'name' => 'Monsoon Retreat in Munnar',
                'slug' => Str::slug('Monsoon Retreat in Munnar'),
                'description' => 'Lush green tea gardens await you.',
                'featured_package' => false,
                'private_package' => true,
            ],
            [
                'name' => 'Romantic Getaway in Udaipur',
                'slug' => Str::slug('Romantic Getaway in Udaipur'),
                'description' => 'Lakes and palaces perfect for couples.',
                'featured_package' => true,
                'private_package' => false,
            ],
            [
                'name' => 'Food Tour in Amritsar',
                'slug' => Str::slug('Food Tour in Amritsar'),
                'description' => 'Tasty treats and heritage walk.',
                'featured_package' => false,
                'private_package' => true,
            ],
            [
                'name' => 'Pilgrimage Tour in Varanasi',
                'slug' => Str::slug('Pilgrimage Tour in Varanasi'),
                'description' => 'Spiritual baths and Ganga Aarti.',
                'featured_package' => true,
                'private_package' => false,
            ],
            [
                'name' => 'Island Hopper in Lakshadweep',
                'slug' => Str::slug('Island Hopper in Lakshadweep'),
                'description' => 'Isolated beaches and coral reefs.',
                'featured_package' => false,
                'private_package' => false,
            ],
            [
                'name' => 'Luxury Escape in Shimla',
                'slug' => Str::slug('Luxury Escape in Shimla'),
                'description' => 'Historic charm and luxury hotels.',
                'featured_package' => true,
                'private_package' => false,
            ],
            [
                'name' => 'Eco Tour in Sikkim',
                'slug' => Str::slug('Eco Tour in Sikkim'),
                'description' => 'Clean green and pristine views.',
                'featured_package' => false,
                'private_package' => true,
            ],
            [
                'name' => 'Festive Tour in Kolkata',
                'slug' => Str::slug('Festive Tour in Kolkata'),
                'description' => 'Enjoy Durga Puja celebrations.',
                'featured_package' => true,
                'private_package' => true,
            ],
            [
                'name' => 'Fort Tour in Chittorgarh',
                'slug' => Str::slug('Fort Tour in Chittorgarh'),
                'description' => 'Massive forts and Rajput glory.',
                'featured_package' => true,
                'private_package' => false,
            ],
            [
                'name' => 'Tea Garden Walk in Darjeeling',
                'slug' => Str::slug('Tea Garden Walk in Darjeeling'),
                'description' => 'Toy train and scenic tea estates.',
                'featured_package' => false,
                'private_package' => false,
            ],
            [
                'name' => 'Temple Trail in Tamil Nadu',
                'slug' => Str::slug('Temple Trail in Tamil Nadu'),
                'description' => 'Ancient temples and architecture.',
                'featured_package' => false,
                'private_package' => true,
            ],
            [
                'name' => 'Wild East in Meghalaya',
                'slug' => Str::slug('Wild East in Meghalaya'),
                'description' => 'Caves, waterfalls, and forests.',
                'featured_package' => false,
                'private_package' => false,
            ],
            [
                'name' => 'Bike Trip to Leh Ladakh',
                'slug' => Str::slug('Bike Trip to Leh Ladakh'),
                'description' => 'Rugged terrains and biker heaven.',
                'featured_package' => true,
                'private_package' => true,
            ],
            [
                'name' => 'Sunset Tour in Kanyakumari',
                'slug' => Str::slug('Sunset Tour in Kanyakumari'),
                'description' => 'Meeting point of oceans.',
                'featured_package' => false,
                'private_package' => true,
            ],
            [
                'name' => 'Island Escape to Diu',
                'slug' => Str::slug('Island Escape to Diu'),
                'description' => 'Portuguese vibes and quiet beaches.',
                'featured_package' => true,
                'private_package' => false,
            ],
            [
                'name' => 'Colonial Trail in Pondicherry',
                'slug' => Str::slug('Colonial Trail in Pondicherry'),
                'description' => 'French town and seaside cafes.',
                'featured_package' => true,
                'private_package' => true,
            ],
            [
                'name' => 'Jungle Stay in Bandipur',
                'slug' => Str::slug('Jungle Stay in Bandipur'),
                'description' => 'Stay amidst wildlife.',
                'featured_package' => false,
                'private_package' => false,
            ],
            [
                'name' => 'Adventure Camp in Rann of Kutch',
                'slug' => Str::slug('Adventure Camp in Rann of Kutch'),
                'description' => 'White desert and cultural nights.',
                'featured_package' => true,
                'private_package' => true,
            ],
            [
                'name' => 'Photography Tour in Spiti',
                'slug' => Str::slug('Photography Tour in Spiti'),
                'description' => 'Barren landscapes and monasteries.',
                'featured_package' => false,
                'private_package' => true,
            ],
            [
                'name' => 'Waterfall Hike in Cherrapunji',
                'slug' => Str::slug('Waterfall Hike in Cherrapunji'),
                'description' => 'Trek to India’s highest falls.',
                'featured_package' => false,
                'private_package' => false,
            ],
        ];

        foreach ($packages as $PackageData) {
            $package = Package::create($PackageData);


            // PackageInformation::create([
            //     'package_id'    => $package->id,
            //     'section_title' => 'Famous Tourist Attractions',
            //     'content'       => 'Taj Mahal, Jaipur, Kerala Backwaters'
            // ]);

            $informations = [
                [
                    'section_title' => 'Famous Tourist Attractions',
                    'content'       => 'Taj Mahal, Jaipur, Kerala Backwaters'
                ],
                [
                    'section_title' => 'Famous Tourist food',
                    'content'       => 'burger, pizza, bread'
                ],
                [
                    'section_title' => 'Famous Tourist Vegies',
                    'content'       => 'Potato, Lahusan, Tomato'
                ]
            ];

            foreach ($informations as $information) {
                PackageInformation::create([
                    'package_id'    => $package->id,
                    'section_title' => $information['section_title'],
                    'content'       => $information['content']
                ]);
            }

            PackageLocation::create([
                'package_id' => $package->id,
                'city_id'    => rand(1, 4),
            ]);

            for ($day = 1; $day <= 3; $day++) {
                $schedule = PackageSchedule::create([
                    'package_id' => $package->id,
                    'day'        => $day,
                ]);

                PackageTransfer::create([
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

                PackageActivity::create([
                    'schedule_id' => $schedule->id,
                    'activity_id' => rand(1, 8),
                    'start_time'  => '09:00:00',
                    'end_time'    => '11:00:00',
                    'notes'       => 'Sample activity note',
                    'price'       => 100.00,
                    'included'    => true,
                ]);

                PackageItinerary::create([
                    'schedule_id'  => $schedule->id,
                    'itinerary_id' => rand(1, 8),
                    'start_time'   => '09:00:00',
                    'end_time'     => '11:00:00',
                    'notes'        => 'Sample itinerary note',
                    'price'        => 100.00,
                    'included'     => true,
                ]);
            }

            $basePricing = PackageBasePricing::create([
                'package_id'   => $package->id,
                'currency'     => 'USD',
                'availability' => 'Available',
                'start_date'   => now(),
                'end_date'     => now()->addMonth(),
            ]);

            PackagePriceVariation::create([
                'base_pricing_id' => $basePricing->id,
                'name'            => 'Standard Package',
                'regular_price'   => 1000.00,
                'sale_price'      => 800.00,
                'max_guests'      => 4,
                'description'     => 'Standard package with discount',
            ]);

            PackageBlackoutDate::create([
                'base_pricing_id' => $basePricing->id,
                'date'            => now()->addDays(7),
                'reason'          => 'Holiday season',
            ]);

            PackageInclusionExclusion::create([
                'package_id'  => $package->id,
                'type'        => 'Meal',
                'title'       => 'Breakfast included',
                'description' => 'Breakfast included in the package',
                'included'    => true,
            ]);

            PackageMediaGallery::create([
                'package_id' => $package->id,
                'media_id'   => rand(1, 5),
            ]);

            // $packageId = $package->id;

            // $lastQuestion = PackageFaq::where('package_id', $packageId)
            // ->orderBy('question_number', 'desc')
            // ->first();

            // $questionNumber = $lastQuestion ? $lastQuestion->question_number + 1 : 1;

            $faqs = [
                [
                    'question' => 'Do I need a visa to visit India?',
                    'answer'   => 'Yes, but Visa on arrival is available for many countries.'
                ],
                [
                    'question' => 'What is the currency in India?',
                    'answer'   => 'The Indian Rupee (INR) is the official currency.'
                ]
            ];
            
            foreach ($faqs as $faq) {
                PackageFaq::create([
                    'package_id' => $package->id,
                    // 'question_number' => $questionNumber,
                    'question'   => $faq['question'],
                    'answer'     => $faq['answer']
                ]);
                // $questionNumber++;
            }

            PackageSeo::create([
                'package_id'       => $package->id,
                'meta_title'       => 'Sample Package',
                'meta_description' => 'This is a sample Package for SEO testing.',
                'keywords'         => 'Package, travel, sample',
                'og_image_url'     => 'https://example.com/sample-og.jpg',
                'canonical_url'    => 'https://example.com/sample',
                'schema_type'      => 'Travel',
                'schema_data'      => json_encode([
                    'type' => 'Travel',
                    'name' => $package->name,
                ]),
            ]);

            PackageCategory::create([
                'package_id'  => $package->id,
                'category_id' => 1,
            ]);

            // PackageAttribute::create([
            //     'package_id'      => $package->id,
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
            
                PackageAttribute::create([
                    'package_id' => $package->id,
                    'attribute_id' => $attributeId,
                    'attribute_value' => $attributeValue
                ]);
            }

            PackageTag::create([
                'package_id' => $package->id,
                'tag_id'     => rand(1, 4),
            ]);

            PackageAvailability::create([
                'package_id'             => $package->id,
                'date_based_Package'     => $dateBased = fake()->boolean,
                'start_date'             => $dateBased ? fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d') : null,
                'end_date'               => $dateBased ? fake()->dateTimeBetween('+2 month', '+6 month')->format('Y-m-d') : null,
                'quantity_based_Package' => $quantityBased = fake()->boolean,
                'max_quantity'           => $quantityBased ? fake()->numberBetween(1, 100) : null,
            ]);

            $addonIds = Addon::where('type', 'package')
            ->where('active_status', true)
            ->inRandomOrder()
            ->limit(rand(2, 4))
            ->pluck('id');

            foreach ($addonIds as $addonId) {
                PackageAddon::create([
                    'package_id' => $package->id,
                    'addon_id'   => $addonId,
                ]);
            }
        }
    }

}
