<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Media;

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
            if (!is_array($files)) {
                $files = $files ? [$files] : []; // Convert single file to array, or empty array if null
            }

            // Debug: Log incoming request with file sizes
            $fileSizes = array_map(fn($f) => $f ? $f->getSize() : 0, $files);
            info('[Media Upload] Files received:', [
                'count' => count($files),
                'sizes' => $fileSizes,
                'total_bytes' => array_sum($fileSizes)
            ]);

            // Check PHP upload limits
            $uploadMax = intval(ini_get('upload_max_filesize'));
            $postMax = intval(ini_get('post_max_size'));
            $contentLength = $request->header('Content-Length');

            info('[Media Upload] PHP Config:', [
                'upload_max_filesize' => $uploadMax,
                'post_max_size' => $postMax,
                'content_length' => $contentLength
            ]);

            // Validate - accept single file or array of files (increased size limit to 10MB)
            $request->validate([
                'file' => 'required',
                'file.*' => 'file|mimes:jpg,jpeg,png,pdf,doc|max:10240', // 10MB = 10240 KB
            ]);

            info('[Media Upload] Processing files:', ['count' => count($files)]);

            if (!empty($files)) {
                $uploadedMedia = [];
                foreach ($files as $file) {
                    // Check if file is valid
                    if (!$file || !$file->isValid()) {
                        $errorMsg = $file ? $file->getErrorMessage() : 'No file provided';
                        info('[Media Upload] Invalid file:', ['error' => $errorMsg]);
                        return response()->json([
                            'message' => 'File upload failed - invalid file.',
                            'error' => $errorMsg
                        ], 500);
                    }

                    // Use Laravel's store() method instead of manual file_get_contents()
                    // This is more memory-efficient and handles streaming properly
                    $filePath = $file->store('media', 'minio');

                    // Check if filePath is valid
                    if (!$filePath) {
                        info('[Media Upload] Storage failed:', [
                            'file' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime' => $file->getMimeType()
                        ]);
                        return response()->json([
                            'message' => 'File upload failed - could not store file to Minio.',
                            'file' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime' => $file->getMimeType()
                        ], 500);
                    }

                    // Extract image dimensions
                    $imageInfo = @getimagesize($file->getRealPath());
                    $width = $imageInfo ? $imageInfo[0] : null;
                    $height = $imageInfo ? $imageInfo[1] : null;

                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                    $media = new Media();
                    $media->name = $originalName;
                    $media->alt_text = $originalName;
                    $media->url = Storage::disk('minio')->url($filePath);
                    $media->file_size = $file->getSize();
                    $media->width = $width;
                    $media->height = $height;
                    $media->save();

                    $uploadedMedia[] = $media;

                    info('[Media Upload] File uploaded successfully:', [
                        'media_id' => $media->id,
                        'path' => $filePath,
                        'url' => $media->url
                    ]);
                }

                return response()->json([
                    'message' => 'Media uploaded successfully!',
                    'data' => $uploadedMedia
                ], 201);
            }

            return response()->json([
                'message' => 'No file was uploaded.'
            ], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors in a consistent format
            info('[Media Upload] Validation failed:', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            info('[Media Upload] Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'File upload failed: ' . $e->getMessage()
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
            'data' => $media
        ]);
    }

    public function destroy($id)
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['message' => 'Media not found'], 404);
        }

        $media->delete();
        return response()->json(['message' => 'Media deleted successfully']);
    }
}
