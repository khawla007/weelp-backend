<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserMeta;  // Import UserMeta model
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    // Avatar constants
    const AVATAR_SIZE = 400;
    const MAX_AVATAR_SIZE_BYTES = 50000; // 50KB
    const INITIAL_QUALITY = 85;
    const MIN_QUALITY = 50;
    const QUALITY_STEP = 5;

    /**
     * Handle the get user profile request.
    */
    public function show(Request $request)
    {
        $user = User::with(['profile.urls', 'meta', ])->find($request->user()->id);

        if (!$user) {
            return response()->json(['error' => 'Profile not found'], 404);
        }

        // return response()->json($profile);
        return response()->json([
            'user'    => $user,
        ]);
    }

    /**
     * Handle the insert/update user profile request.
    */

    public function update(Request $request)
    {
        $validated = $request->validate([
            'avatar' => 'nullable|url',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'linkedin_url' => 'nullable|url',
            'username' => 'nullable|string|max:255',
            'interest' => 'nullable|string',
            'bio' => 'nullable|string',

            // URLs validation
            'urls' => 'nullable|array',
            'urls.*.label' => 'nullable|string|max:255',
            'urls.*.url' => 'nullable|url',
        ]);

        $user = $request->user();
        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);

        $profile->fill($validated);
        $profile->save();
        
        $userMeta = UserMeta::firstOrNew(['user_id' => $user->id]);

        if (isset($validated['username'])) {
            $userMeta->username = $validated['username'];
        }
        if (isset($validated['interest'])) {
            $userMeta->interest = $validated['interest'];
        }
        if (isset($validated['bio'])) {
            $userMeta->bio = $validated['bio'];
        }

        $userMeta->save();  
                        
        if ($request->has('urls')) {
            $incomingUrls = $validated['urls'];
            $existingUrls = $profile->urls()->orderBy('id')->get();
            
            $existingCount = $existingUrls->count();
            $incomingCount = count($incomingUrls);
        
            foreach ($incomingUrls as $index => $urlData) {
                if ($index < $existingCount) {
                    // Update existing URL
                    $existingUrls[$index]->update([
                        'label' => $urlData['label'] ?? $existingUrls[$index]->label,
                        'url' => $urlData['url'] ?? $existingUrls[$index]->url
                    ]);
                } else {
                    // Create new URL entry
                    $profile->urls()->create($urlData);
                }
            }
        
            // If there are extra existing URLs beyond the incoming data, delete them
            if ($existingCount > $incomingCount) {
                for ($i = $incomingCount; $i < $existingCount; $i++) {
                    $existingUrls[$i]->delete();
                }
            }
        }
        
        
        return response()->json([
            'success' => true,
            'profile' => $profile->load('urls'),
            'user_meta' => $userMeta
        ]);
    }

    /**
     * Handle avatar upload with optimization.
     */
    public function uploadAvatar(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Check GD extension availability
        if (!extension_loaded('gd')) {
            return response()->json(['error' => 'Image processing not available'], 500);
        }

        $user = $request->user();
        $file = $request->file('file');

        $image = null;
        $squared = null;
        $filePath = null;

        try {
            // Read the image using real path for memory efficiency
            $image = imagecreatefromstring(file_get_contents($file->getRealPath()));
            if (!$image) {
                return response()->json(['error' => 'Failed to process image'], 400);
            }

            // Get original dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Calculate crop to square (center crop)
            $size = min($width, $height);
            $x = ($width - $size) / 2;
            $y = ($height - $size) / 2;

            // Create new square image
            $squared = imagecreatetruecolor(self::AVATAR_SIZE, self::AVATAR_SIZE);
            imagecopyresampled($squared, $image, 0, 0, $x, $y, self::AVATAR_SIZE, self::AVATAR_SIZE, $size, $size);

            // Determine output format
            $extension = 'webp';

            // Start with initial quality
            $quality = self::INITIAL_QUALITY;
            $filePath = sys_get_temp_dir() . '/' . uniqid('avatar_', true) . '.' . $extension;
            $fileSize = null;

            // Compress until under max size (fixed infinite loop condition)
            do {
                imagewebp($squared, $filePath, $quality);
                $fileSize = filesize($filePath);

                if ($fileSize > self::MAX_AVATAR_SIZE_BYTES) {
                    $quality -= self::QUALITY_STEP;
                }
            } while ($fileSize > self::MAX_AVATAR_SIZE_BYTES && $quality >= self::MIN_QUALITY);

            // Store in MinIO using real path for memory efficiency
            $storagePath = 'avatars/' . $user->id . '.' . $extension;
            Storage::disk('minio')->put($storagePath, file_get_contents($filePath), 'public');

            // Get URL
            $url = Storage::disk('minio')->url($storagePath);

            return response()->json([
                'success' => true,
                'url' => $url
            ]);

        } catch (\Exception $e) {
            // Log internally, return generic message
            \Log::error('Avatar upload failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to upload avatar'], 500);
        } finally {
            // Clean up resources - always execute
            if ($image) {
                imagedestroy($image);
            }
            if ($squared) {
                imagedestroy($squared);
            }
            if ($filePath && file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function getUserOrders(Request $request)
    {
        $user = auth()->user();

        $orders = Order::with([
            'payment',
            'emergencyContact',
            'orderable' => function ($morphTo) {
                $morphTo->morphWith([
                    \App\Models\Activity::class => [
                        'locations.city.state.country',
                        'mediaGallery.media',
                    ],
                    \App\Models\Package::class => [
                        'locations.city.state.country',
                        'mediaGallery.media',
                    ],
                    \App\Models\Itinerary::class => [
                        'locations.city.state.country',
                        'mediaGallery.media',
                    ],
                ]);
            },
        ])->where('user_id', auth()->id())->latest()->get();

        if ($orders->isEmpty()) {
            return response()->json(['error' => 'No orders found'], 404);
        }

        $userProfile = $user->profile;

        $transformed = $orders->map(function ($order) use ($user, $userProfile) {
            $orderable = $order->orderable;

            $cityName = null;
            $regionName = null;

            // ✅ Load from snapshot if orderable is missing
                $snapshot = is_array($order->item_snapshot_json)
                    ? $order->item_snapshot_json
                    : json_decode($order->item_snapshot_json, true);

                $media = collect($snapshot['media'] ?? [])->map(fn ($mediaLink) => [
                    'id' => $mediaLink['id'] ?? null,
                    'name' => $mediaLink['name'] ?? null,
                    'alt_text' => $mediaLink['alt'] ?? null,
                    'url' => $mediaLink['url'] ?? null,
                ]);

                $locations = $snapshot['location'] ?? [];
                $cityName = $locations[0]['city'] ?? null;
                $countryId = null;

                if (!empty($locations[0]['country'])) {
                    $countryId = \App\Models\Country::where('name', $locations[0]['country'])->value('id');
                }

                $region = $countryId
                    ? \App\Models\Region::whereHas('countries', fn ($q) => $q->where('countries.id', $countryId))->first()
                    : null;

                // $review = \App\Models\Review::where('user_id', $user->id)
                //     ->where('item_id', $order->orderable_id)
                //     ->first();

                $review = \App\Models\Review::with('mediaGallery.media')
                            ->where('user_id', $user->id)
                            ->where('item_id', $order->orderable_id)
                            ->first();

                $reviewData = null;

                if ($review) {
                    $reviewMedia = $review->mediaGallery->map(fn($rmg) => [
                        'id' => $rmg->media->id,
                        'name' => $rmg->media->name,
                        'alt_text' => $rmg->media->alt_text,
                        'url' => $rmg->media->url,
                    ]);

                    $reviewData = [
                        'id' => $review->id,
                        'user_id' => $review->user_id,
                        'item_type' => $review->item_type,
                        'item_id' => $review->item_id,
                        'rating' => $review->rating,
                        'review_text' => $review->review_text,
                        'status' => $review->status,
                        'media_gallery' => $reviewMedia->values(),
                        'created_at' => $review->created_at,
                        'updated_at' => $review->updated_at,
                    ];
                }
                    
                return [
                    'id' => $order->id,
                    'item_id' => $order->orderable_id,
                    'status' => $order->status,
                    'travel_date' => $order->travel_date,
                    'preferred_time' => $order->preferred_time,
                    'number_of_adults' => $order->number_of_adults,
                    'number_of_children' => $order->number_of_children,
                    'special_requirements' => $order->special_requirements,
                    'payment' => $order->payment,
                    'emergency_contact' => $order->emergencyContact,
                    'item' => [
                        'name' => $snapshot['name'] ?? null,
                        'slug' => $snapshot['slug'] ?? null,
                        'item_type' => $snapshot['item_type'] ?? null,
                        'city' => $cityName,
                        'region' => $region?->name,
                        'locations' => $snapshot['location'] ?? null,
                        'media' => $media,
                    ],
                    'review' => $reviewData,
                    'user' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $userProfile?->phone,
                    ],
                ];
        });

        return response()->json([
            'success' => true,
            'orders' => $transformed->values()
        ]);
    }


    // ****************************Review api all for customers********************************

    public function reviewIndex(Request $request)
    {
        $user = auth()->user();

        // Fetch reviews of the logged-in customer with media details
        $reviews = \App\Models\Review::with(['mediaGallery.media', 'item'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($review) {
                $media = $review->mediaGallery->map(fn($rmg) => [
                    'id'       => $rmg->media->id,
                    'name'     => $rmg->media->name,
                    'alt_text' => $rmg->media->alt_text,
                    'url'      => $rmg->media->url,
                ])->values();

                $itemName = $review->item?->name;

                return [
                    'id'           => $review->id,
                    'item_type'    => $review->item_type,
                    'item_id'      => $review->item_id,
                    'item_name'    => $itemName,
                    'rating'       => $review->rating,
                    'review_text'  => $review->review_text,
                    'status'       => $review->status,
                    'media_gallery'=> $media,
                    'created_at'   => $review->created_at,
                    'updated_at'   => $review->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'reviews' => $reviews,
        ]);
    }

    public function reviewStore(Request $request)
    {
        $user = auth()->user();

        // Validate review + optional file upload
        $request->validate([
            'item_type' => 'required|string|in:activity,package,itinerary',
            'item_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string',
            'file' => 'nullable|array',
            'file.*' => 'file|mimes:jpg,jpeg,png,pdf,doc|max:2048',
        ]);

        $uploadedMediaIds = [];

        // ✅ Handle file upload if present
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $file) {
                $filePath = $file->store('media', 'minio');

                if (!$filePath) {
                    return response()->json([
                        'message' => 'File upload failed.'
                    ], 500);
                }

                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                $media = new \App\Models\Media();
                $media->name = $originalName;
                $media->alt_text = $originalName;
                $media->url = Storage::disk('minio')->url($filePath);
                // $media->user_id = $user->id; // user ownership track karne ke liye
                $media->save();

                $uploadedMediaIds[] = $media->id;
            }
        }

        // Create review with uploaded media IDs
        $review = \App\Models\Review::create([
            'user_id' => $user->id,
            'item_type' => $request->item_type,
            'item_id' => $request->item_id,
            'rating' => $request->rating,
            'review_text' => $request->review_text,
            'status' => 'pending',
        ]);

        // Sync media to review_media_gallery table
        foreach ($uploadedMediaIds as $index => $mediaId) {
            $review->mediaGallery()->create([
                'media_id' => $mediaId,
                'sort_order' => $index,
            ]);
        }
        $review->load('mediaGallery.media');

        // Full media details
        $media = $review->mediaGallery->map(fn($rmg) => [
            'id' => $rmg->media->id,
            'name' => $rmg->media->name,
            'alt_text' => $rmg->media->alt_text,
            'url' => $rmg->media->url,
        ])->values();

        $reviewData = [
            'id' => $review->id,
            'user_id' => $review->user_id,
            'item_type' => $review->item_type,
            'item_id' => $review->item_id,
            'rating' => $review->rating,
            'review_text' => $review->review_text,
            'status' => $review->status,
            'media_gallery' => $media,
            'created_at' => $review->created_at,
            'updated_at' => $review->updated_at,
        ];

        return response()->json([
            'success' => true,
            'review' => $reviewData
        ]);
    }
    
    public function reviewShow($id)
    {
        $user = auth()->user();

        $review = \App\Models\Review::with(['mediaGallery.media', 'item'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found or access denied'
            ], 404);
        }

        // Load media details
        $media = $review->mediaGallery->map(fn($rmg) => [
            'id'       => $rmg->media->id,
            'name'     => $rmg->media->name,
            'alt_text' => $rmg->media->alt_text,
            'url'      => $rmg->media->url,
        ])->values();

        $itemName = $review->item?->name;

        $reviewData = [
            'id'           => $review->id,
            'item_type'    => $review->item_type,
            'item_id'      => $review->item_id,
            'item_name'    => $itemName, 
            'rating'       => $review->rating,
            'review_text'  => $review->review_text,
            'status'       => $review->status,
            'media_gallery'=> $media,
            'created_at'   => $review->created_at,
            'updated_at'   => $review->updated_at,
        ];

        return response()->json([
            'success' => true,
            'review'  => $reviewData,
        ]);
    }

    public function reviewUpdate(Request $request, $id)
    {
    try {
        $user = auth()->user();

        // Find the review
        $review = \App\Models\Review::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return response()->json(['error' => 'Review not found or access denied'], 404);
        }

        // Validate optional fields
        $request->validate([
            'rating'               => 'nullable|integer|min:1|max:5',
            'review_text'          => 'nullable|string',
            'file'                 => 'nullable|array',
            'file.*'               => 'file|mimes:jpg,jpeg,png,pdf,doc|max:2048',
            'existing_media_ids'   => 'nullable|array',
            'existing_media_ids.*' => 'integer|exists:media,id',
        ]);

        // Update basic fields if provided
        $review->rating = $request->rating ?? $review->rating;
        $review->review_text = $request->review_text ?? $review->review_text;

        // Cast existing_media_ids to integer array
        $existingMediaIdsFromRequest = collect($request->existing_media_ids ?? [])->map(fn($id) => (int)$id)->toArray();
        $currentMediaIds = $review->load('mediaGallery')->mediaGallery->pluck('media_id')->toArray();

        // Delete media not present in request
        foreach ($currentMediaIds as $oldMediaId) {
            if (!in_array($oldMediaId, $existingMediaIdsFromRequest)) {
                $oldMedia = \App\Models\Media::find($oldMediaId);
                if ($oldMedia && !empty($oldMedia->url)) {
                    // Delete from MinIO
                    $parsedUrl = parse_url($oldMedia->url, PHP_URL_PATH);
                    $relativePath = ltrim($parsedUrl, '/');
                    if ($relativePath) {
                        Storage::disk('minio')->delete($relativePath);
                    }
                    // Delete DB record
                    $oldMedia->delete();
                }
            }
        }

        $updatedMediaIds = $existingMediaIdsFromRequest; // keep media user wants

        // Handle new file uploads
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $file) {
                $filePath = $file->store('media', 'minio');

                if (!$filePath) {
                    return response()->json(['message' => 'File upload failed.'], 500);
                }

                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                $media = new \App\Models\Media();
                $media->name = $originalName;
                $media->alt_text = $originalName;
                $media->url = Storage::disk('minio')->url($filePath);
                $media->save();

                $updatedMediaIds[] = $media->id;
            }
        }

        // Sync to review_media_gallery table
        $review->mediaGallery()->delete();
        foreach ($updatedMediaIds as $index => $mediaId) {
            $review->mediaGallery()->create([
                'media_id' => $mediaId,
                'sort_order' => $index,
            ]);
        }
        $review->save();
        $review->load('mediaGallery.media');

        // Full media details
        $media = $review->mediaGallery->map(fn($rmg) => [
            'id' => $rmg->media->id,
            'name' => $rmg->media->name,
            'alt_text' => $rmg->media->alt_text,
            'url' => $rmg->media->url,
        ])->values();

        $reviewData = [
            'id' => $review->id,
            'user_id' => $review->user_id,
            'item_type' => $review->item_type,
            'item_id' => $review->item_id,
            'rating' => $review->rating,
            'review_text' => $review->review_text,
            'status' => $review->status,
            'media_gallery' => $media,
            'created_at' => $review->created_at,
            'updated_at' => $review->updated_at,
        ];

        return response()->json([
            'success' => true,
            'review' => $reviewData
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ], 500);
    }
    }

    public function reviewDelete($id)
    {
        $user = auth()->user();

        // Find the review by id and user
        $review = \App\Models\Review::with('mediaGallery')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return response()->json(['error' => 'Review not found or access denied'], 404);
        }

        // Delete associated media (if any)
        $currentMediaIds = $review->mediaGallery->pluck('media_id')->toArray();

        foreach ($currentMediaIds as $mediaId) {
            $media = \App\Models\Media::find($mediaId);
            if ($media && !empty($media->url)) {
                // Delete from MinIO
                $parsedUrl = parse_url($media->url, PHP_URL_PATH);
                $relativePath = ltrim($parsedUrl, '/');
                if ($relativePath) {
                    Storage::disk('minio')->delete($relativePath);
                }
                // Delete DB record
                $media->delete();
            }
        }

        // Finally delete the review
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    }
    

}
