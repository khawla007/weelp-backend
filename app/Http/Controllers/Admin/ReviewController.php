<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\Paginator;
use App\Models\Activity;
use App\Models\Package;
use App\Models\Transfer;
use App\Models\Itinerary;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{

    public function index(Request $request)
    {
        $frontendBase = env('FRONTEND_URL', 'http://192.168.29.202:3000');
    
        // Force current page
        Paginator::currentPageResolver(function () use ($request) {
            return (int) $request->input('page', 1);
        });
    
        $query = Review::with(['user', 'item', 'mediaGallery.media', 'order']);

        if ($request->filled('item_type')) {
            $query->where('item_type', $request->item_type);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_name')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->customer_name . '%');
            });
        }
    
        $reviews = $query->orderBy('id', 'desc')
            ->paginate(5)
            ->appends($request->query());
    
        $reviews->getCollection()->transform(function ($review) use ($frontendBase) {
            $item = $review->item;

            $city = null;
            $region = null;

            // Agar item Transfer nahi hai tabhi locations load karo
            if ($item && !($item instanceof \App\Models\Transfer)) {
                $location = $item->locations()->with('city.state.country.regions')->first();
                $city     = $location?->city;
                $state    = $city?->state;
                $country  = $state?->country;
                $region   = $country?->regions?->first();
            }

            // Use helper methods
            $displayName = $review->getDisplayName();
            $hasLiveItem = $review->hasLiveItem();

            return [
                'id'            => $review->id,
                'rating'        => $review->rating,
                'review_text'   => $review->review_text,
                'status'        => $review->status,
                'is_featured'   => $review->is_featured,
                'order_id'      => $review->order_id,          // NEW
                'item'          => $item ? [
                    'id'           => $item->id,
                    'name'         => $displayName,            // Changed: use helper
                    'type'         => $item->item_type,
                    'has_live_item'=> $hasLiveItem,            // NEW
                    'frontend_url' => ($item instanceof \App\Models\Transfer)
                        ? null
                        : (($city && $region)
                            ? "{$frontendBase}/{$region->slug}/{$city->slug}/{$item->slug}"
                            : null),
                ] : null,
                'user'          => $review->user ? [
                    'id'   => $review->user->id,
                    'name' => $review->user->name,
                ] : null,
                'media_gallery' => $review->mediaGallery->map(fn($rmg) => [
                    'id'   => $rmg->media->id,
                    'name' => $rmg->media->name,
                    'alt'  => $rmg->media->alt_text,
                    'url'  => $rmg->media->url,
                ]),
                'created_at'    => $review->created_at ? $review->created_at->format('Y-m-d') : null,
                'updated_at'    => $review->updated_at ? $review->updated_at->format('Y-m-d') : null,
            ];
        });
    
        return response()->json([
            'data'         => $reviews->items(),
            'current_page' => $reviews->currentPage(),
            'per_page'     => $reviews->perPage(),
            'total'        => $reviews->total(),
        ]);
        // return response()->json($reviews);
    }
    
     

    // public function index(Request $request)
    // {
    //     $frontendBase = env('FRONTEND_URL', 'http://192.168.29.202:3000');
    
    //     // Base query
    //     $query = Review::with(['user', 'item']);
    
    //     if ($request->has('item_type') && $request->has('item_id')) {
    //         $query->where('item_type', $request->item_type)
    //               ->where('item_id', $request->item_id);
    //     }
    
    //     $reviews = $query->orderBy('id', 'desc')->paginate(10);
    
    //     $reviews->getCollection()->transform(function ($review) use ($frontendBase) {
    //         $item = $review->item;
    
    //         $city = null;
    //         $region = null;
    
    //         // Agar item Transfer nahi hai tabhi locations load karo
    //         if ($item && !($item instanceof \App\Models\Transfer)) {
    //             $location = $item?->locations?->first();
    //             $city     = $location?->city;
    //             $state    = $city?->state;
    //             $country  = $state?->country;
    //             $region   = $country?->regions?->first();
    //         }
    
    //         return [
    //             'id'          => $review->id,
    //             'rating'      => $review->rating,
    //             'review_text' => $review->review_text,
    //             'status'      => $review->status,
    //             'item'        => $item ? [
    //                 'id'   => $item->id,
    //                 'name' => $item->name,
    //                 'type' => $item->item_type,
    //                 // Transfer ke liye frontend_url null hoga
    //                 'frontend_url' => ($item instanceof \App\Models\Transfer)
    //                     ? null
    //                     : (($city && $region)
    //                         ? "{$frontendBase}/{$region->slug}/{$city->slug}/{$item->slug}"
    //                         : null),
    //             ] : null,
    //             'user'        => $review->user ? [
    //                 'id'   => $review->user->id,
    //                 'name' => $review->user->name,
    //             ] : null,
    //             'media_gallery' => $review->medias()
    //                 ? $review->medias()->map(fn($media) => [
    //                     'id'   => $media->id,
    //                     'name' => $media->name,
    //                     'alt'  => $media->alt_text,
    //                     'url'  => $media->url,
    //                 ])
    //                 : [],
    //             'created_at'  => $review->created_at,
    //             'updated_at'  => $review->updated_at,
    //         ];
    //     });
    
    //     return response()->json([
    //         'data'         => $reviews->items(),
    //         'current_page' => $reviews->currentPage(),
    //         'per_page'     => $reviews->perPage(),
    //         'total'        => $reviews->total(),
    //     ]);
    // }
    
    // 2.1. Get item anme and by there type
    public function getItemsByType(Request $request)
    {
        $request->validate([
            'item_type' => 'required|string|in:transfer,package,activity,itinerary',
        ]);

        $itemType = $request->item_type;
        $items = [];

        switch ($itemType) {
            case 'transfer':
                $items = Transfer::select('id', 'name')->get();
                break;

            case 'package':
                $items = Package::select('id', 'name')->get();
                break;

            case 'activity':
                $items = Activity::select('id', 'name')->get();
                break;

            case 'itinerary':
                $items = Itinerary::select('id', 'name')->get();
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    // 2.2. Store new review
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_type'        => 'required|string',
            'item_id'          => 'required|integer',
            'user_id'          => 'required|integer|exists:users,id',
            'order_id'         => 'nullable|integer|exists:orders,id',  // NEW
            'rating'           => 'required|integer|min:1|max:5',
            'review_text'      => 'nullable|string',
            'media_gallery'    => 'nullable|array',
            'media_gallery.*'  => 'integer|exists:media,id',
            'status'           => 'nullable|in:approved,pending',
            'is_featured'      => 'nullable|boolean',
        ]);

        // Remove media_gallery from validated data (not a column anymore)
        $mediaIds = $validated['media_gallery'] ?? [];
        unset($validated['media_gallery']);

        // Fetch item to populate snapshots
        $item = null;
        $itemName = null;
        $itemSlug = null;

        switch ($validated['item_type']) {
            case 'activity':
                $item = \App\Models\Activity::find($validated['item_id']);
                break;
            case 'package':
                $item = \App\Models\Package::find($validated['item_id']);
                break;
            case 'itinerary':
                $item = \App\Models\Itinerary::find($validated['item_id']);
                break;
        }

        if ($item) {
            $itemName = $item->name;
            $itemSlug = $item->slug;
        }

        $review = Review::create([
            'user_id'            => $validated['user_id'],
            'order_id'           => $validated['order_id'] ?? null,
            'item_type'          => $validated['item_type'],
            'item_id'            => $validated['item_id'],
            'item_name_snapshot' => $itemName,
            'item_slug_snapshot' => $itemSlug,
            'rating'             => $validated['rating'],
            'review_text'        => $validated['review_text'] ?? null,
            'status'             => $validated['status'] ?? 'pending',
            'is_featured'        => $validated['is_featured'] ?? false,
        ]);

        // Sync media to review_media_gallery table
        foreach ($mediaIds as $index => $mediaId) {
            $review->mediaGallery()->create([
                'media_id' => $mediaId,
                'sort_order' => $index,
            ]);
        }

        $review->load('mediaGallery.media');

        return response()->json([
            'success' => true,
            'data'    => $review
        ], 201);
    }
    

    // 3. Show single review
    public function show($id)
    {
        $frontendBase = env('FRONTEND_URL', 'http://localhost:3000');
    
        // Review fetch karo, item aur user ke saath
        $review = Review::with(['user', 'item', 'mediaGallery.media', 'order'])->findOrFail($id);
    
        $item = $review->item;
    
        $city = null;
        $region = null;
    
        // Agar item Transfer nahi hai tabhi locations load karo
        if ($item && !($item instanceof \App\Models\Transfer)) {
            $location = $item->locations->first();
            $city     = $location?->city;
            $state    = $city?->state;
            $country  = $state?->country;
            $region   = $country?->regions->first();
        }
    
        $data = [
            'id'            => $review->id,
            'rating'        => $review->rating,
            'review_text'   => $review->review_text,
            'status'        => $review->status,
            'is_featured'   => $review->is_featured,
            'order_id'      => $review->order_id,          // NEW
            'item'          => $item ? [
                'id'           => $item->id,
                'name'         => $review->getDisplayName(),  // Changed: use helper
                'type'         => $item->item_type,
                'has_live_item'=> $review->hasLiveItem(),     // NEW
                'frontend_url' => ($item instanceof \App\Models\Transfer)
                    ? null
                    : (($city && $region)
                        ? "{$frontendBase}/{$region->slug}/{$city->slug}/{$item->slug}"
                        : null),
            ] : null,
            'user'          => $review->user ? [
                'id'   => $review->user->id,
                'name' => $review->user->name,
            ] : null,
            'media_gallery' => $review->mediaGallery->map(fn($rmg) => [
                'id'   => $rmg->media->id,
                'name' => $rmg->media->name,
                'alt'  => $rmg->media->alt_text,
                'url'  => $rmg->media->url,
            ]),
            'created_at'    => $review->created_at ? $review->created_at->format('Y-m-d') : null,
            'updated_at'    => $review->updated_at ? $review->updated_at->format('Y-m-d') : null,
        ];
    
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
       

    // 4. Update review
    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $validated = $request->validate([
            'item_type'        => 'sometimes|string',
            'item_id'          => 'sometimes|integer',
            'user_id'          => 'sometimes|integer|exists:users,id',
            'order_id'         => 'sometimes|integer|exists:orders,id',
            'rating'           => 'sometimes|integer|min:1|max:5',
            'review_text'      => 'nullable|string',
            'media_gallery'    => 'nullable|array',
            'media_gallery.*'  => 'integer|exists:media,id',
            'status'           => 'nullable|in:approved,pending',
            'is_featured'      => 'nullable|boolean',
        ]);

        // Remove media_gallery from validated data (not a column anymore)
        $mediaIds = $validated['media_gallery'] ?? null;
        unset($validated['media_gallery']);

        $review->update($validated);

        // Refresh snapshots if item still exists
        if ($review->item) {
            $review->item_name_snapshot = $review->item->name;
            $review->item_slug_snapshot = $review->item->slug;
            $review->save();
        }

        // Sync media if provided
        if ($mediaIds !== null) {
            $review->mediaGallery()->delete();
            foreach ($mediaIds as $index => $mediaId) {
                $review->mediaGallery()->create([
                    'media_id' => $mediaId,
                    'sort_order' => $index,
                ]);
            }
        }

        $review->load('mediaGallery.media');

        return response()->json([
            'success' => true,
            'data'    => $review
        ]);
    }

    // 5. Delete review
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'integer|exists:reviews,id',
        ]);
    
        $reviewIds = $request->review_ids;
    
        // Delete reviews
        $deletedCount = Review::whereIn('id', $reviewIds)->delete();
    
        return response()->json([
            'success' => true,
            'message' => "$deletedCount review(s) deleted successfully",
        ]);
    }
}
