<?php

/**
 * Seed media items for testing pagination and bulk delete
 * Run: php artisan tinker --exec="include 'scripts/seed_media.php';"
 */

// Source images to duplicate (using frontend assets)
$sourceImages = [
    '/home/khawla/Documents/weelp/frontend/public/assets/images/china.jpg',
    '/home/khawla/Documents/weelp/frontend/public/assets/images/CountryBanner.jpeg',
    '/home/khawla/Documents/weelp/frontend/public/assets/images/Automn.png',
    '/home/khawla/Documents/weelp/frontend/public/assets/images/special.png',
    '/home/khawla/Documents/weelp/frontend/public/assets/images/whatabout.png',
    '/home/khawla/Documents/weelp/frontend/public/assets/images/greenimage.png',
    '/home/khawla/Documents/weelp/frontend/public/assets/images/PaymentMerchant.png',
];

$targetCount = 25;
$currentCount = \App\Models\Media::count();
$needed = $targetCount - $currentCount;

echo "Current: $currentCount, Target: $targetCount, Needed: $needed\n";

if ($needed <= 0) {
    echo "Already have enough media items!\n";
    return;
}

// Check if source images exist
$validSources = [];
foreach ($sourceImages as $img) {
    if (file_exists($img)) {
        $validSources[] = $img;
    }
}

if (empty($validSources)) {
    echo "No source images found!\n";
    return;
}

echo "Found " . count($validSources) . " source images\n";

// Get or create a user for the media
$user = \App\Models\User::where('email', 'khawla@fanaticcoders.com')->first();
if (!$user) {
    echo "Admin user not found!\n";
    return;
}

// Create media entries
$created = 0;
for ($i = 0; $i < $needed; $i++) {
    $sourceImg = $validSources[$i % count($validSources)];

    // Get file info
    $pathInfo = pathinfo($sourceImg);
    $extension = strtolower($pathInfo['extension'] ?? 'jpg');
    $originalName = $pathInfo['basename'];

    // Generate unique filename
    $filename = 'test_' . uniqid() . '_' . $i . '.' . $extension;

    // Create a copy
    $targetPath = storage_path('app/temp/' . $filename);
    if (!is_dir(dirname($targetPath))) {
        mkdir(dirname($targetPath), 0755, true);
    }
    copy($sourceImg, $targetPath);

    // Get file size
    $fileSize = filesize($targetPath);

    // Get image dimensions
    $dimensions = getimagesize($sourceImg);
    $width = $dimensions[0] ?? null;
    $height = $dimensions[1] ?? null;

    // Create media record
    $media = \App\Models\Media::create([
        'name' => 'Test Image ' . ($currentCount + $i + 1),
        'alt_text' => 'Test image for pagination testing',
        'url' => '/storage/media/' . $filename,
        'file_size' => $fileSize,
        'width' => $width,
        'height' => $height,
        'created_by' => $user->id,
    ]);

    // Move file to storage (simulate MinIO storage location)
    $storagePath = storage_path('app/public/media/' . $filename);
    if (!is_dir(dirname($storagePath))) {
        mkdir(dirname($storagePath), 0755, true);
    }
    rename($targetPath, $storagePath);

    $created++;

    if ($created % 5 === 0) {
        echo "Created $created items...\n";
    }
}

echo "\n✅ Successfully created $created media items!\n";
echo "New total: " . \App\Models\Media::count() . "\n";
