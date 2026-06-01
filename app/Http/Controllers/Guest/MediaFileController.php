<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;

class MediaFileController extends Controller
{
    public function show(Media $media)
    {
        $path = $media->getRawOriginal('url');

        if (! $path) {
            abort(404);
        }

        if (str_starts_with($path, 'http')) {
            $path = ltrim(parse_url($path, PHP_URL_PATH) ?? '', '/');
            $bucket = config('filesystems.disks.minio.bucket');
            if ($bucket && str_starts_with($path, "{$bucket}/")) {
                $path = substr($path, strlen($bucket) + 1);
            }
        }

        // Guard against rows pointing at a deleted/missing bucket object:
        // FilesystemAdapter::response() eagerly reads fileSize() for the
        // Content-Length header, which throws UnableToRetrieveMetadata (500)
        // when the object is gone. A missing object is a 404, not a server error.
        if (! Storage::disk('minio')->exists($path)) {
            abort(404);
        }

        return Storage::disk('minio')->response($path);
    }
}
