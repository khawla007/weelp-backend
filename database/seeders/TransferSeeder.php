<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transfer;
use App\Models\TransferVendorRoute;
use App\Models\TransferMediaGallery;
use App\Models\TransferSeo;
use App\Models\Media;
use App\Models\Place;
use Illuminate\Support\Arr;

class TransferSeeder extends Seeder
{
    public function run()
    {
        $mediaIds = Media::pluck('id')->toArray();

        // Resolve Dubai place IDs for transfer pickup/dropoff (admin-managed flow)
        $dubaiPlaces = Place::whereHas('city', fn($q) => $q->where('slug', 'dubai'))
            ->pluck('id', 'slug')->toArray();

        // Admin-managed Dubai transfers. Vendor flow is suspended (coming soon),
        // so every entry is created with is_vendor = false and uses Dubai places
        // as pickup / drop-off points.
        $transfers = [
            [
                'name'             => 'Dubai Airport to Burj Khalifa',
                'slug'             => 'dubai-airport-to-burj-khalifa',
                'description'      => 'Private transfer from Dubai International Airport to Burj Khalifa area in a comfortable sedan or SUV.',
                'transfer_type'    => 'One-way',
                'pickup_location'  => 'Dubai International Airport',
                'dropoff_location' => 'Burj Khalifa',
                'pickup_place_id'  => null,
                'dropoff_place_id' => $dubaiPlaces['burj-khalifa'] ?? null,
                'vehicle_type'     => 'Sedan / SUV',
                'inclusion'        => 'Meet & greet, bottled water, WiFi',
                'seo'              => [
                    'meta_title'       => 'Dubai Airport to Burj Khalifa Transfer',
                    'meta_description' => 'Private airport transfer to Burj Khalifa and Downtown Dubai.',
                    'keywords'         => 'dubai, airport, burj khalifa, transfer',
                    'og_image_url'     => 'https://example.com/og-dubai-airport.jpg',
                    'canonical_url'    => 'https://example.com/dubai-airport-to-burj-khalifa',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode(['@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'Dubai Airport to Burj Khalifa']),
                ],
            ],
            [
                'name'             => 'Dubai Mall to Palm Jumeirah',
                'slug'             => 'dubai-mall-to-palm-jumeirah',
                'description'      => 'Comfortable transfer from Dubai Mall to Palm Jumeirah hotels and resorts via Sheikh Zayed Road.',
                'transfer_type'    => 'One-way',
                'pickup_location'  => 'Dubai Mall',
                'dropoff_location' => 'Palm Jumeirah',
                'pickup_place_id'  => $dubaiPlaces['dubai-mall'] ?? null,
                'dropoff_place_id' => $dubaiPlaces['palm-jumeirah'] ?? null,
                'vehicle_type'     => 'Sedan',
                'inclusion'        => 'AC vehicle, bottled water',
                'seo'              => [
                    'meta_title'       => 'Dubai Mall to Palm Jumeirah Transfer',
                    'meta_description' => 'Quick and comfortable ride from Dubai Mall to Palm Jumeirah.',
                    'keywords'         => 'dubai, mall, palm jumeirah, transfer',
                    'og_image_url'     => 'https://example.com/og-dubai-mall.jpg',
                    'canonical_url'    => 'https://example.com/dubai-mall-to-palm-jumeirah',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode(['@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'Dubai Mall to Palm Jumeirah']),
                ],
            ],
            [
                'name'             => 'Dubai Marina to Dubai Creek',
                'slug'             => 'dubai-marina-to-dubai-creek',
                'description'      => 'Scenic transfer from Dubai Marina to the historic Dubai Creek area, passing iconic landmarks along the way.',
                'transfer_type'    => 'One-way',
                'pickup_location'  => 'Dubai Marina',
                'dropoff_location' => 'Dubai Creek',
                'pickup_place_id'  => $dubaiPlaces['dubai-marina'] ?? null,
                'dropoff_place_id' => $dubaiPlaces['dubai-creek'] ?? null,
                'vehicle_type'     => 'SUV',
                'inclusion'        => 'AC vehicle, bottled water, city guide map',
                'seo'              => [
                    'meta_title'       => 'Dubai Marina to Dubai Creek Transfer',
                    'meta_description' => 'Scenic ride from modern Dubai Marina to historic Dubai Creek.',
                    'keywords'         => 'dubai, marina, creek, transfer',
                    'og_image_url'     => 'https://example.com/og-dubai-marina.jpg',
                    'canonical_url'    => 'https://example.com/dubai-marina-to-creek',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode(['@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'Dubai Marina to Dubai Creek']),
                ],
            ],
            [
                'name'             => 'Burj Khalifa to Dubai Marina Round Trip',
                'slug'             => 'burj-khalifa-to-dubai-marina-round-trip',
                'description'      => 'Round-trip private transfer between Burj Khalifa and Dubai Marina with flexible timing and professional driver.',
                'transfer_type'    => 'Round-trip',
                'pickup_location'  => 'Burj Khalifa',
                'dropoff_location' => 'Dubai Marina',
                'pickup_place_id'  => $dubaiPlaces['burj-khalifa'] ?? null,
                'dropoff_place_id' => $dubaiPlaces['dubai-marina'] ?? null,
                'vehicle_type'     => 'Luxury Sedan',
                'inclusion'        => 'Professional driver, WiFi, refreshments, waiting time included',
                'seo'              => [
                    'meta_title'       => 'Burj Khalifa to Dubai Marina Round Trip',
                    'meta_description' => 'Round-trip luxury transfer between Downtown and Marina.',
                    'keywords'         => 'dubai, burj khalifa, marina, round trip',
                    'og_image_url'     => 'https://example.com/og-dubai-roundtrip.jpg',
                    'canonical_url'    => 'https://example.com/burj-khalifa-marina-roundtrip',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode(['@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'Burj Khalifa to Dubai Marina Round Trip']),
                ],
            ],
            [
                'name'             => 'Palm Jumeirah to Burj Khalifa',
                'slug'             => 'palm-jumeirah-to-burj-khalifa',
                'description'      => 'Direct transfer from Palm Jumeirah resorts to Burj Khalifa and Downtown Dubai in an air-conditioned vehicle.',
                'transfer_type'    => 'One-way',
                'pickup_location'  => 'Palm Jumeirah',
                'dropoff_location' => 'Burj Khalifa',
                'pickup_place_id'  => $dubaiPlaces['palm-jumeirah'] ?? null,
                'dropoff_place_id' => $dubaiPlaces['burj-khalifa'] ?? null,
                'vehicle_type'     => 'Sedan',
                'inclusion'        => 'AC vehicle, bottled water, English-speaking driver',
                'seo'              => [
                    'meta_title'       => 'Palm Jumeirah to Burj Khalifa Transfer',
                    'meta_description' => 'Private transfer from Palm Jumeirah to Burj Khalifa and Downtown.',
                    'keywords'         => 'palm jumeirah, burj khalifa, downtown, transfer',
                    'og_image_url'     => 'https://example.com/og-palm-burj.jpg',
                    'canonical_url'    => 'https://example.com/palm-jumeirah-to-burj-khalifa',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode(['@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'Palm Jumeirah to Burj Khalifa']),
                ],
            ],
            [
                'name'             => 'Dubai Creek to Dubai Mall',
                'slug'             => 'dubai-creek-to-dubai-mall',
                'description'      => 'Transfer from historic Dubai Creek and the souks to Dubai Mall for a modern shopping experience.',
                'transfer_type'    => 'One-way',
                'pickup_location'  => 'Dubai Creek',
                'dropoff_location' => 'Dubai Mall',
                'pickup_place_id'  => $dubaiPlaces['dubai-creek'] ?? null,
                'dropoff_place_id' => $dubaiPlaces['dubai-mall'] ?? null,
                'vehicle_type'     => 'Sedan',
                'inclusion'        => 'AC vehicle, bottled water',
                'seo'              => [
                    'meta_title'       => 'Dubai Creek to Dubai Mall Transfer',
                    'meta_description' => 'Comfortable transfer from Dubai Creek souks to Dubai Mall.',
                    'keywords'         => 'dubai creek, dubai mall, souks, transfer',
                    'og_image_url'     => 'https://example.com/og-creek-mall.jpg',
                    'canonical_url'    => 'https://example.com/dubai-creek-to-dubai-mall',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode(['@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'Dubai Creek to Dubai Mall']),
                ],
            ],
            [
                'name'             => 'Dubai Mall to Burj Khalifa Round Trip',
                'slug'             => 'dubai-mall-to-burj-khalifa-round-trip',
                'description'      => 'Round-trip shuttle between Dubai Mall and Burj Khalifa with flexible return timing for shopping and sightseeing.',
                'transfer_type'    => 'Round-trip',
                'pickup_location'  => 'Dubai Mall',
                'dropoff_location' => 'Burj Khalifa',
                'pickup_place_id'  => $dubaiPlaces['dubai-mall'] ?? null,
                'dropoff_place_id' => $dubaiPlaces['burj-khalifa'] ?? null,
                'vehicle_type'     => 'SUV',
                'inclusion'        => 'AC vehicle, bottled water, waiting time included',
                'seo'              => [
                    'meta_title'       => 'Dubai Mall to Burj Khalifa Round Trip',
                    'meta_description' => 'Round-trip transfer between Dubai Mall and Burj Khalifa.',
                    'keywords'         => 'dubai mall, burj khalifa, round trip, transfer',
                    'og_image_url'     => 'https://example.com/og-mall-burj.jpg',
                    'canonical_url'    => 'https://example.com/dubai-mall-to-burj-khalifa-round-trip',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode(['@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'Dubai Mall to Burj Khalifa Round Trip']),
                ],
            ],
        ];

        foreach ($transfers as $data) {
            $transfer = Transfer::create([
                'name'          => $data['name'],
                'slug'          => $data['slug'],
                'description'   => $data['description'],
                'transfer_type' => $data['transfer_type'],
            ]);

            // Admin-managed route (is_vendor = false)
            TransferVendorRoute::create([
                'transfer_id'      => $transfer->id,
                'is_vendor'        => false,
                'vendor_id'        => null,
                'route_id'         => null,
                'pickup_location'  => $data['pickup_location'],
                'dropoff_location' => $data['dropoff_location'],
                'pickup_place_id'  => $data['pickup_place_id'],
                'dropoff_place_id' => $data['dropoff_place_id'],
                'vehicle_type'     => $data['vehicle_type'],
                'inclusion'        => $data['inclusion'],
            ]);

            // Media Gallery - 3-4 random images
            foreach (Arr::random($mediaIds, rand(3, 4)) as $mediaId) {
                TransferMediaGallery::create([
                    'transfer_id' => $transfer->id,
                    'media_id'    => $mediaId,
                ]);
            }

            // SEO
            TransferSeo::create([
                'transfer_id'      => $transfer->id,
                'meta_title'       => $data['seo']['meta_title'],
                'meta_description' => $data['seo']['meta_description'],
                'keywords'         => $data['seo']['keywords'],
                'og_image_url'     => $data['seo']['og_image_url'],
                'canonical_url'    => $data['seo']['canonical_url'],
                'schema_type'      => $data['seo']['schema_type'],
                'schema_data'      => $data['seo']['schema_data'],
            ]);
        }
    }
}
