<?php

/**
 * Quick Country Images Import Script
 * Run: php artisan tinker --execute="include 'scripts/import_country_images.php';"
 */
$imagesPerCountry = 5;

// Country data with direct image URLs (free to use)
$countryImages = [
    'United Arab Emirates' => [
        'https://picsum.photos/seed/uae1/1200/800',
        'https://picsum.photos/seed/uae2/1200/800',
        'https://picsum.photos/seed/uae3/1200/800',
        'https://picsum.photos/seed/uae4/1200/800',
        'https://picsum.photos/seed/uae5/1200/800',
    ],
    'Saudi Arabia' => [
        'https://picsum.photos/seed/saudi1/1200/800',
        'https://picsum.photos/seed/saudi2/1200/800',
        'https://picsum.photos/seed/saudi3/1200/800',
        'https://picsum.photos/seed/saudi4/1200/800',
        'https://picsum.photos/seed/saudi5/1200/800',
    ],
    'Qatar' => [
        'https://picsum.photos/seed/qatar1/1200/800',
        'https://picsum.photos/seed/qatar2/1200/800',
        'https://picsum.photos/seed/qatar3/1200/800',
        'https://picsum.photos/seed/qatar4/1200/800',
        'https://picsum.photos/seed/qatar5/1200/800',
    ],
    'Oman' => [
        'https://picsum.photos/seed/oman1/1200/800',
        'https://picsum.photos/seed/oman2/1200/800',
        'https://picsum.photos/seed/oman3/1200/800',
        'https://picsum.photos/seed/oman4/1200/800',
        'https://picsum.photos/seed/oman5/1200/800',
    ],
    'Bahrain' => [
        'https://picsum.photos/seed/bahrain1/1200/800',
        'https://picsum.photos/seed/bahrain2/1200/800',
        'https://picsum.photos/seed/bahrain3/1200/800',
        'https://picsum.photos/seed/bahrain4/1200/800',
        'https://picsum.photos/seed/bahrain5/1200/800',
    ],
    'Kuwait' => [
        'https://picsum.photos/seed/kuwait1/1200/800',
        'https://picsum.photos/seed/kuwait2/1200/800',
        'https://picsum.photos/seed/kuwait3/1200/800',
        'https://picsum.photos/seed/kuwait4/1200/800',
        'https://picsum.photos/seed/kuwait5/1200/800',
    ],
    'Turkey' => [
        'https://picsum.photos/seed/turkey1/1200/800',
        'https://picsum.photos/seed/turkey2/1200/800',
        'https://picsum.photos/seed/turkey3/1200/800',
        'https://picsum.photos/seed/turkey4/1200/800',
        'https://picsum.photos/seed/turkey5/1200/800',
    ],
    'United Kingdom' => [
        'https://picsum.photos/seed/uk1/1200/800',
        'https://picsum.photos/seed/uk2/1200/800',
        'https://picsum.photos/seed/uk3/1200/800',
        'https://picsum.photos/seed/uk4/1200/800',
        'https://picsum.photos/seed/uk5/1200/800',
    ],
    'France' => [
        'https://picsum.photos/seed/france1/1200/800',
        'https://picsum.photos/seed/france2/1200/800',
        'https://picsum.photos/seed/france3/1200/800',
        'https://picsum.photos/seed/france4/1200/800',
        'https://picsum.photos/seed/france5/1200/800',
    ],
    'Germany' => [
        'https://picsum.photos/seed/germany1/1200/800',
        'https://picsum.photos/seed/germany2/1200/800',
        'https://picsum.photos/seed/germany3/1200/800',
        'https://picsum.photos/seed/germany4/1200/800',
        'https://picsum.photos/seed/germany5/1200/800',
    ],
    'Italy' => [
        'https://picsum.photos/seed/italy1/1200/800',
        'https://picsum.photos/seed/italy2/1200/800',
        'https://picsum.photos/seed/italy3/1200/800',
        'https://picsum.photos/seed/italy4/1200/800',
        'https://picsum.photos/seed/italy5/1200/800',
    ],
    'Spain' => [
        'https://picsum.photos/seed/spain1/1200/800',
        'https://picsum.photos/seed/spain2/1200/800',
        'https://picsum.photos/seed/spain3/1200/800',
        'https://picsum.photos/seed/spain4/1200/800',
        'https://picsum.photos/seed/spain5/1200/800',
    ],
    'Switzerland' => [
        'https://picsum.photos/seed/swiss1/1200/800',
        'https://picsum.photos/seed/swiss2/1200/800',
        'https://picsum.photos/seed/swiss3/1200/800',
        'https://picsum.photos/seed/swiss4/1200/800',
        'https://picsum.photos/seed/swiss5/1200/800',
    ],
    'Netherlands' => [
        'https://picsum.photos/seed/netherlands1/1200/800',
        'https://picsum.photos/seed/netherlands2/1200/800',
        'https://picsum.photos/seed/netherlands3/1200/800',
        'https://picsum.photos/seed/netherlands4/1200/800',
        'https://picsum.photos/seed/netherlands5/1200/800',
    ],
    'Japan' => [
        'https://picsum.photos/seed/japan1/1200/800',
        'https://picsum.photos/seed/japan2/1200/800',
        'https://picsum.photos/seed/japan3/1200/800',
        'https://picsum.photos/seed/japan4/1200/800',
        'https://picsum.photos/seed/japan5/1200/800',
    ],
    'Singapore' => [
        'https://picsum.photos/seed/singapore1/1200/800',
        'https://picsum.photos/seed/singapore2/1200/800',
        'https://picsum.photos/seed/singapore3/1200/800',
        'https://picsum.photos/seed/singapore4/1200/800',
        'https://picsum.photos/seed/singapore5/1200/800',
    ],
    'Thailand' => [
        'https://picsum.photos/seed/thailand1/1200/800',
        'https://picsum.photos/seed/thailand2/1200/800',
        'https://picsum.photos/seed/thailand3/1200/800',
        'https://picsum.photos/seed/thailand4/1200/800',
        'https://picsum.photos/seed/thailand5/1200/800',
    ],
    'Malaysia' => [
        'https://picsum.photos/seed/malaysia1/1200/800',
        'https://picsum.photos/seed/malaysia2/1200/800',
        'https://picsum.photos/seed/malaysia3/1200/800',
        'https://picsum.photos/seed/malaysia4/1200/800',
        'https://picsum.photos/seed/malaysia5/1200/800',
    ],
    'India' => [
        'https://picsum.photos/seed/india1/1200/800',
        'https://picsum.photos/seed/india2/1200/800',
        'https://picsum.photos/seed/india3/1200/800',
        'https://picsum.photos/seed/india4/1200/800',
        'https://picsum.photos/seed/india5/1200/800',
    ],
    'China' => [
        'https://picsum.photos/seed/china1/1200/800',
        'https://picsum.photos/seed/china2/1200/800',
        'https://picsum.photos/seed/china3/1200/800',
        'https://picsum.photos/seed/china4/1200/800',
        'https://picsum.photos/seed/china5/1200/800',
    ],
];

