<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Itinerary;
use App\Models\Package;
use App\Models\State;
use App\Models\Tag;
use Illuminate\Http\Request;

class PublicCitiesController extends Controller
{
    // -------------------------getting city behalf of state-------------------------
    public function getCitiesByState($country_slug, $state_slug)
    {
        $country = Country::where('slug', $country_slug)->first();
        if (! $country) {
            return response()->json(['success' => false, 'message' => 'Country not found'], 404);
        }

        $state = State::where('slug', $state_slug)->where('country_id', $country->id)->first();
        if (! $state) {
            return response()->json(['success' => false, 'message' => 'State not found'], 404);
        }

        $cities = City::with('mediaGallery.media')
            ->where('state_id', $state->id)
            ->get()
            ->map(function ($city) {
                // Get featured image from media_gallery
                $featuredImage = $city->mediaGallery->firstWhere('is_featured', true);
                $city->feature_image = $featuredImage?->media->url ?? null;
                unset($city->mediaGallery);

                return $city;
            });

        if (empty($cities)) {
            return response()->json([
                'success' => false,
                'message' => 'Cities not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $cities,
        ]);
    }

    // ---------------------------getting all featured city for home page-------------------------
    public function getFeaturedCities()
    {
        $cities = City::with([
            'state.country.regions',
            'mediaGallery.media',
        ])
            ->withCount('activities')
            ->where('featured_destination', true)
            ->get()
            ->map(function ($city) {
                // Get featured image from media_gallery
                $featuredImage = $city->mediaGallery->firstWhere('is_featured', true);
                $featureImageUrl = $featuredImage?->media->url ?? null;

                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'slug' => $city->slug,
                    'description' => $city->description,
                    'featured_image' => $featureImageUrl,
                    'activities_count' => $city->activities_count,
                    'state' => [
                        'id' => $city->state->id ?? null,
                        'name' => $city->state->name ?? null,
                    ],
                    'country' => [
                        'id' => $city->state->country->id ?? null,
                        'name' => $city->state->country->name ?? null,
                    ],
                    'region' => $city->state->country->regions->map(function ($region) {
                        return [
                            'id' => $region->id,
                            'name' => $region->name,
                        ];
                    }),
                ];
            });

        if ($cities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No featured cities found',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $cities,
        ]);
    }

    // ---------------------------getting all cities with pagination-------------------------
    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 8), 50);

        $cities = City::with([
            'state.country',
            'mediaGallery.media',
        ])
            ->withCount('activities')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $request->input('page', 1));

        $mapped = $cities->getCollection()->map(function ($city) {
            $featuredImage = $city->mediaGallery->firstWhere('is_featured', true);
            $featureImageUrl = $featuredImage?->media->url ?? null;

            return [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'description' => $city->description,
                'featured_image' => $featureImageUrl,
                'activities_count' => $city->activities_count,
                'state' => [
                    'id' => $city->state->id ?? null,
                    'name' => $city->state->name ?? null,
                ],
                'country' => [
                    'id' => $city->state->country->id ?? null,
                    'name' => $city->state->country->name ?? null,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $mapped,
            'current_page' => $cities->currentPage(),
            'last_page' => $cities->lastPage(),
            'per_page' => $cities->perPage(),
            'total' => $cities->total(),
        ]);
    }

    // ----------------------------get single city page by slug--------------------------------

    public function getCityDetails($slug)
    {
        $city = City::with([
            'mediaGallery.media',
            'state',
            'country',
            'region',
            'locationDetails',
            'travelInfo',
            'seasons',
            'events',
            'additionalInfo',
            'faqs',
            'seo',
        ])->where('slug', $slug)->first();

        if (! $city) {
            return response()->json([
                'success' => false,
                'message' => 'City not found',
            ], 404);
        }

        // Get featured image and full gallery from media_gallery
        $featureImageUrl = null;
        $mediaGallery = [];
        if ($city->mediaGallery->count()) {
            $featuredImage = $city->mediaGallery->firstWhere('is_featured', true);
            $featureImageUrl = $featuredImage?->media->url ?? null;

            $mediaGallery = $city->mediaGallery->map(function ($item) {
                return [
                    'id' => $item->id,
                    'url' => $item->media->url ?? null,
                    'alt_text' => $item->media->alt_text ?? null,
                    'is_featured' => (bool) $item->is_featured,
                ];
            })->filter(fn ($item) => $item['url'] !== null)->values();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'description' => $city->description,
                'feature_image' => $featureImageUrl,
                'media_gallery' => $mediaGallery,
                'featured_destination' => $city->featured_destination,
                'state' => [
                    'id' => $city->state->id,
                    'name' => $city->state->name,
                ],
                'country' => $city->country ? [
                    'id' => $city->country->id,
                    'name' => $city->country->name,
                ] : null,
                'region' => $city->region ? [
                    'id' => $city->region->id,
                    'name' => $city->region->name,
                ] : null,
                'location_details' => $city->locationDetails,
                'travel_info' => $city->travelInfo,
                'seasons' => $city->seasons,
                'events' => $city->events,
                'additional_info' => $city->additionalInfo,
                'faqs' => $city->faqs,
                'seo' => $city->seo,
            ],
        ], 200);
    }

    // ----------------------------getting all items (activity, itinerary, package) by city---------------------------------
    public function getAllItemsByCity($city_slug)
    {
        request()->validate([
            'categories' => 'nullable|string',
            'tags' => 'nullable|string',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort_by' => 'nullable|in:name_asc,name_desc,price_asc,price_desc,id_asc,id_desc',
            'item_type' => 'nullable|in:activity,itinerary,package',
        ]);

        $city = City::with('state.country.regions')->where('slug', $city_slug)->first();

        if (! $city) {
            return response()->json(['success' => false, 'message' => 'City not found.'], 404);
        }

        $categorySlugs = request()->has('categories') ? explode(',', request()->get('categories')) : [];
        $tagSlugs = request()->has('tags') ? explode(',', request()->get('tags')) : [];
        $minPrice = request()->get('min_price', 0);
        $maxPrice = request()->get('max_price', null);
        $sortBy = request()->get('sort_by', 'id_desc');
        $itemType = request()->get('item_type', null);

        $categoryIds = ! empty($categorySlugs) ? Category::whereIn('slug', $categorySlugs)->pluck('id')->toArray() : [];
        $tagIds = ! empty($tagSlugs) ? Tag::whereIn('slug', $tagSlugs)->pluck('id')->toArray() : [];

        $activities = (! $itemType || $itemType === 'activity')
            ? Activity::whereHas('locations', fn ($query) => $query->where('city_id', $city->id))
                ->with(['pricing', 'groupDiscounts', 'categories.category', 'locations.city.state.country.regions', 'mediaGallery.media'])
            : null;

        $itineraries = (! $itemType || $itemType === 'itinerary')
            ? Itinerary::whereHas('locations', fn ($query) => $query->where('city_id', $city->id))
                ->with(['basePricing.variations', 'mediaGallery.media', 'categories.category', 'tags'])
            : null;

        $packages = (! $itemType || $itemType === 'package')
            ? Package::whereHas('locations', fn ($query) => $query->where('city_id', $city->id))
                ->with(['basePricing.variations', 'mediaGallery.media', 'categories.category', 'tags'])
            : null;

        if (! empty($categoryIds)) {
            $activities?->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $itineraries?->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $packages?->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
        }

        if (! empty($tagIds)) {
            $itineraries?->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
            $packages?->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
        }

        if ($maxPrice !== null) {
            $activities?->whereHas('pricing', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
            $itineraries?->whereHas('basePricing.variations', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
            $packages?->whereHas('basePricing.variations', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
        }

        $activities = $activities?->get() ?? collect();
        $itineraries = $itineraries?->get() ?? collect();
        $packages = $packages?->get() ?? collect();

        $allItems = collect()
            ->merge($activities->map(fn ($activity) => [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'item_type' => 'activity',
                'city_slug' => $city_slug,
                'featured' => $activity->featured_activity,
                'featured_image' => $activity->mediaGallery->where('is_featured', true)->first()?->media->url
                    ?? $activity->mediaGallery->first()?->media->url,
                'pricing' => $activity->pricing,
                'categories' => $activity->categories->map(fn ($category) => [
                    'slug' => $category->category->slug,
                    'name' => $category->category->name,
                ])->toArray(),
            ]))
            ->merge($itineraries->map(fn ($itinerary) => [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'item_type' => 'itinerary',
                'city_slug' => $city_slug,
                'featured' => $itinerary->featured_itinerary,
                'featured_image' => $itinerary->mediaGallery->where('is_featured', true)->first()?->media->url
                    ?? $itinerary->mediaGallery->first()?->media->url,
                'base_pricing' => $itinerary->basePricing,
                'categories' => $itinerary->categories->map(fn ($category) => [
                    'slug' => $category->category->slug,
                    'name' => $category->category->name,
                ])->toArray(),
                'tags' => $itinerary->tags->map(fn ($tag) => [
                    'slug' => $tag->slug,
                    'name' => $tag->name,
                ])->toArray(),
            ]))
            ->merge($packages->map(fn ($package) => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'item_type' => 'package',
                'city_slug' => $city_slug,
                'featured' => $package->featured_package,
                'featured_image' => $package->mediaGallery->where('is_featured', true)->first()?->media->url
                    ?? $package->mediaGallery->first()?->media->url,
                'base_pricing' => $package->basePricing,
                'categories' => $package->categories->map(fn ($category) => [
                    'slug' => $category->category->slug,
                    'name' => $category->category->name,
                ])->toArray(),
                'tags' => $package->tags->map(fn ($tag) => [
                    'slug' => $tag->slug,
                    'name' => $tag->name,
                ])->toArray(),
            ]));

        // Sorting
        $allItems = match ($sortBy) {
            'name_asc' => $allItems->sortBy('name'),
            'name_desc' => $allItems->sortByDesc('name'),
            'price_asc' => $allItems->sortBy(fn ($item) => $item['base_pricing']['regular_price'] ?? $item['pricing']['regular_price'] ?? 0),
            'price_desc' => $allItems->sortByDesc(fn ($item) => $item['base_pricing']['regular_price'] ?? $item['pricing']['regular_price'] ?? 0),
            'rating_desc' => $allItems->sortByDesc('rating'),
            'id_asc' => $allItems->sortBy('id'),
            default => $allItems->sortByDesc('id'),
        };

        // Pagination
        $perPage = (int) request()->get('per_page', 8);
        $page = (int) request()->get('page', 1);
        $total = $allItems->count();
        $paginatedItems = $allItems->forPage($page, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $paginatedItems,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
            'per_page' => $perPage,
            'total' => $total,
        ], 200);
    }
}
