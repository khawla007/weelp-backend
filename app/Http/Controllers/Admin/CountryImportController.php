<?php

// namespace App\Http\Controllers\Admin;

// use App\Http\Controllers\Controller;

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Validator;
// use Illuminate\Support\Facades\Http;
// use App\Models\Country;
// use App\Models\CountryLocationDetail;
// use App\Models\CountryTravelInfo;
// use App\Models\CountrySeason;
// use App\Models\CountryEvent;
// use App\Models\CountryAdditionalInfo;
// use App\Models\CountryFaq;
// use App\Models\CountrySeo;

// class CountryImportController extends Controller
// {
//     public function import(Request $request)
//     {
//         // Validate the request
//         $validator = Validator::make($request->all(), [
//             'file' => 'required|url',
//         ]);
    
//         if ($validator->fails()) {
//             return response()->json(['error' => $validator->errors()], 400);
//         }
    
//         // Get the file URL from the request
//         $fileUrl = $request->input('file');
    
//         // Download the file
//         $response = Http::get($fileUrl);
    
//         if (!$response->successful()) {
//             return response()->json(['error' => 'Failed to download the file.'], 400);
//         }
    
//         // Save the file temporarily
//         $tempFilePath = tempnam(sys_get_temp_dir(), 'csv');
//         file_put_contents($tempFilePath, $response->body());
    
//         // Process the file
//         $file = fopen($tempFilePath, 'r');
//         $header = fgetcsv($file); // Skip the header row
    
//         while ($row = fgetcsv($file)) {
//             // Skip rows with insufficient columns
//             if (count($row) < 46) { // Ensure there are at least 46 columns
//                 continue;
//             }
    
//             // Skip rows with malformed data (e.g., JavaScript or HTML)
//             if ($this->isRowMalformed($row)) {
//                 continue;
//             }
    
//             // Insert into the `countries` table
//             $country = Country::create([
//                 'name' => $this->sanitizeInput($row[0]),
//                 'country_code' => $this->sanitizeInput($row[1]),
//                 'slug' => $this->sanitizeInput($row[2]),
//                 'description' => $this->sanitizeInput($row[3]),
//                 'feature_image' => $this->sanitizeInput($row[4]),
//                 'featured_destination' => filter_var($this->sanitizeInput($row[5]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
//             ]);
    
//             // Insert into the `country_details` table
//             CountryLocationDetail::create([
//                 'country_id' => $country->id,
//                 'latitude' => $this->sanitizeInput($row[6]),
//                 'longitude' => $this->sanitizeInput($row[7]),
//                 'capital_city' => $this->sanitizeInput($row[8]),
//                 'population' => $this->sanitizeInput($row[9]),
//                 'currency' => $this->sanitizeInput($row[10]),
//                 'timezone' => $this->sanitizeInput($row[11]),
//                 'language' => $this->sanitizeInput($row[12]),
//                 'local_cuisine' => $this->sanitizeInput($row[13]),
//             ]);
    
//             // Insert into the `country_travel_info` table
//             CountryTravelInfo::create([
//                 'country_id' => $country->id,
//                 'airport' => $this->sanitizeInput($row[14]),
//                 'public_transportation' => $this->sanitizeInput($row[15]),

//                 'taxi_available' => filter_var($this->sanitizeInput($row[16]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
//                 'rental_cars_available' => filter_var($this->sanitizeInput($row[17]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
//                 'hotels' => filter_var($this->sanitizeInput($row[18]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
//                 'hostels' => filter_var($this->sanitizeInput($row[19]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
//                 'apartments' => filter_var($this->sanitizeInput($row[20]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
//                 'resorts' => filter_var($this->sanitizeInput($row[21]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,

//                 'visa_requirements' => $this->sanitizeInput($row[22]),
//                 'best_time_to_visit' => $this->sanitizeInput($row[23]),
//                 'travel_tips' => $this->sanitizeInput($row[24]),
//                 'safety_information' => $this->sanitizeInput($row[25]),
//             ]);
    
