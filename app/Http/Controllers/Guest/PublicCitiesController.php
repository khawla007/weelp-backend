<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityTag;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Itinerary;
use App\Models\ItineraryCategory;
use App\Models\ItineraryTag;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageTag;
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
                $featuredImage = $city->mediaGallery->firstWhere('is_featured', true)
                    ?? $city->mediaGallery->first();
                $city->feature_image = $featuredImage?->media->url ?? null;
                unset($city->mediaGallery);

                return $city;
            });

        if ($cities->isEmpty()) {
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
                $featuredImage = $city->mediaGallery->firstWhere('is_featured', true)
                    ?? $city->mediaGallery->first();
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

    // ---------------------------getting all featured city with starting price for tours & experiences page-------------------------
    public function getFeaturedCitiesWithStartingPrice()
    {
        $cities = City::with([
            'state.country.regions',
            'mediaGallery.media',
        ])
            ->withCount('activities')
            ->where('featured_destination', true)
            ->get()
            ->map(function ($city) {
                $featuredImage = $city->mediaGallery->firstWhere('is_featured', true)
                    ?? $city->mediaGallery->first();
                $featureImageUrl = $featuredImage?->media->url ?? null;

                // Get the lowest starting price and currency for this city
                $priceData = $city->lowestStartingPrice();

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
                    'starting_price' => $priceData['starting_price'],
                    'currency' => $priceData['currency'],
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
            $featuredImage = $city->mediaGallery->firstWhere('is_featured', true)
                ?? $city->mediaGallery->first();
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
            $featuredImage = $city->mediaGallery->firstWhere('is_featured', true)
                ?? $city->mediaGallery->first();
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
                'state' => $city->state ? [
                    'id' => $city->state->id,
                    'name' => $city->state->name,
                ] : null,
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
            'search' => 'nullable|string|max:100',
            'categories' => ['nullable', 'string', 'max:500', 'regex:/^[A-Za-z0-9-]+(?:,[A-Za-z0-9-]+)*$/'],
            'tags' => ['nullable', 'string', 'max:500', 'regex:/^[A-Za-z0-9-]+(?:,[A-Za-z0-9-]+)*$/'],
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gte:min_price',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'sort_by' => 'nullable|in:name_asc,name_desc,price_asc,price_desc,rating_desc,id_asc,id_desc',
            'item_type' => 'nullable|in:activity,itinerary,package',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $city = City::with('state.country.regions')->where('slug', $city_slug)->first();

        if (! $city) {
            return response()->json(['success' => false, 'message' => 'City not found.'], 404);
        }

        $categorySlugs = request()->has('categories') ? explode(',', request()->get('categories')) : [];
        $tagSlugs = request()->has('tags') ? explode(',', request()->get('tags')) : [];
        $minPrice = request()->get('min_price', 0);
        $maxPrice = request()->get('max_price', null);
        $minRating = (float) request()->get('min_rating', 0);
        $sortBy = request()->get('sort_by', 'id_desc');
        $itemType = request()->get('item_type', null);
        $search = request()->get('search');

        $categoryIds = ! empty($categorySlugs) ? Category::whereIn('slug', $categorySlugs)->pluck('id')->toArray() : [];
        $tagIds = ! empty($tagSlugs) ? Tag::whereIn('slug', $tagSlugs)->pluck('id')->toArray() : [];

        $activities = (! $itemType || $itemType === 'activity')
            ? Activity::whereHas('locations', fn ($query) => $query->where('city_id', $city->id))
                ->with(['pricing', 'groupDiscounts', 'categories.category', 'tags.tag', 'locations.city.state.country.regions', 'mediaGallery.media'])
                ->withCount(['reviews as reviews_count' => fn ($query) => $query->where('status', 'approved')])
                ->withAvg(['reviews as average_rating' => fn ($query) => $query->where('status', 'approved')], 'rating')
            : null;

        $itineraries = (! $itemType || $itemType === 'itinerary')
            ? Itinerary::whereHas('locations', fn ($query) => $query->where('city_id', $city->id))
                ->with([
                    'basePricing.variations',
                    'mediaGallery.media',
                    'categories.category',
                    'tags.tag',
                    'schedules.activities',
                    'schedules.transfers.transfer.route',
                    'schedules.transfers.transfer.pricingAvailability',
                ])
                ->withCount(['reviews as reviews_count' => fn ($query) => $query->where('status', 'approved')])
                ->withAvg(['reviews as average_rating' => fn ($query) => $query->where('status', 'approved')], 'rating')
            : null;

        $packages = (! $itemType || $itemType === 'package')
            ? Package::whereHas('locations', fn ($query) => $query->where('city_id', $city->id))
                ->where('private_package', false)
                ->with(['basePricing.variations', 'mediaGallery.media', 'categories.category', 'tags.tag'])
                ->withCount(['reviews as reviews_count' => fn ($query) => $query->where('status', 'approved')])
                ->withAvg(['reviews as average_rating' => fn ($query) => $query->where('status', 'approved')], 'rating')
            : null;

        $categoryPivots = collect();
        $tagPivots = collect();
        if (! $itemType || $itemType === 'activity') {
            $categoryPivots = $categoryPivots->merge(ActivityCategory::whereHas('activity.locations', fn ($query) => $query->where('city_id', $city->id))->with('category')->get());
            $tagPivots = $tagPivots->merge(ActivityTag::whereHas('activity.locations', fn ($query) => $query->where('city_id', $city->id))->with('tag')->get());
        }
        if (! $itemType || $itemType === 'itinerary') {
            $categoryPivots = $categoryPivots->merge(ItineraryCategory::whereHas('itinerary.locations', fn ($query) => $query->where('city_id', $city->id))->with('category')->get());
            $tagPivots = $tagPivots->merge(ItineraryTag::whereHas('itinerary.locations', fn ($query) => $query->where('city_id', $city->id))->with('tag')->get());
        }
        if (! $itemType || $itemType === 'package') {
            $categoryPivots = $categoryPivots->merge(PackageCategory::whereHas('package', fn ($query) => $query->where('private_package', false)->whereHas('locations', fn ($location) => $location->where('city_id', $city->id)))->with('category')->get());
            $tagPivots = $tagPivots->merge(PackageTag::whereHas('package', fn ($query) => $query->where('private_package', false)->whereHas('locations', fn ($location) => $location->where('city_id', $city->id)))->with('tag')->get());
        }

        $availableCategories = $categoryPivots
            ->map(fn ($pivot) => $pivot->category)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn ($category) => ['slug' => $category->slug, 'name' => $category->name]);

        $availableTags = $tagPivots
            ->map(fn ($pivot) => $pivot->tag)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn ($tag) => ['slug' => $tag->slug, 'name' => $tag->name]);

        if ($search) {
            $activities?->where('name', 'like', "%{$search}%");
            $itineraries?->where('name', 'like', "%{$search}%");
            $packages?->where('name', 'like', "%{$search}%");
        }

        $hasUnknownCategories = count(array_unique($categorySlugs)) !== count($categoryIds);
        $hasUnknownTags = count(array_unique($tagSlugs)) !== count($tagIds);

        if ($hasUnknownCategories || $hasUnknownTags) {
            $activities?->whereRaw('1 = 0');
            $itineraries?->whereRaw('1 = 0');
            $packages?->whereRaw('1 = 0');
        } elseif (! empty($categoryIds)) {
            $activities?->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $itineraries?->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $packages?->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
        }

        if (! $hasUnknownCategories && ! $hasUnknownTags && ! empty($tagIds)) {
            $activities?->whereHas('tags', fn ($q) => $q->whereIn('tag_id', $tagIds));
            $itineraries?->whereHas('tags', fn ($q) => $q->whereIn('tag_id', $tagIds));
            $packages?->whereHas('tags', fn ($q) => $q->whereIn('tag_id', $tagIds));
        }

        if ($minPrice > 0) {
            $activities?->whereHas('pricing', fn ($query) => $query->where('regular_price', '>=', $minPrice));
            $itineraries?->whereHas('basePricing.variations')->whereDoesntHave('basePricing.variations', fn ($query) => $query->where('regular_price', '<', $minPrice));
            $packages?->whereHas('basePricing.variations')->whereDoesntHave('basePricing.variations', fn ($query) => $query->where('regular_price', '<', $minPrice));
        }
        if ($maxPrice !== null) {
            $activities?->whereHas('pricing', fn ($query) => $query->where('regular_price', '<=', $maxPrice));
            $itineraries?->whereHas('basePricing.variations', fn ($query) => $query->where('regular_price', '<=', $maxPrice));
            $packages?->whereHas('basePricing.variations', fn ($query) => $query->where('regular_price', '<=', $maxPrice));
        }

        $activities = $activities?->get() ?? collect();
        $itineraries = $itineraries?->get() ?? collect();
        $packages = $packages?->get() ?? collect();

        $allItems = collect()
            ->merge($activities->map(function ($activity) use ($city_slug) {
                $averageRating = round((float) ($activity->average_rating ?? 0), 1);
                $reviewsCount = (int) ($activity->reviews_count ?? 0);

                return [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'slug' => $activity->slug,
                    'item_type' => 'activity',
                    'city_slug' => $city_slug,
                    'featured' => $activity->featured_activity,
                    'featured_image' => $activity->mediaGallery->where('is_featured', true)->first()?->media?->url
                        ?? $activity->mediaGallery->first()?->media?->url,
                    'pricing' => $activity->pricing,
                    'average_rating' => $averageRating,
                    'reviews_count' => $reviewsCount,
                    'review_summary' => [
                        'average_rating' => $averageRating,
                        'total_reviews' => $reviewsCount,
                    ],
                    'categories' => $activity->categories->map(fn ($category) => [
                        'slug' => $category->category->slug,
                        'name' => $category->category->name,
                    ])->toArray(),
                    'tags' => $activity->tags->map(fn ($pivot) => [
                        'slug' => $pivot->tag?->slug,
                        'name' => $pivot->tag?->name,
                    ])->filter(fn ($tag) => $tag['slug'])->values()->toArray(),
                    '_sort_price' => (float) ($activity->pricing?->regular_price ?? 0),
                    'listing_price' => (float) ($activity->pricing?->regular_price ?? 0),
                ];
            }))
            ->merge($itineraries->map(function ($itinerary) use ($city_slug) {
                $averageRating = round((float) ($itinerary->average_rating ?? 0), 1);
                $reviewsCount = (int) ($itinerary->reviews_count ?? 0);

                return [
                    'id' => $itinerary->id,
                    'name' => $itinerary->name,
                    'slug' => $itinerary->slug,
                    'item_type' => 'itinerary',
                    'city_slug' => $city_slug,
                    'featured' => $itinerary->featured_itinerary,
                    'featured_image' => $itinerary->featured_image,
                    'base_pricing' => $itinerary->basePricing,
                    'schedule_total_price' => $itinerary->schedule_total_price,
                    'schedule_total_currency' => $itinerary->schedule_total_currency,
                    'average_rating' => $averageRating,
                    'reviews_count' => $reviewsCount,
                    'review_summary' => [
                        'average_rating' => $averageRating,
                        'total_reviews' => $reviewsCount,
                    ],
                    'categories' => $itinerary->categories->map(fn ($category) => [
                        'slug' => $category->category->slug,
                        'name' => $category->category->name,
                    ])->toArray(),
                    'tags' => $itinerary->tags->map(fn ($pivot) => [
                        'slug' => $pivot->tag?->slug,
                        'name' => $pivot->tag?->name,
                    ])->filter(fn ($tag) => $tag['slug'])->values()->toArray(),
                    '_sort_price' => (float) ($itinerary->schedule_total_price
                        ?? $itinerary->basePricing?->variations?->min('regular_price')
                        ?? 0),
                    'listing_price' => (float) ($itinerary->schedule_total_price
                        ?? $itinerary->basePricing?->variations?->min('regular_price')
                        ?? 0),
                ];
            }))
            ->merge($packages->map(function ($package) use ($city_slug) {
                $averageRating = round((float) ($package->average_rating ?? 0), 1);
                $reviewsCount = (int) ($package->reviews_count ?? 0);

                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'slug' => $package->slug,
                    'item_type' => 'package',
                    'city_slug' => $city_slug,
                    'featured' => $package->featured_package,
                    'featured_image' => $package->mediaGallery->where('is_featured', true)->first()?->media?->url
                        ?? $package->mediaGallery->first()?->media?->url,
                    'base_pricing' => $package->basePricing,
                    'average_rating' => $averageRating,
                    'reviews_count' => $reviewsCount,
                    'review_summary' => [
                        'average_rating' => $averageRating,
                        'total_reviews' => $reviewsCount,
                    ],
                    'categories' => $package->categories->map(fn ($category) => [
                        'slug' => $category->category->slug,
                        'name' => $category->category->name,
                    ])->toArray(),
                    'tags' => $package->tags->map(fn ($pivot) => [
                        'slug' => $pivot->tag?->slug,
                        'name' => $pivot->tag?->name,
                    ])->filter(fn ($tag) => $tag['slug'])->values()->toArray(),
                    '_sort_price' => (float) ($package->basePricing?->variations?->min('regular_price') ?? 0),
                    'listing_price' => (float) ($package->basePricing?->variations?->min('regular_price') ?? 0),
                ];
            }));

        if ($minRating > 0) {
            $allItems = $allItems
                ->filter(fn ($item) => (float) ($item['average_rating'] ?? 0) >= $minRating)
                ->values();
        }

        // Sorting
        $allItems = match ($sortBy) {
            'name_asc' => $allItems->sortBy('name'),
            'name_desc' => $allItems->sortByDesc('name'),
            'price_asc' => $allItems->sortBy('_sort_price'),
            'price_desc' => $allItems->sortByDesc('_sort_price'),
            'rating_desc' => $allItems->sortByDesc('average_rating'),
            'id_asc' => $allItems->sortBy('id'),
            default => $allItems->sortByDesc('id'),
        };

        // Pagination
        $perPage = (int) request()->get('per_page', 8);
        $page = (int) request()->get('page', 1);
        $total = $allItems->count();
        $paginatedItems = $allItems->forPage($page, $perPage)->values()->map(function ($item) {
            unset($item['_sort_price']);

            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $paginatedItems,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
            'per_page' => $perPage,
            'total' => $total,
            'available_categories' => $availableCategories,
            'available_tags' => $availableTags,
        ], 200);
    }
}
