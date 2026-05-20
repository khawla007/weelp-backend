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

        return Storage::disk('minio')->response($path);
    }
}
