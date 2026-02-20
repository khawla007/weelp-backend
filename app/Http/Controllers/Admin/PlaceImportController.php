<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Place;
use App\Models\PlaceLocationDetail;
use App\Models\PlaceTravelInfo;
use App\Models\PlaceSeason;
use App\Models\PlaceEvent;
use App\Models\PlaceAdditionalInfo;
use App\Models\PlaceFaq;
use App\Models\PlaceSeo;

class PlaceImportController extends Controller
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

        // Track cities that have already been processed for SEO
        $processedPlaces = [];

        while ($row = fgetcsv($file)) {
            // Skip rows with insufficient columns
            if (count($row) < 47) { // Ensure there are at least 46 columns
                continue;
            }

            // Skip rows with malformed data (e.g., JavaScript or HTML)
            if ($this->isRowMalformed($row)) {
                continue;
            }

            // Finding ccity_id from the cities table by sheet's city code/city name
            $city = City::where('name', $this->sanitizeInput($row[3]))
            ->orWhere('city_code', $this->sanitizeInput($row[3]))
            ->first();

            if (!$city) {
                return response()->json(['message' => 'City ID not exist!'], 404);
            }

            // Sanitize and validate the place_code
            $placeCode = $this->sanitizeInput($row[1]);
            if (empty($placeCode)) {
                continue; // Skip this row if the place_code is empty
            }

            // Check if the Place already exists
            $place = Place::firstOrCreate(
                [
                    'place_code' => $placeCode, // Use place_code as the unique key
                ],
                [
                    'name' => $this->sanitizeInput($row[0]),
                    'slug' => $this->sanitizeInput($row[2]),
                    'city_id' => $city->id,
                    'description' => $this->sanitizeInput($row[4]),
                    'feature_image' => $this->sanitizeInput($row[5]),
                    'featured_destination' => filter_var($this->sanitizeInput($row[6]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                ]
            );

            // Insert into the `place_details` table
            PlaceLocationDetail::firstOrCreate(
                ['place_id' => $place->id],
                [
                    'latitude'      => $this->sanitizeInput($row[7]),
                    'longitude'     => $this->sanitizeInput($row[8]),
                    'capital_city'  => $this->sanitizeInput($row[9]),
                    'population'    => $this->sanitizeInput($row[10]),
                    'currency'      => $this->sanitizeInput($row[11]),
                    'timezone'      => $this->sanitizeInput($row[12]),
                    'language'      => $this->sanitizeInput($row[13]),
                    'local_cuisine' => $this->sanitizeInput($row[14]),
                ]
            );

            // Insert into the `place_travel_info` table
            PlaceTravelInfo::firstOrCreate(
                ['place_id' => $place->id],
                [
                    'airport'               => $this->sanitizeInput($row[15]),
                    'public_transportation' => $this->sanitizeInput($row[16]),
                    'taxi_available'        => filter_var($this->sanitizeInput($row[17]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                    'rental_cars_available' => filter_var($this->sanitizeInput($row[18]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                    'hotels'                => filter_var($this->sanitizeInput($row[19]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                    'hostels'               => filter_var($this->sanitizeInput($row[20]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                    'apartments'            => filter_var($this->sanitizeInput($row[21]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                    'resorts'               => filter_var($this->sanitizeInput($row[22]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                    'visa_requirements'     => $this->sanitizeInput($row[23]),
                    'best_time_to_visit'    => $this->sanitizeInput($row[24]),
                    'travel_tips'           => $this->sanitizeInput($row[25]),
                    'safety_information'    => $this->sanitizeInput($row[26]),
                ]
            );

            // Insert multiple seasons
            $seasons = explode('|', $this->sanitizeInput($row[27]));
            $months = explode('|', $this->sanitizeInput($row[28]));
            $weather = explode('|', $this->sanitizeInput($row[29]));
            $activities = explode('|', $this->sanitizeInput($row[30]));

            foreach ($seasons as $index => $season) {
                // Skip if the season name is empty
                if (empty($season)) {
                    continue;
                }

                PlaceSeason::updateOrCreate(
                    [
                        'place_id' => $place->id,
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
            $eventNames = explode('|', $this->sanitizeInput($row[31]));
            $eventTypes = explode('|', $this->sanitizeInput($row[32]));
            $eventDates = explode('|', $this->sanitizeInput($row[33]));
            $eventLocations = explode('|', $this->sanitizeInput($row[34]));
            $eventDescriptions = explode('|', $this->sanitizeInput($row[35]));

            foreach ($eventNames as $index => $eventName) {
                // Skip if the event name is empty
                if (empty($eventName)) {
                    continue;
                }

                // Ensure date_time is either a valid datetime or null
                $dateTime = $this->sanitizeInput($eventDates[$index] ?? '');
                $dateTime = $this->validateDateTime($dateTime);

                PlaceEvent::updateOrCreate(
                    [
                        'place_id' => $place->id,
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
            $additionalTitles = explode('|', $this->sanitizeInput($row[36]));
            $additionalContents = explode('|', $this->sanitizeInput($row[37]));

            foreach ($additionalTitles as $index => $title) {
                // Skip if the title is empty
                if (empty($title)) {
                    continue;
                }

                PlaceAdditionalInfo::updateOrCreate(
                    [
                        'place_id' => $place->id,
                        'title' => $title,
                    ],
                    [
                        'content' => $additionalContents[$index] ?? '',
                    ]
                );
            }

            // Insert multiple FAQs
            $faqQuestions = explode('|', $this->sanitizeInput($row[38]));
            $faqAnswers = explode('|', $this->sanitizeInput($row[39]));
            
            // Fetch the last question number from the database
            $lastQuestion = PlaceFaq::where('place_id', $place->id)->orderBy('question_number', 'desc')->first();
            $questionNumber = $lastQuestion ? $lastQuestion->question_number + 1 : 1;

            foreach ($faqQuestions as $index => $question) {
                // Skip if the question is empty
                if (empty($question)) {
                    continue;
                }
                
                PlaceFaq::updateOrCreate(
                    [
                        'place_id' => $place->id,
                        'question' => $question,
                    ],
                    [
                        'question_number' => $questionNumber,
                        'answer' => $faqAnswers[$index] ?? '',
                    ]
                );
                $questionNumber++;
            }

            // Insert into the `place_seo` table (only once per place)
            if (!in_array($place->id, $processedPlaces)) {
                $metaTitle = $this->sanitizeInput($row[40]);
                $metaDescription = $this->sanitizeInput($row[41]);
                $keywords = $this->sanitizeInput($row[42]);
                $ogImageUrl = $this->sanitizeInput($row[43]);
                $canonicalUrl = $this->sanitizeInput($row[44]);
                $schemaType = $this->sanitizeInput($row[45]);
                $schemaData = $this->sanitizeInput($row[46]);

                // Only insert/update SEO data if required fields are not null
                if ($metaTitle && $metaDescription) {
                    PlaceSeo::updateOrCreate(
                        ['place_id' => $place->id], // Unique key to ensure single entry
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

                    // Mark this Place as processed for SEO
                    $processedPlaces[] = $place->id;
                }
            }
        }

        fclose($file);

        // Delete the temporary file
        unlink($tempFilePath);

        return response()->json(['message' => 'Places imported successfully!'], 200);
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