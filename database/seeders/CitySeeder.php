<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
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
    public function run()
    {
        // 1️⃣ Insert Cities
        $cities = [
            // Rajasthan (state_id = 1)
            [
                'state_id' => 1, // Rajasthan
                'name' => 'Jaipur',
                'code' => 'JA',
                'slug' => 'jaipur',
                'type' => 'city',
                'description' => 'The Pink City of India.',
                'feature_image' => 'https://example.com/jaipur.jpg',
                'featured_destination' => true,
            ],
            [
                'state_id' => 1,
                'name' => 'Udaipur',
                'code' => 'UD',
                'slug' => 'udaipur',
                'type' => 'city',
                'description' => 'The City of Lakes.',
                'feature_image' => 'https://example.com/udaipur.jpg',
                'featured_destination' => false,
            ],
            [
                'state_id' => 1,
                'name' => 'Bihad',
                'code' => 'BI',
                'slug' => 'bihad',
                'type' => 'city',
                'description' => 'A historic region.',
                'feature_image' => 'https://example.com/bihad.jpg',
                'featured_destination' => false,
            ],
            [
                'state_id' => 1,
                'name' => 'Jeend',
                'code' => 'JE',
                'slug' => 'jeend',
                'type' => 'city',
                'description' => 'A cultural city in Rajasthan.',
                'feature_image' => 'https://example.com/jeend.jpg',
                'featured_destination' => false,
            ],
        
            // Île-de-France (state_id = 6)
            [
                'state_id' => 6,
                'name' => 'Paris',
                'code' => 'PAR',
                'slug' => 'paris',
                'type' => 'city',
                'description' => 'The capital city of France, known as the City of Light.',
                'feature_image' => 'https://example.com/paris.jpg',
                'featured_destination' => true,
            ],
            [
                'state_id' => 6,
                'name' => 'Versailles',
                'code' => 'VER',
                'slug' => 'versailles',
                'type' => 'city',
                'description' => 'Famous for the Palace of Versailles.',
                'feature_image' => 'https://example.com/versailles.jpg',
                'featured_destination' => true,
            ],
            [
                'state_id' => 6,
                'name' => 'Boulogne-Billancourt',
                'code' => 'BB',
                'slug' => 'boulogne-billancourt',
                'type' => 'city',
                'description' => 'A major suburb of Paris.',
                'feature_image' => 'https://example.com/boulogne.jpg',
                'featured_destination' => false,
            ],
            [
                'state_id' => 6,
                'name' => 'Saint-Denis',
                'code' => 'SD',
                'slug' => 'saint-denis',
                'type' => 'city',
                'description' => 'Known for the Basilica of Saint-Denis.',
                'feature_image' => 'https://example.com/saint-denis.jpg',
                'featured_destination' => false,
            ],
        ];        

        $mediaIds = range(1, 5);

        foreach ($cities as $data) {
            $city = City::create($data);

            $randomMedias = collect($mediaIds)->random(3);
            foreach ($randomMedias as $mediaId) {
                CityMediaGallery::create([
                    'city_id'   => $city->id,
                    'media_id'  => $mediaId,
                ]);
            }

            // 2️⃣ Insert City Location Details
            CityLocationDetail::create([
                'city_id' => $city->id,
                'latitude' => '26.9124',
                'longitude' => '75.7873',
                'population' => 3000000,
                'currency' => 'INR',
                'timezone' => 'GMT+5:30',
                'language' => ['Hindi', 'Rajasthani'],
                'local_cuisine' => ['Dal Baati', 'Churma', 'Ghewar']
            ]);

            // 3️⃣ Insert Travel Information
            CityTravelInfo::create([
                'city_id' => $city->id,
                'airport' => 'Jaipur International Airport',
                'public_transportation' => ['Buses', 'Rickshaws', 'Metro'],
                'taxi_available' => true,
                'rental_cars_available' => true,
                'hotels' => true,
                'hostels' => true,
                'apartments' => true,
                'resorts' => true,
                'visa_requirements' => 'No visa required for Indian citizens',
                'best_time_to_visit' => 'October - March',
                'travel_tips' => 'Wear comfortable walking shoes',
                'safety_information' => 'Safe but beware of pickpockets in crowded areas'
            ]);

            // 4️⃣ Insert Seasons
            CitySeason::create([
                'city_id' => $city->id,
                'name' => 'Winter',
                'months' => ['November', 'February'],
                'weather' => 'Cool and pleasant',
                'activities' => ['Heritage Walks', 'Sightseeing', 'Shopping']
            ]);

            // 5️⃣ Insert Events
            CityEvent::create([
                'city_id' => $city->id,
                'name' => 'Jaipur Literature Festival',
                'type' => ['Cultural', 'Festival'],
                'date' => '2025-01-21',
                'location' => 'Jaipur, Rajasthan',
                'description' => 'A gathering of authors, thinkers, and readers from across the world.'
            ]);

            // 6️⃣ Insert Additional Information
            CityAdditionalInfo::create([
                'city_id' => $city->id,
                'title' => 'Must-Visit Places',
                'content' => 'Hawa Mahal, City Palace, Amer Fort, Jal Mahal'
            ]);

            $cityId = $city->id;

            $lastQuestion = CityFaq::where('city_id', $cityId)
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
                CityFaq::create([
                    'city_id' => $city->id,
                    'question_number' => $questionNumber,
                    'question' => $faq['question'],
                    'answer' => $faq['answer']
                ]);
                $questionNumber++;
            }

            // 8️⃣ Insert SEO Data
            CitySeo::create([
                'city_id' => $city->id,
                'meta_title' => 'Explore Jaipur - The Pink City',
                'meta_description' => 'Discover the rich heritage of Jaipur, Rajasthan.',
                'keywords' => 'Jaipur, Rajasthan, Pink City, Amer Fort, Hawa Mahal',
                'og_image_url' => 'https://example.com/og-jaipur.jpg',
                'canonical_url' => 'https://example.com/jaipur',
                'schema_type' => 'TravelDestination',
                'schema_data' => [
                    "@context" => "https://schema.org",
                    "@type" => "TravelDestination",
                    "name" => "Jaipur",
                    "description" => "The capital of Rajasthan, known for its royal heritage.",
                    "image" => "https://example.com/jaipur.jpg"
                ],
            ]);
        }
    }
}
