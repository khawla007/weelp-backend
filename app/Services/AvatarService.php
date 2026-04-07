<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AvatarService
{
    const AVATAR_SIZE = 400;
    const MAX_AVATAR_SIZE_BYTES = 50000;
    const INITIAL_QUALITY = 85;
    const MIN_QUALITY = 50;
    const QUALITY_STEP = 5;

    /**
     * Upload and process an avatar for a user.
     *
     * @return string Full MinIO URL of the uploaded avatar
     */
    public function upload(User $user, UploadedFile $file): string
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('GD extension is not available for image processing.');
        }

        $image = imagecreatefromstring(file_get_contents($file->getRealPath()));

        if (!$image) {
            throw new \RuntimeException('Failed to read image file.');
        }

        // Center crop to square
        $width = imagesx($image);
        $height = imagesy($image);
        $size = min($width, $height);
        $x = (int)(($width - $size) / 2);
        $y = (int)(($height - $size) / 2);

        $squared = imagecreatetruecolor(self::AVATAR_SIZE, self::AVATAR_SIZE);
        imagecopyresampled($squared, $image, 0, 0, $x, $y, self::AVATAR_SIZE, self::AVATAR_SIZE, $size, $size);
        imagedestroy($image);

        // Compress to WebP with iterative quality reduction
        $extension = 'webp';
        $filePath = sys_get_temp_dir() . '/' . uniqid('avatar_', true) . '.' . $extension;
        $quality = self::INITIAL_QUALITY;

        do {
            imagewebp($squared, $filePath, $quality);
            $fileSize = filesize($filePath);
            clearstatcache(true, $filePath);

            if ($fileSize > self::MAX_AVATAR_SIZE_BYTES) {
                $quality -= self::QUALITY_STEP;
            }
        } while ($fileSize > self::MAX_AVATAR_SIZE_BYTES && $quality >= self::MIN_QUALITY);

        imagedestroy($squared);

        // Store in MinIO
        $storagePath = 'avatars/' . $user->id . '.' . $extension;
        Storage::disk('minio')->put($storagePath, file_get_contents($filePath), 'public');

        // Cleanup temp file
        @unlink($filePath);

        // Save path to user profile
        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);
        $profile->avatar = $storagePath;
        $profile->save();

        return Storage::disk('minio')->url($storagePath);
    }

    /**
     * Delete a user's avatar from MinIO and clear the profile field.
     */
    public function delete(User $user): void
    {
        $profile = $user->profile;

        if (!$profile || !$profile->getRawOriginal('avatar')) {
            return;
        }

        $storagePath = $profile->getRawOriginal('avatar');

        if (!str_starts_with($storagePath, 'http')) {
            Storage::disk('minio')->delete($storagePath);
        }

        $profile->avatar = null;
        $profile->save();
    }
}
