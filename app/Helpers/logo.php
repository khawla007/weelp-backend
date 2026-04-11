<?php

use Illuminate\Support\Facades\Storage;

/**
 * Get the full URL for the Weelp logo.
 * Returns MinIO URL with fallback to local asset if MinIO is unavailable.
 *
 * @return string Full URL to the logo image
 */
function weelpLogoUrl(): string
{
    try {
        $path = config('app.logo_path', 'logos/weelp-logo-icon.png');
        return Storage::disk('minio')->url($path);
    } catch (\Exception $e) {
        // Fallback to local asset if MinIO is unavailable
        return asset('assets/images/weelp-logo-icon.png');
    }
}
