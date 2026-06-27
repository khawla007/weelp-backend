<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MediaStorage
{
    public static function storeUploadedFile(UploadedFile $file, string $directory, string $fileName, string $disk = 'minio'): string
    {
        $path = $file->storeAs($directory, $fileName, $disk);

        if (! is_string($path) || $path === '' || ! Storage::disk($disk)->exists($path)) {
            throw new RuntimeException('File upload failed - object was not written to storage.');
        }

        return $path;
    }

    public static function putObject(string $path, string $contents, string $disk = 'minio'): string
    {
        $stored = Storage::disk($disk)->put($path, $contents);

        if (! $stored || ! Storage::disk($disk)->exists($path)) {
            throw new RuntimeException('File upload failed - object was not written to storage.');
        }

        return $path;
    }
}
