<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transfer;
use App\Models\TransferVendorRoute;
use App\Models\TransferPricingAvailability;
use App\Models\TransferMediaGallery;
use App\Models\TransferSeo;
use App\Models\Media;
use Illuminate\Support\Arr;

class TransferSeeder extends Seeder
{
    public function run()
    {
        $mediaIds = Media::pluck('id')->toArray();

        // Resolve Dubai place IDs for transfer pickup/dropoff
        $dubaiPlaces = \App\Models\Place::whereHas('city', fn($q) => $q->where('slug', 'dubai'))
            ->pluck('id', 'slug')->toArray();

        $transfers = [
            [
                'name'            => 'Airport Transfer',
                'slug'            => 'airport-transfer',
                'description'     => 'Airport to Hotel transfer service',
                'transfer_type'   => 'One-way',
                'vendor_id'       => 1,
                'route_id'        => 1,
                'pricing_tier_id' => 1,
                'availability_id' => 1,
                'media_gallery'   => Arr::random($mediaIds, rand(3, 4)),
                'seo'             => [
                    'meta_title'       => 'Airport Transfer Service',
                    'meta_description' => 'Comfortable and reliable airport transfer service.',
                    'keywords'         => 'airport, transfer, hotel',
                    'og_image_url'     => 'https://example.com/og-image1.jpg',
                    'canonical_url'    => 'https://example.com/airport-transfer',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode([
                        '@context' => 'https://schema.org',
                        '@type'    => 'Service',
                        'name'     => 'Airport Transfer'
                    ])
                ]
            ],
            [
                'name'            => 'City Tour',
                'slug'            => 'city-tour',
                'description'     => 'Full day city tour with private guide',
                'transfer_type'   => 'Round-trip',
                'vendor_id'       => 2,
                'route_id'        => 4,
                'pricing_tier_id' => 2,
                'availability_id' => 2,
                'media_gallery'   => Arr::random($mediaIds, rand(3, 4)),
                'seo'             => [
                    'meta_title'       => 'City Tour',
                    'meta_description' => 'Enjoy a full day city tour with a professional guide.',
                    'keywords'         => 'city, tour, guide',
                    'og_image_url'     => 'https://example.com/og-image2.jpg',
                    'canonical_url'    => 'https://example.com/city-tour',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode([
                        '@context' => 'https://schema.org',
                        '@type'    => 'Service',
                        'name'     => 'City Tour'
                    ])
                ]
            ],
            [
                'name'            => 'Hotel Transfer',
                'slug'            => 'hotel-transfer',
                'description'     => 'Hotel to Train Station transfer service',
                'transfer_type'   => 'One-way',
                'vendor_id'       => 3,
                'route_id'        => 5,
                'pricing_tier_id' => 3,
                'availability_id' => 3,
                'media_gallery'   => Arr::random($mediaIds, rand(3, 4)),
                'seo'             => [
                    'meta_title'       => 'Hotel Transfer',
                    'meta_description' => 'Easy and reliable hotel transfer service.',
                    'keywords'         => 'hotel, transfer, train',
                    'og_image_url'     => 'https://example.com/og-image3.jpg',
                    'canonical_url'    => 'https://example.com/hotel-transfer',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode([
                        '@context' => 'https://schema.org',
                        '@type'    => 'Service',
                        'name'     => 'Hotel Transfer'
                    ])
                ]
            ],
            [
                'name'            => 'Adventure Trip',
                'slug'            => 'adventure-trip',
                'description'     => 'Mountain hiking trip with professional guide',
                'transfer_type'   => 'Round-trip',
                'vendor_id'       => 4,
                'route_id'        => 7,
                'pricing_tier_id' => 4,
                'availability_id' => 4,
                'media_gallery'   => Arr::random($mediaIds, rand(3, 4)),
                'seo'             => [
                    'meta_title'       => 'Adventure Trip',
                    'meta_description' => 'Exciting adventure trip with professional guide.',
                    'keywords'         => 'adventure, trip, guide',
                    'og_image_url'     => 'https://example.com/og-image4.jpg',
                    'canonical_url'    => 'https://example.com/adventure-trip',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode([
                        '@context' => 'https://schema.org',
                        '@type'    => 'Service',
                        'name'     => 'Adventure Trip'
                    ])
                ]
            ],

            // ── Dubai Transfers (admin-managed) ──
            [
                'name'            => 'Dubai Airport to Burj Khalifa',
                'slug'            => 'dubai-airport-to-burj-khalifa',
                'description'     => 'Private transfer from Dubai International Airport to Burj Khalifa area in a comfortable sedan or SUV.',
                'transfer_type'   => 'One-way',
                'is_vendor'       => true,
                'pickup_location' => 'Dubai International Airport',
                'dropoff_location'=> 'Burj Khalifa',
                'pickup_place_id' => null,
                'dropoff_place_id'=> $dubaiPlaces['burj-khalifa'] ?? null,
                'vehicle_type'    => 'Sedan / SUV',
                'inclusion'       => 'Meet & greet, bottled water, WiFi',
                'media_gallery'   => Arr::random($mediaIds, rand(3, 4)),
                'seo'             => [
                    'meta_title'       => 'Dubai Airport to Burj Khalifa Transfer',
                    'meta_description' => 'Private airport transfer to Burj Khalifa and Downtown Dubai.',
                    'keywords'         => 'dubai, airport, burj khalifa, transfer',
                    'og_image_url'     => 'https://example.com/og-dubai-airport.jpg',
                    'canonical_url'    => 'https://example.com/dubai-airport-to-burj-khalifa',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode(['@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'Dubai Airport to Burj Khalifa'])
                ]
            ],
            [
                'name'            => 'Dubai Mall to Palm Jumeirah',
                'slug'            => 'dubai-mall-to-palm-jumeirah',
                'description'     => 'Comfortable transfer from Dubai Mall to Palm Jumeirah hotels and resorts via Sheikh Zayed Road.',
                'transfer_type'   => 'One-way',
                'is_vendor'       => true,
                'pickup_location' => 'Dubai Mall',
                'dropoff_location'=> 'Palm Jumeirah',
                'pickup_place_id' => $dubaiPlaces['dubai-mall'] ?? null,
                'dropoff_place_id'=> $dubaiPlaces['palm-jumeirah'] ?? null,
                'vehicle_type'    => 'Sedan',
                'inclusion'       => 'AC vehicle, bottled water',
                'media_gallery'   => Arr::random($mediaIds, rand(3, 4)),
                'seo'             => [
                    'meta_title'       => 'Dubai Mall to Palm Jumeirah Transfer',
                    'meta_description' => 'Quick and comfortable ride from Dubai Mall to Palm Jumeirah.',
                    'keywords'         => 'dubai, mall, palm jumeirah, transfer',
                    'og_image_url'     => 'https://example.com/og-dubai-mall.jpg',
                    'canonical_url'    => 'https://example.com/dubai-mall-to-palm-jumeirah',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode(['@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'Dubai Mall to Palm Jumeirah'])
                ]
            ],
            [
                'name'            => 'Dubai Marina to Dubai Creek',
                'slug'            => 'dubai-marina-to-dubai-creek',
                'description'     => 'Scenic transfer from Dubai Marina to the historic Dubai Creek area, passing iconic landmarks along the way.',
                'transfer_type'   => 'One-way',
                'is_vendor'       => true,
                'pickup_location' => 'Dubai Marina',
                'dropoff_location'=> 'Dubai Creek',
                'pickup_place_id' => $dubaiPlaces['dubai-marina'] ?? null,
                'dropoff_place_id'=> $dubaiPlaces['dubai-creek'] ?? null,
                'vehicle_type'    => 'SUV',
                'inclusion'       => 'AC vehicle, bottled water, city guide map',
                'media_gallery'   => Arr::random($mediaIds, rand(3, 4)),
                'seo'             => [
                    'meta_title'       => 'Dubai Marina to Dubai Creek Transfer',
                    'meta_description' => 'Scenic ride from modern Dubai Marina to historic Dubai Creek.',
                    'keywords'         => 'dubai, marina, creek, transfer',
                    'og_image_url'     => 'https://example.com/og-dubai-marina.jpg',
                    'canonical_url'    => 'https://example.com/dubai-marina-to-creek',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode(['@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'Dubai Marina to Dubai Creek'])
                ]
            ],
            [
                'name'            => 'Burj Khalifa to Dubai Marina Round Trip',
                'slug'            => 'burj-khalifa-to-dubai-marina-round-trip',
                'description'     => 'Round-trip private transfer between Burj Khalifa and Dubai Marina with flexible timing and professional driver.',
                'transfer_type'   => 'Round-trip',
                'is_vendor'       => true,
                'pickup_location' => 'Burj Khalifa',
                'dropoff_location'=> 'Dubai Marina',
                'pickup_place_id' => $dubaiPlaces['burj-khalifa'] ?? null,
                'dropoff_place_id'=> $dubaiPlaces['dubai-marina'] ?? null,
                'vehicle_type'    => 'Luxury Sedan',
                'inclusion'       => 'Professional driver, WiFi, refreshments, waiting time included',
                'media_gallery'   => Arr::random($mediaIds, rand(3, 4)),
                'seo'             => [
                    'meta_title'       => 'Burj Khalifa to Dubai Marina Round Trip',
                    'meta_description' => 'Round-trip luxury transfer between Downtown and Marina.',
                    'keywords'         => 'dubai, burj khalifa, marina, round trip',
                    'og_image_url'     => 'https://example.com/og-dubai-roundtrip.jpg',
                    'canonical_url'    => 'https://example.com/burj-khalifa-marina-roundtrip',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode(['@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'Burj Khalifa to Dubai Marina Round Trip'])
                ]
            ],
        ];

        foreach ($transfers as $data) {
            $transfer = Transfer::create([
                'name'          => $data['name'],
                'slug'          => $data['slug'],
                'description'   => $data['description'],
                'transfer_type' => $data['transfer_type'],
            ]);

            // Vendor & Route
            TransferVendorRoute::create([
                'transfer_id'      => $transfer->id,
                'is_vendor'        => $data['is_vendor'] ?? false,
                'vendor_id'        => $data['vendor_id'] ?? null,
                'route_id'         => $data['route_id'] ?? null,
                'pickup_location'  => $data['pickup_location'] ?? null,
                'dropoff_location' => $data['dropoff_location'] ?? null,
                'pickup_place_id'  => $data['pickup_place_id'] ?? null,
                'dropoff_place_id' => $data['dropoff_place_id'] ?? null,
                'vehicle_type'     => $data['vehicle_type'] ?? null,
                'inclusion'        => $data['inclusion'] ?? null,
            ]);

            // Pricing & Availability (if provided)
            if (isset($data['pricing_tier_id']) && isset($data['availability_id'])) {
                TransferPricingAvailability::create([
                    'transfer_id' => $transfer->id,
                    'pricing_tier_id' => $data['pricing_tier_id'],
                    'availability_id' => $data['availability_id'],
                ]);
            }

            // Media Gallery - 3-4 random images
            foreach ($data['media_gallery'] as $mediaId) {
                TransferMediaGallery::create([
                    'transfer_id' => $transfer->id,
                    'media_id'    => $mediaId,
                ]);
            }

            // SEO  
            TransferSeo::create([
                'transfer_id' => $transfer->id,
                'meta_title' => $data['seo']['meta_title'],
                'meta_description' => $data['seo']['meta_description'],
                'keywords' => $data['seo']['keywords'],
                'og_image_url' => $data['seo']['og_image_url'],
                'canonical_url' => $data['seo']['canonical_url'],
                'schema_type' => $data['seo']['schema_type'],
                'schema_data' => $data['seo']['schema_data'],
            ]);
        }
    }
}
