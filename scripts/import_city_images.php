<?php

/**
 * City Images Import Script
 * Run: php artisan tinker --execute="include 'scripts/import_city_images.php';"
 */
echo "Starting city images import...\n";

$stats = [
    'cities' => 0,
    'images' => 0,
    'errors' => [],
];

// Get all cities from database
$cities = \App\Models\City::all();

echo 'Found '.$cities->count()." cities in database\n\n";

foreach ($cities as $city) {
    $imageCount = rand(2, 5);  // Randomize per city
    echo "--- Processing: {$city->name} ({$city->slug}) - {$imageCount} images ---\n";

    // Skip if city already has images
    $existingCount = \App\Models\CityMediaGallery::where('city_id', $city->id)->count();
    if ($existingCount > 0) {
        echo "  SKIP: City already has {$existingCount} images\n\n";

        continue;
    }

    $isFirst = true;

    for ($i = 1; $i <= $imageCount; $i++) {
        try {
            echo "  Image {$i}: Downloading...\n";

            // Check for duplicate by media name
            $mediaName = $city->name.' - Image '.$i;
            $existingMedia = \App\Models\Media::where('name', $mediaName)->first();
            if ($existingMedia) {
                echo "  Image {$i}: SKIP - Media already exists\n";

                continue;
            }

            // Generate seed-based URL for unique images
            $seed = $city->slug.'-'.$i;
            $imageUrl = "https://picsum.photos/seed/{$seed}/800/600.jpg";

            // Download image with timeout and SSL verification disabled
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $imageContent = @file_get_contents($imageUrl, false, $context);

            if ($imageContent === false || empty($imageContent)) {
                throw new \Exception('Failed to download or empty content');
            }

            // Generate filename and path
            $fileName = $city->slug.'-'.$i.'.jpg';
            $path = 'cities/'.$fileName;

            // Upload to MinIO
            \Illuminate\Support\Facades\Storage::disk('minio')->put($path, $imageContent);
            $url = \Illuminate\Support\Facades\Storage::disk('minio')->url($path);

            echo "  Image {$i}: Uploaded to MinIO at {$path}\n";

            // Create Media record
            $media = \App\Models\Media::create([
                'name' => $mediaName,
                'url' => $url,
                'file_name' => $fileName,
                'file_size' => strlen($imageContent),
                'mime_type' => 'image/jpeg',
                'alt_text' => $city->name.' travel image',
            ]);

            // Attach to city via CityMediaGallery pivot (first image = featured)
            \App\Models\CityMediaGallery::create([
                'city_id' => $city->id,
                'media_id' => $media->id,
                'is_featured' => $isFirst,
            ]);

            echo "  Image {$i}: ✓ Added to ".($isFirst ? 'FEATURED' : 'gallery')."\n";

            $stats['images']++;
            $isFirst = false;

        } catch (\Exception $e) {
            echo "  Image {$i}: ✗ Error - {$e->getMessage()}\n";
            $stats['errors'][] = "{$city->name} Image {$i}: {$e->getMessage()}";
        }
    }

    $stats['cities']++;
    echo "\n";
}

echo "\n=== Import Summary ===\n";
echo "Cities processed: {$stats['cities']}\n";
echo "Images uploaded: {$stats['images']}\n";

if (! empty($stats['errors'])) {
    echo 'Errors: '.count($stats['errors'])."\n";
    foreach ($stats['errors'] as $error) {
        echo "  - {$error}\n";
    }
}

echo "=== Done ===\n";
