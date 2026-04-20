<?php

/**
 * Quick State Images Import Script
 * Run: php artisan tinker --execute="include 'scripts/import_state_images.php';"
 */

// Define states (image counts randomized per state in loop)
$statesToProcess = [
    'Île-de-France',
    'Provence-Alpes-Côte d\'Azur',
    'Lombardy',
    'Tuscany',
    'Catalonia',
    'Andalusia',
    'Istanbul',
    'Antalya',
    'Bangkok',
    'Phuket',
    'Tokyo',
    'Osaka',
    'Dubai',
    'Abu Dhabi',
    'England',
    'Scotland',
    'Maharashtra',
    'Kerala',
    'Central',
    'East',
];

echo "Starting state images import...\n";

$stats = [
    'states' => 0,
    'images' => 0,
    'errors' => [],
];

foreach ($statesToProcess as $stateName) {
    $imageCount = rand(2, 5);  // Randomize per state
    echo "--- Processing: {$stateName} ({$imageCount} images) ---\n";

    $state = \App\Models\State::where('name', $stateName)->first();

    if (! $state) {
        echo "  ERROR: State not found\n";
        $stats['errors'][] = "{$stateName}: State not found";

        continue;
    }

    // Skip if state already has images
    $existingCount = \App\Models\StateMediaGallery::where('state_id', $state->id)->count();
    if ($existingCount > 0) {
        echo "  SKIP: State already has {$existingCount} images\n";

        continue;
    }

    $isFirst = true;

    for ($i = 1; $i <= $imageCount; $i++) {
        try {
            echo "  Image {$i}: Downloading...\n";

            // Generate seed for unique images
            $seed = \Illuminate\Support\Str::slug($stateName).$i;
            $imageUrl = "https://picsum.photos/seed/{$seed}/1200/800";

            // Download image with timeout
            $context = stream_context_create(['http' => ['timeout' => 10]]);
            $imageContent = @file_get_contents($imageUrl, false, $context);

            if ($imageContent === false || empty($imageContent)) {
                throw new \Exception('Failed to download or empty content');
            }

            // Generate filename
            $extension = '.jpg';
            $fileName = \Illuminate\Support\Str::slug($stateName).'_'.$i.'_'.time().$extension;
            $path = 'states/'.$fileName;

            // Upload to MinIO
            \Illuminate\Support\Facades\Storage::disk('minio')->put($path, $imageContent);
            $url = \Illuminate\Support\Facades\Storage::disk('minio')->url($path);

            echo "  Image {$i}: Uploaded to MinIO\n";

            // Create Media record
            $media = \App\Models\Media::create([
                'name' => $stateName.' - Image '.$i,
                'alt_text' => $stateName.' travel image',
                'url' => $url,
                'file_name' => $fileName,
                'file_size' => strlen($imageContent),
                'mime_type' => 'image/jpeg',
            ]);

            // Assign to state (first image = featured)
            \App\Models\StateMediaGallery::create([
                'state_id' => $state->id,
                'media_id' => $media->id,
                'is_featured' => $isFirst,
            ]);

            echo "  Image {$i}: ✓ Added to ".($isFirst ? 'FEATURED' : 'gallery')."\n";

            $stats['images']++;
            $isFirst = false;

        } catch (\Exception $e) {
            echo "  Image {$i}: ✗ Error - {$e->getMessage()}\n";
            $stats['errors'][] = "{$stateName} Image {$i}: {$e->getMessage()}";
        }
    }

    $stats['states']++;
    echo "\n";
}

echo "\n=== Import Summary ===\n";
echo "States processed: {$stats['states']}\n";
echo "Images uploaded: {$stats['images']}\n";

if (! empty($stats['errors'])) {
    echo 'Errors: '.count($stats['errors'])."\n";
}

echo "=== Done ===\n";
