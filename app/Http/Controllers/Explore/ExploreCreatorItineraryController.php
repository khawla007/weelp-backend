<?php

namespace App\Http\Controllers\Explore;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Models\ItineraryLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class ExploreCreatorItineraryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Itinerary::creatorCopies()->approved()
            ->with([
                'creator:id,name,email',
                'creator.profile:id,user_id,avatar',
                'locations:id,itinerary_id,city_id',
                'locations.city:id,name',
                'mediaGallery' => fn($q) => $q->featured()->with('media:id,url')->limit(1),
                'basePricing.variations' => fn($q) => $q->limit(1),
                'schedules:id,itinerary_id',
            ]);

        // Source filter: show only the authenticated user's itineraries
        if ($request->query('source') === 'mine') {
            $user = Auth::guard('api')->user();
            if ($user) {
                $query->where('creator_id', $user->id);
            } else {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                ]);
            }
        }

        // Sort
        switch ($request->query('sort', 'latest')) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'top_rated':
                $query->orderBy('likes_count', 'desc')->orderBy('created_at', 'desc');
                break;
            case 'most_viewed':
                $query->orderBy('views_count', 'desc')->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $paginated = $query->paginate(15);

        $userId = Auth::guard('api')->id();

        $likedIds = $userId
            ? ItineraryLike::where('user_id', $userId)
                ->whereIn('itinerary_id', $paginated->pluck('id'))
                ->pluck('itinerary_id')
                ->toArray()
            : [];

        $collection = $paginated->getCollection()->map(function (Itinerary $itinerary) use ($userId, $likedIds) {
            $featuredMedia = $itinerary->mediaGallery->first();
            $variation = $itinerary->basePricing?->variations->first();

            return [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'description' => $itinerary->description,
                'creator' => $itinerary->creator,
                'locations' => $itinerary->locations,
                'is_liked' => in_array($itinerary->id, $likedIds),
                'day_count' => $itinerary->schedules->count(),
                'featured_image' => $featuredMedia?->media?->url,
                'display_price' => $variation?->sale_price ?? $variation?->regular_price,
                'currency' => $itinerary->basePricing?->currency,
                'likes_count' => $itinerary->likes_count,
                'views_count' => $itinerary->views_count,
                'created_at' => $itinerary->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $collection,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function toggleLike(int $id): JsonResponse
    {
        $user = Auth::user();
        $itinerary = Itinerary::creatorCopies()->approved()->findOrFail($id);

        $existing = ItineraryLike::where('itinerary_id', $itinerary->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $itinerary->decrement('likes_count');

            return response()->json([
                'success' => true,
                'liked' => false,
                'likes_count' => $itinerary->fresh()->likes_count,
            ]);
        }

        ItineraryLike::create([
            'itinerary_id' => $itinerary->id,
            'user_id' => $user->id,
        ]);
        $itinerary->increment('likes_count');

        return response()->json([
            'success' => true,
            'liked' => true,
            'likes_count' => $itinerary->fresh()->likes_count,
        ]);
    }

    public function recordView(int $id): JsonResponse
    {
        $itinerary = Itinerary::creatorCopies()->approved()->findOrFail($id);
        $itinerary->increment('views_count');

        return response()->json([
            'success' => true,
            'views_count' => $itinerary->fresh()->views_count,
        ]);
    }
}
