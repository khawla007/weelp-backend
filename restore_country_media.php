<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Restoring Country-Media relationships...\n\n";

// Get all countries keyed by name
$countries = \App\Models\Country::all()->keyBy('name');
echo "Found " . $countries->count() . " countries\n";

// Get media records with country names
$mediaRecords = \App\Models\Media::where('name', 'like', '% - Image %')->get();
echo "Found " . $mediaRecords->count() . " media records\n\n";

$attached = 0;
$notFound = 0;

foreach ($mediaRecords as $media) {
    // Extract country name from media name (e.g., 'United Arab Emirates - Image 1')
    $parts = explode(' - Image ', $media->name);
    if (count($parts) >= 2) {
        $countryName = trim($parts[0]);

        if (isset($countries[$countryName])) {
            $country = $countries[$countryName];

            // Check if already attached
            $exists = DB::table('country_media_gallery')
                ->where('country_id', $country->id)
                ->where('media_id', $media->id)
                ->exists();

            if (!$exists) {
                // Set first image as featured
                $isFeatured = !DB::table('country_media_gallery')
                    ->where('country_id', $country->id)
                    ->where('is_featured', true)
                    ->exists();

                DB::table('country_media_gallery')->insert([
                    'country_id' => $country->id,
                    'media_id' => $media->id,
                    'is_featured' => $isFeatured,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $attached++;
                echo "✓ Attached: {$media->name} → {$countryName}\n";
            }
        } else {
            $notFound++;
            echo "✗ Country not found: {$countryName}\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "Total attachments created: {$attached}\n";
echo "Countries not found: {$notFound}\n";
echo "CountryMediaGallery count: " . DB::table('country_media_gallery')->count() . "\n";
