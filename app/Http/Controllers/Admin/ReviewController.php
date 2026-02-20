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
    
        $query = Review::with(['user', 'item']);
    
        if ($request->filled('item_type')) {
            $query->where('item_type', $request->item_type);
        }
    
        if ($request->filled('item_name')) {
            $query->whereHas('item', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->item_name . '%');
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
                $location = $item?->locations?->first();
                $city     = $location?->city;
                $state    = $city?->state;
                $country  = $state?->country;
                $region   = $country?->regions?->first();
            }
    
            return [
                'id'          => $review->id,
                'rating'      => $review->rating,
                'review_text' => $review->review_text,
                'status'      => $review->status,
                'item'        => $item ? [
                    'id'   => $item->id,
                    'name' => $item->name,
                    'type' => $item->item_type,
                    // Transfer ke liye frontend_url null hoga
                    'frontend_url' => ($item instanceof \App\Models\Transfer)
                        ? null
                        : (($city && $region)
                            ? "{$frontendBase}/{$region->slug}/{$city->slug}/{$item->slug}"
                            : null),
                ] : null,
                'user'        => $review->user ? [
                    'id'   => $review->user->id,
                    'name' => $review->user->name,
                ] : null,
                'media_gallery' => $review->medias()
                    ? $review->medias()->map(fn($media) => [
                        'id'   => $media->id,
                        'name' => $media->name,
                        'alt'  => $media->alt_text,
                        'url'  => $media->url,
                    ])
                    : [],
                    'created_at' => $review->created_at ? $review->created_at->format('Y-m-d') : null,
                    'updated_at' => $review->updated_at ? $review->updated_at->format('Y-m-d') : null,
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
            'rating'           => 'required|integer|min:1|max:5',
            'review_text'      => 'nullable|string', 
            'media_gallery'   => 'nullable|array',
            'media_gallery.*' => 'integer|exists:media,id',
            'status'           => 'nullable|in:approved,pending',
        ]);
    
        // media_gallery ko JSON store karna
        // if (isset($validated['media_gallery'])) {
        //     $validated['media_gallery'] = json_encode($validated['media_gallery']);
        // }
    
        $review = Review::create($validated);
    
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
        $review = Review::with(['user', 'item'])->findOrFail($id);
    
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
            'id'           => $review->id,
            'rating'       => $review->rating,
            'review_text'  => $review->review_text,
            'status'       => $review->status,
            'item'         => $item ? [
                'id'           => $item->id,
                'name'         => $item->name,
                'type'         => $item->item_type,
                'frontend_url' => ($item instanceof \App\Models\Transfer) 
                                    ? null 
                                    : (($city && $region) 
                                        ? "{$frontendBase}/{$region->slug}/{$city->slug}/{$item->slug}" 
                                        : null),
            ] : null,
            'user'         => $review->user ? [
                'id'   => $review->user->id,
                'name' => $review->user->name,
            ] : null,
            'media_gallery' => $review->medias() 
                                ? $review->medias()->map(fn($media) => [
                                    'id'   => $media->id,
                                    'name' => $media->name,
                                    'alt'  => $media->alt_text,
                                    'url'  => $media->url,
                                ]) 
                                : [],
            'created_at' => $review->created_at ? $review->created_at->format('Y-m-d') : null,
            'updated_at' => $review->updated_at ? $review->updated_at->format('Y-m-d') : null,
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
            'rating'           => 'sometimes|integer|min:1|max:5',
            'review_text'      => 'nullable|string',
            'media_gallery'    => 'nullable|array',
            'media_gallery.*'  => 'integer|exists:media,id',
            'status'           => 'nullable|in:approved,pending',
        ]);

        // Agar media_gallery bheja gaya hai to JSON store karo
        // if (isset($validated['media_gallery'])) {
        //     $validated['media_gallery'] = json_encode($validated['media_gallery']);
        // }

        $review->update($validated);

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
