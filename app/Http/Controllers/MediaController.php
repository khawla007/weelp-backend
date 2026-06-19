<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Stream a public object from the MinIO disk.
     *
     * Avatar URLs are exposed to the browser as `/api/media/{path}` so they
     * resolve against the app origin instead of the (often unreachable) MinIO
     * endpoint host. Laravel pipes the object body straight through.
     */
    public function show(Request $request, string $path): StreamedResponse
    {
        $disk = Storage::disk('minio');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