//             // Insert into the `country_seasons` table
//             // CountrySeason::create([
//             //     'country_id' => $country->id,
//             //     'name' => $this->sanitizeInput($row[26]),
//             //     'months' => $this->sanitizeInput($row[27]),
//             //     'weather' => $this->sanitizeInput($row[28]),
//             //     'activities' => $this->sanitizeInput($row[29]),
//             // ]);

//             // Insert multiple seasons
//             $seasons = explode('|', $this->sanitizeInput($row[26]));
//             $months = explode('|', $this->sanitizeInput($row[27]));
//             $weather = explode('|', $this->sanitizeInput($row[28]));
//             $activities = explode('|', $this->sanitizeInput($row[29]));

//             foreach ($seasons as $index => $season) {
//                 CountrySeason::create([
//                     'country_id' => $country->id,
//                     'name' => $season,
//                     'months' => $months[$index] ?? '',
//                     'weather' => $weather[$index] ?? '',
//                     'activities' => $activities[$index] ?? '',
//                 ]);
//             }
    
//             // Insert into the `country_events` table
//             // CountryEvent::create([
//             //     'country_id' => $country->id,
//             //     'name' => $this->sanitizeInput($row[30]),
//             //     'type' => $this->sanitizeInput($row[31]),
//             //     'date_time' => $this->sanitizeInput($row[32]),
//             //     'location' => $this->sanitizeInput($row[33]),
//             //     'description' => $this->sanitizeInput($row[34]),
//             // ]);

//             // Insert multiple events
//             $eventNames = explode('|', $this->sanitizeInput($row[30]));
//             $eventTypes = explode('|', $this->sanitizeInput($row[31]));
//             $eventDates = explode('|', $this->sanitizeInput($row[32]));
//             $eventLocations = explode('|', $this->sanitizeInput($row[33]));
//             $eventDescriptions = explode('|', $this->sanitizeInput($row[34]));

//             foreach ($eventNames as $index => $eventName) {
//                 CountryEvent::create([
//                     'country_id' => $country->id,
//                     'name' => $eventName,
//                     'type' => $eventTypes[$index] ?? '',
//                     'date_time' => $eventDates[$index] ?? '',
//                     'location' => $eventLocations[$index] ?? '',
//                     'description' => $eventDescriptions[$index] ?? '',
//                 ]);
//             }
    
//             // Insert into the `country_additional_info` table
//             // CountryAdditionalInfo::create([
//             //     'country_id' => $country->id,
//             //     'title' => $this->sanitizeInput($row[35]),
//             //     'content' => $this->sanitizeInput($row[36]),
//             // ]);

//             // Insert multiple additional info
//             $additionalTitles = explode('|', $this->sanitizeInput($row[35]));
//             $additionalContents = explode('|', $this->sanitizeInput($row[36]));

//             foreach ($additionalTitles as $index => $title) {
//                 CountryAdditionalInfo::create([
//                     'country_id' => $country->id,
//                     'title' => $title,
//                     'content' => $additionalContents[$index] ?? '',
//                 ]);
//             }
    
//             // Insert into the `country_faqs` table
//             // CountryFaq::create([
//             //     'country_id' => $country->id,
//             //     'question' => $this->sanitizeInput($row[37]),
//             //     'answer' => $this->sanitizeInput($row[38]),
//             // ]);

//             // Insert multiple FAQs
//             $faqQuestions = explode('|', $this->sanitizeInput($row[37]));
//             $faqAnswers = explode('|', $this->sanitizeInput($row[38]));

//             foreach ($faqQuestions as $index => $question) {
//                 CountryFaq::create([
//                     'country_id' => $country->id,
//                     'question' => $question,
//                     'answer' => $faqAnswers[$index] ?? '',
//                 ]);
//             }
    
//             // Insert into the `country_seo` table
//             CountrySeo::create([
//                 'country_id' => $country->id,
//                 'meta_title' => $this->sanitizeInput($row[39]),
//                 'meta_description' => $this->sanitizeInput($row[40]),
//                 'keywords' => $this->sanitizeInput($row[41]),
//                 'og_image_url' => $this->sanitizeInput($row[42]),
//                 'canonical_url' => $this->sanitizeInput($row[43]),
//                 'schema_type' => $this->sanitizeInput($row[44]),
//                 'schema_data' => json_decode($this->sanitizeInput($row[45]), true),
//             ]);
//         }
    
