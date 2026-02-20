<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Media;

class MediaController extends Controller
{
    public function index()
    {
        return response()->json(Media::all());
    }


    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'file' => 'required|array',
            'file.*' => 'file|mimes:jpg,jpeg,png,pdf,doc|max:2048',
        ]);

        if ($request->hasFile('file')) {
            $uploadedMedia = [];
            foreach ($request->file('file') as $file) {

               
                // $filePath = $file->store('media', 'minio', 'public');
                $filePath = $file->store('media', 'minio');

                // dd($filePath);
                // Check if filePath is valid and then generate URL
                if (!$filePath) {
                    return response()->json([
                        'message' => 'File upload failed.'
                    ], 500);
                }

                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                
                $media = new Media();
                $media->name = $originalName;
                $media->alt_text = $originalName;
                $media->url = Storage::disk('minio')->url($filePath);
                $media->save();

                $uploadedMedia[] = $media;

            }
            return response()->json([
                'message' => 'Media uploaded successfully!',
                'data' => $uploadedMedia
            ], 201);
        }
        
        return response()->json([
            'message' => 'No file was uploaded.'
        ], 400);
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
