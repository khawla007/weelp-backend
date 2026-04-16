<?php

/**
 * Designs → MinIO Uploader
 * Run: php artisan tinker --execute="include 'scripts/upload_designs_to_minio.php';"
 *
 * Uploads image files from /Designs and /Designs/images to MinIO bucket
 * under prefix `designs/`. Skips non-image files (.fig, .pen, etc.).
 * Idempotent: skips objects already present.
 */

use Illuminate\Support\Facades\Storage;

$sourceRoot   = base_path('../Designs');
$extensions   = ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'];
$disk         = Storage::disk('minio');
$stats        = ['found' => 0, 'uploaded' => 0, 'skipped' => 0, 'failed' => 0];
$failures     = [];

if (! is_dir($sourceRoot)) {
    echo "ERROR: source dir missing: {$sourceRoot}\n";
    return;
}

/**
 * Collect image files from a directory (non-recursive).
 * Returns [local_path => remote_key] map.
 */
$collect = function (string $dir, string $remotePrefix) use ($extensions): array {
    $out = [];
    if (! is_dir($dir)) return $out;
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $path = $dir . '/' . $entry;
        if (! is_file($path)) continue;
        $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
        if (! in_array($ext, $extensions, true)) continue;
        $out[$path] = $remotePrefix . $entry;
    }
    return $out;
};

$targets = $collect($sourceRoot, 'designs/')
         + $collect($sourceRoot . '/images', 'designs/images/');

$stats['found'] = count($targets);
echo "Found {$stats['found']} image files to process.\n\n";

foreach ($targets as $local => $remote) {
    try {
        if ($disk->exists($remote)) {
            $stats['skipped']++;
            continue;
        }
        $ok = $disk->put($remote, file_get_contents($local));
        if ($ok) {
            $stats['uploaded']++;
            if ($stats['uploaded'] % 25 === 0) {
                echo "  … uploaded {$stats['uploaded']}\n";
            }
        } else {
            $stats['failed']++;
            $failures[] = $remote;
        }
    } catch (\Throwable $e) {
        $stats['failed']++;
        $failures[] = $remote . ' (' . $e->getMessage() . ')';
    }
}

echo "\n=== Results ===\n";
echo "found:    {$stats['found']}\n";
echo "uploaded: {$stats['uploaded']}\n";
echo "skipped:  {$stats['skipped']}\n";
echo "failed:   {$stats['failed']}\n";

if ($failures) {
    echo "\nFailures:\n";
    foreach ($failures as $f) echo "  - {$f}\n";
}