//         fclose($file);
    
//         // Delete the temporary file
//         unlink($tempFilePath);
    
//         return response()->json(['message' => 'Countries imported successfully!'], 200);
//     }
    
//     /**
//      * Check if a row contains malformed data (e.g., JavaScript or HTML).
//      */
//     private function isRowMalformed(array $row): bool
//     {
//         foreach ($row as $value) {
//             if (strpos($value, '<script>') !== false || strpos($value, 'function(') !== false) {
//                 return true;
//             }
//         }
//         return false;
//     }
    
//     private function sanitizeInput(?string $value): ?string
//     {
//         return $value ? trim(strip_tags($value)) : null;
//     }
// }

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\Country;
use App\Models\CountryLocationDetail;
use App\Models\CountryTravelInfo;
use App\Models\CountrySeason;
use App\Models\CountryEvent;
use App\Models\CountryAdditionalInfo;
use App\Models\CountryFaq;
use App\Models\CountrySeo;

class CountryImportController extends Controller
{
    public function import(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'file' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Get the file URL from the request
        $fileUrl = $request->input('file');

        // Download the file
        $response = Http::get($fileUrl);

        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to download the file.'], 400);
        }

        // Save the file temporarily
        $tempFilePath = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempFilePath, $response->body());

        // Process the file
        $file = fopen($tempFilePath, 'r');
        $header = fgetcsv($file); // Skip the header row

        // Track countries that have already been processed for SEO
        $processedCountries = [];

        while ($row = fgetcsv($file)) {
            // Skip rows with insufficient columns
            if (count($row) < 46) { // Ensure there are at least 46 columns
                continue;
            }

            // Skip rows with malformed data (e.g., JavaScript or HTML)
            if ($this->isRowMalformed($row)) {
                continue;
            }

            // Check if the country already exists
            $country = Country::firstOrCreate(
                ['name' => $this->sanitizeInput($row[0])], // Unique key to check
                [
                    'country_code' => $this->sanitizeInput($row[1]),
                    'slug' => $this->sanitizeInput($row[2]),
                    'description' => $this->sanitizeInput($row[3]),
                    'feature_image' => $this->sanitizeInput($row[4]),
                    'featured_destination' => filter_var($this->sanitizeInput($row[5]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                ]
            );

            // Insert into the `country_details` table
            CountryLocationDetail::firstOrCreate(
                ['country_id' => $country->id],
                [
                    'latitude' => $this->sanitizeInput($row[6]),
                    'longitude' => $this->sanitizeInput($row[7]),
                    'capital_city' => $this->sanitizeInput($row[8]),
                    'population' => $this->sanitizeInput($row[9]),
                    'currency' => $this->sanitizeInput($row[10]),
                    'timezone' => $this->sanitizeInput($row[11]),
                    'language' => $this->sanitizeInput($row[12]),
                    'local_cuisine' => $this->sanitizeInput($row[13]),
                ]
            );

            // if (!in_array($country->id, $processedCountries)) {
            //     $latitude       = $this->sanitizeInput($row[6]);
            //     $longitude       = $this->sanitizeInput($row[7]);
            //     $capital_city   = $this->sanitizeInput($row[8]);
            //     $population     = $this->sanitizeInput($row[9]);
            //     $currency       = $this->sanitizeInput($row[10]);
            //     $timezone       = $this->sanitizeInput($row[11]);
            //     $language       = $this->sanitizeInput($row[12]);
            //     $local_cuisine  = $this->sanitizeInput($row[13]);

            //     // Only insert/update SEO data if required fields are not null
            //     if ($latitude && $longitude) {
            //         CountrySeo::updateOrCreate(
            //             ['country_id' => $country->id], // Unique key to ensure single entry
            //             [
            //                 'latitude' => $latitude,
            //                 'longitude' => $logitude,
            //                 'capital_city' => $capital_city,
            //                 'population' => $population ,
            //                 'currency' => $currency,
            //                 'timezone' => $timezone,
            //                 'language' => $language,
            //                 'local_cuisine' => $local_cuisine,
            //             ]
            //         );

            //         // Mark this country as processed for SEO
            //         $processedCountries[] = $country->id;
            //     }
            // }

            // Insert into the `country_travel_info` table
            CountryTravelInfo::firstOrCreate(
                ['country_id' => $country->id],
                [
                    'airport' => $this->sanitizeInput($row[14]),
                    'public_transportation' => $this->sanitizeInput($row[15]),
                    'taxi_available' => filter_var($this->sanitizeInput($row[16]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                    'rental_cars_available' => filter_var($this->sanitizeInput($row[17]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                    'hotels' => filter_var($this->sanitizeInput($row[18]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                    'hostels' => filter_var($this->sanitizeInput($row[19]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                    'apartments' => filter_var($this->sanitizeInput($row[20]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                    'resorts' => filter_var($this->sanitizeInput($row[21]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                    'visa_requirements' => $this->sanitizeInput($row[22]),
                    'best_time_to_visit' => $this->sanitizeInput($row[23]),
                    'travel_tips' => $this->sanitizeInput($row[24]),
                    'safety_information' => $this->sanitizeInput($row[25]),
                ]
            );

            // if (!in_array($country->id, $processedCountries)) {
            //     $airport                = $this->sanitizeInput($row[14]);
            //     $public_transportation  = $this->sanitizeInput($row[15]);
            //     $taxi_available         = filter_var($this->sanitizeInput($row[16]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            //     $rental_cars_available  = filter_var($this->sanitizeInput($row[17]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            //     $hotels                 = filter_var($this->sanitizeInput($row[18]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            //     $hostels                = filter_var($this->sanitizeInput($row[19]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            //     $apartments             = filter_var($this->sanitizeInput($row[20]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            //     $resorts                = filter_var($this->sanitizeInput($row[21]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            //     $visa_requirements      = $this->sanitizeInput($row[22]);
            //     $best_time_to_visit     = $this->sanitizeInput($row[23]);
            //     $travel_tips            = $this->sanitizeInput($row[24]);
            //     $safety_information     = $this->sanitizeInput($row[25]);

            //     // Only insert/update SEO data if required fields are not null
            //     if ($airport && $public_transportation) {
            //         CountrySeo::updateOrCreate(
            //             ['country_id' => $country->id], // Unique key to ensure single entry
            //             [
            //                 'airport'               => $airport,
            //                 'public_transportation' => $public_transportation,
            //                 'taxi_available'        => $taxi_available,
            //                 'rental_cars_available' => $rental_cars_available,
            //                 'hotels'                => $hotels,
            //                 'hostels'               => $hostels,
            //                 'apartments'            => $apartments,
            //                 'resorts'               => $resorts,
            //                 'visa_requirements'     => $visa_requirements,
            //                 'best_time_to_visit'    => $best_time_to_visit,
            //                 'travel_tips'           => $travel_tips,
            //                 'safety_information'    => $safety_information,
            //             ]
            //         );

            //         // Mark this country as processed for SEO
            //         $processedCountries[] = $country->id;
            //     }
            // }

            // Insert multiple seasons
            $seasons = explode('|', $this->sanitizeInput($row[26]));
            $months = explode('|', $this->sanitizeInput($row[27]));
            $weather = explode('|', $this->sanitizeInput($row[28]));
            $activities = explode('|', $this->sanitizeInput($row[29]));

            foreach ($seasons as $index => $season) {
                // Skip if the season name is empty
                if (empty($season)) {
                    continue;
                }

                CountrySeason::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'name' => $season,
                    ],
                    [
                        'months' => $months[$index] ?? '',
                        'weather' => $weather[$index] ?? '',
                        'activities' => $activities[$index] ?? '',
                    ]
                );
            }

            // Insert multiple events
            $eventNames = explode('|', $this->sanitizeInput($row[30]));
            $eventTypes = explode('|', $this->sanitizeInput($row[31]));
            $eventDates = explode('|', $this->sanitizeInput($row[32]));
            $eventLocations = explode('|', $this->sanitizeInput($row[33]));
            $eventDescriptions = explode('|', $this->sanitizeInput($row[34]));

            foreach ($eventNames as $index => $eventName) {
                // Skip if the event name is empty
                if (empty($eventName)) {
                    continue;
                }

                // Ensure date_time is either a valid datetime or null
                $dateTime = $this->sanitizeInput($eventDates[$index] ?? '');
                $dateTime = $this->validateDateTime($dateTime);

                CountryEvent::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'name' => $eventName,
                    ],
                    [
                        'type' => $eventTypes[$index] ?? '',
                        'date_time' => $dateTime,
                        'location' => $eventLocations[$index] ?? '',
                        'description' => $eventDescriptions[$index] ?? '',
                    ]
                );
            }

            // Insert multiple additional info
            $additionalTitles = explode('|', $this->sanitizeInput($row[35]));
            $additionalContents = explode('|', $this->sanitizeInput($row[36]));

            foreach ($additionalTitles as $index => $title) {
                // Skip if the title is empty
                if (empty($title)) {
                    continue;
                }

                CountryAdditionalInfo::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'title' => $title,
                    ],
                    [
                        'content' => $additionalContents[$index] ?? '',
                    ]
                );
            }

            // Insert multiple FAQs
            $faqQuestions = explode('|', $this->sanitizeInput($row[37]));
            $faqAnswers = explode('|', $this->sanitizeInput($row[38]));
            
            // Fetch the last question number from the database
            $lastQuestion = CountryFaq::where('country_id', $country->id)->orderBy('question_number', 'desc')->first();
            $questionNumber = $lastQuestion ? $lastQuestion->question_number + 1 : 1;

            foreach ($faqQuestions as $index => $question) {
                // Skip if the question is empty
                if (empty($question)) {
                    continue;
                }
                
                CountryFaq::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'question' => $question,
                    ],
                    [
                        'question_number' => $questionNumber,
                        'answer' => $faqAnswers[$index] ?? '',
                    ]
                );
                $questionNumber++;
            }

            // Insert into the `country_seo` table (only once per country)
            if (!in_array($country->id, $processedCountries)) {
                $metaTitle = $this->sanitizeInput($row[39]);
                $metaDescription = $this->sanitizeInput($row[40]);
                $keywords = $this->sanitizeInput($row[41]);
                $ogImageUrl = $this->sanitizeInput($row[42]);
                $canonicalUrl = $this->sanitizeInput($row[43]);
                $schemaType = $this->sanitizeInput($row[44]);
                $schemaData = $this->sanitizeInput($row[45]);

                // Only insert/update SEO data if required fields are not null
                if ($metaTitle && $metaDescription) {
                    CountrySeo::updateOrCreate(
                        ['country_id' => $country->id], // Unique key to ensure single entry
                        [
                            'meta_title' => $metaTitle,
                            'meta_description' => $metaDescription,
                            'keywords' => $keywords,
                            'og_image_url' => $ogImageUrl,
                            'canonical_url' => $canonicalUrl,
                            'schema_type' => $schemaType,
                            'schema_data' => $schemaData ? json_decode($schemaData, true) : null,
                        ]
                    );

                    // Mark this country as processed for SEO
                    $processedCountries[] = $country->id;
                }
            }
        }

        fclose($file);

        // Delete the temporary file
        unlink($tempFilePath);

        return response()->json(['message' => 'Countries imported successfully!'], 200);
    }

    /**
     * Check if a row contains malformed data (e.g., JavaScript or HTML).
     */
    private function isRowMalformed(array $row): bool
    {
        foreach ($row as $value) {
            if (strpos($value, '<script>') !== false || strpos($value, 'function(') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sanitize input data.
     */
    private function sanitizeInput(?string $value): ?string
    {
        return $value ? trim(strip_tags($value)) : null;
    }

    /**
     * Validate and format datetime.
     */
    private function validateDateTime(?string $dateTime): ?string
    {
        if (empty($dateTime)) {
            return null;
        }

        // Check if the datetime is in a valid format
        try {
            return \Carbon\Carbon::parse($dateTime)->toDateTimeString();
        } catch (\Exception $e) {
            return null;
        }
    }
}