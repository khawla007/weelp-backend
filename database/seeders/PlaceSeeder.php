<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Place;
use App\Models\PlaceMediaGallery;
use App\Models\PlaceLocationDetail;
use App\Models\PlaceTravelInfo;
use App\Models\PlaceSeason;
use App\Models\PlaceEvent;
use App\Models\PlaceAdditionalInfo;
use App\Models\PlaceFaq;
use App\Models\PlaceSeo;

class PlaceSeeder extends Seeder
{
    public function run()
    {
        // 1️ Insert Places
        $places = [
            [
                'city_id' => 1, // Jaipur
                'name' => 'Amber Fort',
                'code' => 'AF',
                'slug' => 'amber-fort',
                'type' => 'place',
                'description' => 'A magnificent fort known for its artistic Hindu style elements.',
                'feature_image' => 'https://example.com/amber-fort.jpg',
                'featured_destination' => true,
            ],
            [
                'city_id' => 1,
                'name' => 'Hawa Mahal',
                'code' => 'HM',
                'slug' => 'hawa-mahal',
                'type' => 'place',
                'description' => 'A palace made of red and pink sandstone, also called the Palace of Winds.',
                'feature_image' => 'https://example.com/hawa-mahal.jpg',
                'featured_destination' => false,
            ]
        ];

        $mediaIds = range(1, 5);
        
        foreach ($places as $data) {
            $place = Place::create($data);

            $randomMedias = collect($mediaIds)->random(3);
            foreach ($randomMedias as $mediaId) {
                PlaceMediaGallery::create([
                    'place_id'   => $place->id,
                    'media_id'  => $mediaId,
                ]);
            }

            // 2️ Insert Place Location Details
            PlaceLocationDetail::create([
                'place_id' => $place->id,
                'latitude' => '26.9855',
                'longitude' => '75.8513',
                'population' => 100000,
                'currency' => 'INR',
                'timezone' => 'GMT+5:30',
                'language' => ['Hindi', 'Rajasthani'],
                'local_cuisine' => ['Dal Baati', 'Churma', 'Ghewar']
            ]);

            // 3️ Insert Travel Information
            PlaceTravelInfo::create([
                'place_id' => $place->id,
                'airport' => 'Jaipur International Airport',
                'public_transportation' => ['Buses', 'Rickshaws', 'Metro'],
                'taxi_available' => true,
                'rental_cars_available' => true,
                'hotels' => true,
                'hostels' => false,
                'apartments' => true,
                'resorts' => true,
                'visa_requirements' => 'No visa required for Indian citizens',
                'best_time_to_visit' => 'October - March',
                'travel_tips' => 'Wear comfortable shoes for walking around the fort.',
                'safety_information' => 'Safe but avoid isolated areas at night.'
            ]);

            // 4️ Insert Seasons
            PlaceSeason::create([
                'place_id' => $place->id,
                'name' => 'Winter',
                'months' => ['November', 'February'],
                'weather' => 'Cool and perfect for sightseeing',
                'activities' => ['Heritage Walks', 'Sightseeing', 'Shopping']
            ]);

            // 5️ Insert Events
            PlaceEvent::create([
                'place_id' => $place->id,
                'name' => 'Light & Sound Show',
                'type' => ['Cultural', 'Festival'],
                'date' => '2025-01-21',
                'location' => 'Amber Fort, Jaipur',
                'description' => 'A historical show depicting the history of Amber Fort.'
            ]);

            // 6️ Insert Additional Information
            PlaceAdditionalInfo::create([
                'place_id' => $place->id,
                'title' => 'Interesting Facts',
                'content' => 'Amber Fort was built in 1592 and is a UNESCO World Heritage Site.'
            ]);


            $placeId = $place->id;

            $lastQuestion = PlaceFaq::where('place_id', $placeId)
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
                PlaceFaq::create([
                    'place_id' => $place->id,
                    'question_number' => $questionNumber,
                    'question' => $faq['question'],
                    'answer' => $faq['answer']
                ]);
                $questionNumber++;
            }

            // 8️ Insert SEO Data
            PlaceSeo::create([
                'place_id' => $place->id,
                'meta_title' => 'Explore Amber Fort - Jaipur',
                'meta_description' => 'Amber Fort, a magnificent palace known for its artistic architecture.',
                'keywords' => 'Amber Fort, Jaipur, Rajasthan, Historical Places, Travel',
                'og_image_url' => 'https://example.com/og-amber-fort.jpg',
                'canonical_url' => 'https://example.com/amber-fort',
                'schema_type' => 'TravelDestination',
                'schema_data' => [
                    "@context" => "https://schema.org",
                    "@type" => "TravelDestination",
                    "name" => "Amber Fort",
                    "description" => "A historical fort in Jaipur, Rajasthan.",
                    "image" => "https://example.com/amber-fort.jpg"
                ],
            ]);
        }
    }
}