echo "Starting country images import...\n";

$stats = [
    'countries' => 0,
    'images' => 0,
    'errors' => [],
];

foreach ($countryImages as $countryName => $imageUrls) {
    echo "--- Processing: {$countryName} ---\n";

    $country = \App\Models\Country::where('name', $countryName)->first();

    if (! $country) {
        echo "  ERROR: Country not found\n";
        $stats['errors'][] = "{$countryName}: Country not found";

        continue;
    }

    $isFirst = true;

    foreach ($imageUrls as $index => $imageUrl) {
        try {
            echo '  Image '.($index + 1).": Downloading...\n";

            // Download image
            $imageContent = file_get_contents($imageUrl);

            if (! $imageContent) {
                throw new \Exception('Failed to download');
            }

            // Generate filename
            $extension = '.jpg';
            $fileName = \Illuminate\Support\Str::slug($countryName).'_'.($index + 1).'_'.time().$extension;
            $path = 'countries/'.$fileName;

            // Upload to MinIO
            \Illuminate\Support\Facades\Storage::disk('minio')->put($path, $imageContent);
            $url = \Illuminate\Support\Facades\Storage::disk('minio')->url($path);

            echo '  Image '.($index + 1).": Uploaded to MinIO\n";

            // Create Media record
            $media = \App\Models\Media::create([
                'name' => $countryName.' - Image '.($index + 1),
                'alt_text' => $countryName.' travel image',
                'url' => $url,
                'file_name' => $fileName,
                'file_size' => strlen($imageContent),
                'mime_type' => 'image/jpeg',
            ]);

            // Assign to country (first image = featured)
            \App\Models\CountryMediaGallery::create([
                'country_id' => $country->id,
                'media_id' => $media->id,
                'is_featured' => $isFirst,
            ]);

            echo '  Image '.($index + 1).': ✓ Added to '.($isFirst ? 'FEATURED' : 'gallery')."\n";

            $stats['images']++;
            $isFirst = false;

        } catch (\Exception $e) {
            echo '  Image '.($index + 1).": ✗ Error - {$e->getMessage()}\n";
            $stats['errors'][] = "{$countryName} Image ".($index + 1).": {$e->getMessage()}";
        }
    }

    $stats['countries']++;
    echo "\n";
}

echo "\n=== Import Summary ===\n";
echo "Countries processed: {$stats['countries']}\n";
echo "Images uploaded: {$stats['images']}\n";

if (! empty($stats['errors'])) {
    echo 'Errors: '.count($stats['errors'])."\n";
}

echo "=== Done ===\n";
