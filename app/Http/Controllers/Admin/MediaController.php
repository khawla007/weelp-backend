<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);

        return Media::orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function store(Request $request)
    {
        try {
            // Get files and normalize to array FIRST (handle both single file and multiple files)
            $files = $request->file('file');
            if (! is_array($files)) {
                $files = [$files]; // Convert single file to array
            }

            // Debug: Log incoming request with file sizes
            $fileSizes = array_map(fn ($f) => $f->getSize(), $files);
            info('[Media Upload] Files received:', [
                'count' => count($files),
                'sizes' => $fileSizes,
                'total_bytes' => array_sum($fileSizes),
            ]);

            // Check PHP upload limits
            $uploadMax = intval(ini_get('upload_max_filesize'));
            $postMax = intval(ini_get('post_max_size'));
            $contentLength = $request->header('Content-Length');

            info('[Media Upload] PHP Config:', [
                'upload_max_filesize' => $uploadMax,
                'post_max_size' => $postMax,
                'content_length' => $contentLength,
            ]);

            // Validate - accept single file or array of files (increased size limit to 10MB)
            $request->validate([
                'file' => 'required',
                'file.*' => 'file|mimes:jpg,jpeg,png,pdf,doc|max:10240', // 10MB = 10240 KB
            ]);

            info('[Media Upload] Processing files:', ['count' => count($files)]);

            if (! empty($files)) {
                $uploadedMedia = [];
                foreach ($files as $file) {
                    // Check if file is valid
                    if (! $file || ! $file->isValid()) {
                        $errorMsg = $file->getErrorMessage();
                        info('[Media Upload] Invalid file:', ['error' => $errorMsg]);

                        return response()->json([
                            'message' => 'File upload failed - invalid file.',
                            'error' => $errorMsg,
                        ], 500);
                    }

                    // Use Laravel's store() method instead of manual file_get_contents()
                    // This is more memory-efficient and handles streaming properly
                    $filePath = $file->store('media', 'minio');

                    // Check if filePath is valid
                    if (! $filePath) {
                        info('[Media Upload] Storage failed:', [
                            'file' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime' => $file->getMimeType(),
                        ]);

                        return response()->json([
                            'message' => 'File upload failed - could not store file to Minio.',
                            'file' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime' => $file->getMimeType(),
                        ], 500);
                    }

                    // Extract image dimensions
                    $imageInfo = @getimagesize($file->getRealPath());
                    $width = $imageInfo ? $imageInfo[0] : null;
                    $height = $imageInfo ? $imageInfo[1] : null;

                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                    $media = new Media;
                    $media->name = $originalName;
                    $media->alt_text = $originalName;
                    $media->url = $filePath;
                    $media->file_size = $file->getSize();
                    $media->width = $width;
                    $media->height = $height;
                    $media->save();

                    $uploadedMedia[] = $media;

                    info('[Media Upload] File uploaded successfully:', [
                        'media_id' => $media->id,
                        'path' => $filePath,
                        'url' => $media->url,
                    ]);
                }

                return response()->json([
                    'message' => 'Media uploaded successfully!',
                    'data' => $uploadedMedia,
                ], 201);
            }

            return response()->json([
                'message' => 'No file was uploaded.',
            ], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors in a consistent format
            info('[Media Upload] Validation failed:', ['errors' => $e->errors()]);

            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            info('[Media Upload] Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'File upload failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $media = Media::findOrFail($id);

        return response()->json($media);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $media = Media::findOrFail($id);

        $media->update([
            'name' => $request->has('name') ? $request->name : $media->name,
            'alt_text' => $request->has('alt_text') ? $request->alt_text : $media->alt_text,
        ]);

        return response()->json([
            'message' => 'Media updated successfully',
            'data' => $media,
        ]);
    }

    public function destroy($id)
    {
        $media = Media::find($id);

        if (! $media) {
            return response()->json(['message' => 'Media not found'], 404);
        }

        // Delete file from MinIO first
        try {
            $path = $media->getRawOriginal('url');

            // Legacy fallback: handle full URLs from before migration cleanup
            if (str_starts_with($path, 'http')) {
                $parsed = parse_url($path, PHP_URL_PATH);
                $path = ltrim($parsed, '/');
                $bucket = config('filesystems.disks.minio.bucket');
                $path = preg_replace('#^'.preg_quote($bucket, '#').'/#', '', $path);
            }

            if ($path && Storage::disk('minio')->exists($path)) {
                Storage::disk('minio')->delete($path);
                info('[Media Delete] Deleted from MinIO:', ['path' => $path]);
            }
        } catch (\Exception $e) {
            // Log error but continue with database deletion
            info('[Media Delete] Failed to delete from MinIO:', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Delete from database
        $media->delete();

        return response()->json(['message' => 'Media deleted successfully']);
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'media_ids' => 'required|array',
            'media_ids.*' => 'integer|exists:media,id',
        ]);

        // Get all media items before deletion
        $mediaItems = Media::whereIn('id', $request->media_ids)->get();

        // Delete files from MinIO first
        $minioDeletions = 0;
        foreach ($mediaItems as $media) {
            try {
                $path = $media->getRawOriginal('url');

                // Legacy fallback: handle full URLs from before migration cleanup
                if (str_starts_with($path, 'http')) {
                    $parsed = parse_url($path, PHP_URL_PATH);
                    $path = ltrim($parsed, '/');
                    $bucket = config('filesystems.disks.minio.bucket');
                    $path = preg_replace('#^'.preg_quote($bucket, '#').'/#', '', $path);
                }

                if ($path && Storage::disk('minio')->exists($path)) {
                    Storage::disk('minio')->delete($path);
                    $minioDeletions++;
                }
            } catch (\Exception $e) {
                // Log error but continue with other files
                info('[Media Bulk Delete] Failed to delete from MinIO:', [
                    'media_id' => $media->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Delete from database
        $deletedCount = Media::whereIn('id', $request->media_ids)->delete();

        info('[Media Bulk Delete] Completed:', [
            'database_deletions' => $deletedCount,
            'minio_deletions' => $minioDeletions,
        ]);

        return response()->json([
            'message' => "{$deletedCount} media deleted successfully",
            'deleted_count' => $deletedCount,
        ], 200);
    }
}
