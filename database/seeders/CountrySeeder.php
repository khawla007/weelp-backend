<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\CountryMediaGallery;
use App\Models\CountryLocationDetail;
use App\Models\CountryTravelInfo;
use App\Models\CountrySeason;
use App\Models\CountryEvent;
use App\Models\CountryAdditionalInfo;
use App\Models\CountryFaq;
use App\Models\CountrySeo;

class CountrySeeder extends Seeder
{
    public function run()
    {
        $countries = [
            [
                'name' => 'India',
                'code' => 'IN',
                'slug' => 'india',
                'type' => 'country',
                'description' => 'India details',
                'feature_image' => 'https://example.com/india.jpg',
                'featured_destination' => false,
            ],
            [
                'name' => 'China',
                'code' => 'CN',
                'slug' => 'china',
                'type' => 'country',
                'description' => 'A beautiful country',
                'feature_image' => 'https://example.com/china.jpg',
                'featured_destination' => true,
            ],
            [
                'name' => 'Japan',
                'code' => 'JP',
                'slug' => 'japan',
                'type' => 'country',
                'description' => 'Land of the Rising Sun',
                'feature_image' => 'https://example.com/japan.jpg',
                'featured_destination' => true,
            ],
            [
                'name' => 'USA',
                'code' => 'US',
                'slug' => 'usa',
                'type' => 'country',
                'description' => 'United States of America',
                'feature_image' => 'https://example.com/usa.jpg',
                'featured_destination' => true,
            ],
            [
                'name' => 'France',
                'code' => 'FR',
                'slug' => 'france',
                'type' => 'country',
                'description' => 'The country of love and Eiffel Tower',
                'feature_image' => 'https://example.com/france.jpg',
                'featured_destination' => true,
            ],
            [
                'name' => 'Germany',
                'code' => 'DE',
                'slug' => 'germany',
                'type' => 'country',
                'description' => 'Engineering and cultural hub',
                'feature_image' => 'https://example.com/germany.jpg',
                'featured_destination' => false,
            ],
            [
                'name' => 'Italy',
                'code' => 'IT',
                'slug' => 'italy',
                'type' => 'country',
                'description' => 'Famous for Rome and Venice',
                'feature_image' => 'https://example.com/italy.jpg',
                'featured_destination' => true,
            ],
            [
                'name' => 'Spain',
                'code' => 'ES',
                'slug' => 'spain',
                'type' => 'country',
                'description' => 'Land of Flamenco and Football',
                'feature_image' => 'https://example.com/spain.jpg',
                'featured_destination' => true,
            ],
            [
                'name' => 'Australia',
                'code' => 'AU',
                'slug' => 'australia',
                'type' => 'country',
                'description' => 'The land of Kangaroos',
                'feature_image' => 'https://example.com/australia.jpg',
                'featured_destination' => true,
            ],
            [
                'name' => 'Brazil',
                'code' => 'BR',
                'slug' => 'brazil',
                'type' => 'country',
                'description' => 'Carnival and Football country',
                'feature_image' => 'https://example.com/brazil.jpg',
                'featured_destination' => true,
            ],
        ];

        $mediaIds = range(1, 5);
    
        foreach ($countries as $data) {
            $country = Country::create($data);
    
            // Country_Media (Array of Objects )
            $randomMedias = collect($mediaIds)->random(3); // ek country ko 3 random media milega
            foreach ($randomMedias as $mediaId) {
                CountryMediaGallery::create([
                    'country_id' => $country->id,
                    'media_id'   => $mediaId,
                ]);
            }

            // Location Details
            CountryLocationDetail::create([
                'country_id' => $country->id,
                'latitude' => '20.5937',
                'longitude' => '78.9629',
                'capital_city' => 'Capital City Example',
                'population' => 1000000,
                'currency' => 'USD',
                'timezone' => 'GMT+5:30',
                'language' => ["English"],   // array
                'local_cuisine' => ["Cuisine1", "Cuisine2"], 
            ]);
    
            // Travel Info
            CountryTravelInfo::create([
                'country_id' => $country->id,
                'airport' => 'Main International Airport',
                'public_transportation' => ['Metro', 'Buses', 'Trains'],
                'taxi_available' => true,
                'rental_cars_available' => true,
                'hotels' => true,
                'hostels' => true,
                'apartments' => true,
                'resorts' => true,
                'visa_requirements' => 'Visa info',
                'best_time_to_visit' => 'October - March',
                'travel_tips' => 'Carry local currency',
                'safety_information' => 'Safe for tourists',
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
    
            // Events
            $events = [
                [
                    'name' => 'New Year Festival',
                    'type' => ['Festival', 'Holiday'],
                    'date' => '2025-01-01',
                    "location" => "kangra",
                    'description' => 'New Year celebrations',
                ],
                [
                    'name' => 'National Day',
                    "type"=> ["Festival", "Holiday"],
                    'date' => '2025-08-15',
                    "location"=> "hamirpur",
                    'description' => 'National holiday celebrations',
                ],
            ];
            foreach ($events as $event) {
                CountryEvent::create(array_merge($event, [
                    'country_id' => $country->id,
                ]));
            }
    
            // Additional Info (Array of Objects ✅)
            $additionalInfos = [
                [
                    'title' => 'Famous Tourist Attractions',
                    'content' => 'Monuments, Museums, National Parks',
                ],
                [
                    'title' => 'Popular Food',
                    'content' => 'Street food, Traditional Dishes',
                ],
                [
                    'title' => 'Culture',
                    'content' => 'Music, Dance, Art',
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
                    'question' => 'Do I need a visa?',
                    'answer' => 'Depends on your nationality.',
                ],
                [
                    'question' => 'What is the currency?',
                    'answer' => 'The official currency is local.',
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
                'meta_title' => 'Visit ' . $country->name,
                'meta_description' => 'Explore ' . $country->name,
                'keywords' => $country->name . ', Travel, Tourism',
                'og_image_url' => 'https://example.com/' . $country->slug . '.jpg',
                'canonical_url' => 'https://example.com/' . $country->slug,
                'schema_type' => 'TravelDestination',
                'schema_data' => [
                    "@context" => "https://schema.org",
                    "@type" => "TravelDestination",
                    "name" => $country->name,
                    "description" => "A beautiful country.",
                    "image" => 'https://example.com/' . $country->slug . '.jpg',
                ],
            ]);
        }
    }    
}
