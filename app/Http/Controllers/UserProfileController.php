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

                $review = \App\Models\Review::where('user_id', $user->id)
                            ->where('item_id', $order->orderable_id)
                            ->first();

                $reviewData = null;

                if ($review) {
                    $reviewMedia = collect($review->media_gallery ?? [])->map(function ($mediaId) {
                        $media = \App\Models\Media::find($mediaId);
                        return $media ? [
                            'id' => $media->id,
                            'name' => $media->name,
                            'alt_text' => $media->alt_text,
                            'url' => $media->url,
                        ] : null;
                    })->filter(); // null values remove kar diya

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
        $reviews = \App\Models\Review::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($review) {
                $media = collect($review->media_gallery ?? [])->map(function ($mediaId) {
                    $m = \App\Models\Media::find($mediaId);
                    return $m ? [
                        'id'       => $m->id,
                        'name'     => $m->name,
                        'alt_text' => $m->alt_text,
                        'url'      => $m->url,
                    ] : null;
                })->filter()->values();

                // Fetch item name dynamically
                $itemName = null;
                switch ($review->item_type) {
                    case 'activity':
                        $item = \App\Models\Activity::find($review->item_id);
                        $itemName = $item?->name;
                        break;

                    case 'package':
                        $item = \App\Models\Package::find($review->item_id);
                        $itemName = $item?->name;
                        break;

                    case 'itinerary':
                        $item = \App\Models\Itinerary::find($review->item_id);
                        $itemName = $item?->name;
                        break;
                }

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
            'media_gallery' => $uploadedMediaIds,
            'status' => 'pending',
        ]);

        // Full media details
        $media = collect($review->media_gallery ?? [])->map(function ($mediaId) {
            $m = \App\Models\Media::find($mediaId);
            return $m ? [
                'id' => $m->id,
                'name' => $m->name,
                'alt_text' => $m->alt_text,
                'url' => $m->url,
            ] : null;
        })->filter()->values();

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

        $review = \App\Models\Review::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found or access denied'
            ], 404);
        }

        // Load media details
        $media = collect($review->media_gallery ?? [])->map(function ($mediaId) {
            $m = \App\Models\Media::find($mediaId);
            return $m ? [
                'id'       => $m->id,
                'name'     => $m->name,
                'alt_text' => $m->alt_text,
                'url'      => $m->url,
            ] : null;
        })->filter()->values();

        // Fetch item name dynamically
        $itemName = null;
        switch ($review->item_type) {
            case 'activity':
                $item = \App\Models\Activity::find($review->item_id);
                $itemName = $item?->name;
                break;

            case 'package':
                $item = \App\Models\Package::find($review->item_id);
                $itemName = $item?->name;
                break;

            case 'itinerary':
                $item = \App\Models\Itinerary::find($review->item_id);
                $itemName = $item?->name;
                break;
        }

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
        $currentMediaIds = $review->media_gallery ?? [];

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

        // Update review media gallery
        $review->media_gallery = $updatedMediaIds;
        $review->save();

        // Full media details
        $media = collect($review->media_gallery ?? [])->map(function ($mediaId) {
            $m = \App\Models\Media::find($mediaId);
            return $m ? [
                'id' => $m->id,
                'name' => $m->name,
                'alt_text' => $m->alt_text,
                'url' => $m->url,
            ] : null;
        })->filter()->values();

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
        $review = \App\Models\Review::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return response()->json(['error' => 'Review not found or access denied'], 404);
        }

        // Delete associated media (if any)
        $currentMediaIds = $review->media_gallery ?? [];

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
