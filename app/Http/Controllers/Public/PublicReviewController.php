<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\City;
use App\Models\Activity;
use App\Models\Package;
use App\Models\Itinerary;

class PublicReviewController extends Controller
{
    /**
     * Get all approved reviews with optional city filter.
     * Used on: Homepage (all reviews), City page (city-filtered reviews).
     *
     * Query params:
     *   ?city=slug   — filter reviews to items located in that city
     *   ?per_page=N  — pagination (default 10)
     *   ?page=N      — page number
     */
    public function index()
    {
        $citySlug = request()->query('city');
        $perPage = (int) request()->query('per_page', 10);

        $query = Review::with(['user', 'item'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc');

        if ($citySlug) {
            $city = City::where('slug', $citySlug)->first();
            if (!$city) {
                return response()->json(['success' => false, 'message' => 'City not found'], 404);
            }
            $this->applyCityFilter($query, $city->id);
        }

        $reviews = $query->paginate($perPage);

        $reviews->getCollection()->transform(fn($review) => $this->transformReview($review));

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'current_page' => $reviews->currentPage(),
            'per_page' => $reviews->perPage(),
            'total' => $reviews->total(),
        ]);
    }

    /**
     * Get featured (is_featured = true) approved reviews with optional city filter.
     * Used on: City page featured review slider.
     *
     * Query params:
     *   ?city=slug — filter to items in that city
     */
    public function getFeaturedReviews()
    {
        $citySlug = request()->query('city');

        $query = Review::with(['user', 'item'])
            ->where('status', 'approved')
            ->where('is_featured', true)
            ->orderBy('created_at', 'desc');

        if ($citySlug) {
            $city = City::where('slug', $citySlug)->first();
            if (!$city) {
                return response()->json(['success' => false, 'message' => 'City not found'], 404);
            }
            $this->applyCityFilter($query, $city->id);
        }

        $reviews = $query->get();

        $data = $reviews->map(fn($review) => $this->transformReview($review));

        if ($data->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No featured reviews found']);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Filter reviews to items located in a specific city.
     * Uses direct subqueries per item type to avoid morphTo limitations.
     */
    private function applyCityFilter($query, int $cityId): void
    {
        $activityIds = Activity::whereHas('locations', fn($l) => $l->where('city_id', $cityId))->pluck('id');
        $packageIds = Package::whereHas('locations', fn($l) => $l->where('city_id', $cityId))->pluck('id');
        $itineraryIds = Itinerary::whereHas('locations', fn($l) => $l->where('city_id', $cityId))->pluck('id');

        $query->where(function ($q) use ($activityIds, $packageIds, $itineraryIds) {
            $q->where(function ($sub) use ($activityIds) {
                $sub->where('item_type', 'activity')->whereIn('item_id', $activityIds);
            })->orWhere(function ($sub) use ($packageIds) {
                $sub->where('item_type', 'package')->whereIn('item_id', $packageIds);
            })->orWhere(function ($sub) use ($itineraryIds) {
                $sub->where('item_type', 'itinerary')->whereIn('item_id', $itineraryIds);
            });
        });
    }

    /**
     * Transform a review into the public API response shape.
     */
    private function transformReview(Review $review): array
    {
        $item = $review->item;
        $citySlug = null;

        if ($item && !($item instanceof \App\Models\Transfer)) {
            $location = $item->locations()->with('city')->first();
            $citySlug = $location?->city?->slug;
        }

        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'review_text' => $review->review_text,
            'is_featured' => $review->is_featured,
            'item' => $item ? [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->item_type ?? $review->item_type,
                'slug' => $item->slug ?? null,
                'city_slug' => $citySlug,
            ] : null,
            'user' => $review->user ? [
                'id' => $review->user->id,
                'name' => $review->user->name,
            ] : null,
            'media_gallery' => $review->medias()
                ? $review->medias()->map(fn($media) => [
                    'id' => $media->id,
                    'name' => $media->name,
                    'alt' => $media->alt_text,
                    'url' => $media->url,
                ])
                : [],
            'created_at' => $review->created_at?->format('Y-m-d'),
        ];
    }
}
