<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Region;
use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Package;
use App\Models\City;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;

class PublicRegionController extends Controller
{

    // -----------------------Get all Regions--------------------------
    public function getRegions()
    {
        $regions = Region::all();
    
        if ($regions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No regions found'
            ], 404);
        }
    
        return response()->json([
            'success' => true,
            'data' => $regions->map(function ($region) {
                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'slug' => $region->slug,
                    // 'description' => $region->description,
                    // 'image_url' => $region->image_url,
                ];
            })
        ], 200);
    }

    // --------------------Getting Region Singel page details----------------------
    public function getRegionDetails($slug)
    {
        $region = Region::with('countries')->where('slug', $slug)->first();

        if (!$region) {
            return response()->json([
                'success' => false,
                'message' => 'Region not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $region->id,
                'name' => $region->name,
                'slug' => $region->slug,
                'description' => $region->description,
                'image_url' => $region->image_url,
                'countries' => $region->countries->map(function ($country) {
                    return [
                        'id' => $country->id,
                        'name' => $country->name,
                        'slug' => $country->slug
                    ];
                })
            ]
        ], 200);
    }

    // ------------------Getting Cities behalf of Region-------------------
    public function getCitiesByRegion($region_slug)
    {
        $region = Region::where('slug', $region_slug)->first();

        if (!$region) {
            return response()->json([
                'success' => false,
                'message' => 'Region not found'
            ], 404);
        }

        $cities = City::with('mediaGallery.media')
            ->withCount('activities')
            ->whereExists(function ($query) use ($region) {
                $query->select(DB::raw(1))
                    ->from('states')
                    ->whereColumn('cities.state_id', 'states.id')
                    ->whereExists(function ($subQuery) use ($region) {
                        $subQuery->select(DB::raw(1))
                            ->from('countries')
                            ->whereColumn('states.country_id', 'countries.id')
                            ->whereExists(function ($innerQuery) use ($region) {
                                $innerQuery->select(DB::raw(1))
                                    ->from('region_country')
                                    ->whereColumn('countries.id', 'region_country.country_id')
                                    ->where('region_country.region_id', $region->id);
                            });
                    });
            })
            ->get()
            ->map(function ($city) {
                $featuredImage = $city->mediaGallery->firstWhere('is_featured', true)
                    ?? $city->mediaGallery->first();
                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'slug' => $city->slug,
                    'description' => $city->description,
                    'featured_image' => $featuredImage?->media?->url,
                    'activities_count' => $city->activities_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $cities
        ], 200);
    }

    // -----------------------Code to get activity based on location type primary city with location details--------------------------
    // public function getActivityByCity($region_slug, $city_slug)
    public function getFeaturedActivitiesByCity($city_slug)
    {
        $city = City::with(['state.country.regions'])->where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        $activities = Activity::whereHas('locations', function ($query) use ($city) {
            $query->where('city_id', $city->id)
                ->where('location_type', 'primary');
        })
        ->with(['pricing', 'groupDiscounts', 'categories.category', 'locations.city.state.country.regions', 'mediaGallery.media'])
        ->where('featured_activity', true)
        ->get();

        if ($activities->isEmpty()) {
            return response()->json(['message' => 'No activities found for this city.'], 404);
        }

        $formattedActivities = $activities->map(function ($activity) {
            $primaryLocation = $activity->locations->where('location_type', 'primary')->first();

            return [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'item_type' => $activity->item_type,
                'featured_activity' => $activity->featured_activity,
                'featured_image' => $activity->mediaGallery->where('is_featured', true)->first()?->media?->url
                    ?? $activity->mediaGallery->first()?->media?->url,
                'pricing' => $activity->pricing,
                'groupDiscounts' => $activity->groupDiscounts,
                'categories' => $activity->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                })->toArray(),

                //  All Locations (including primary + additional)
                'locations' => $activity->locations->map(function ($location) {
                    $city = $location->city;

                    return [
                        'location_type' => $location->location_type,
                        'location_label' => $location->location_label,
                        'duration' => $location->duration,
                        'city_id' => $city->id,
                        'city' => $city->name,
                        'state_id' => $city->state ? $city->state->id : null,
                        'state' => $city->state ? $city->state->name : null,
                        'country_id' => $city->state && $city->state->country ? $city->state->country->id : null,
                        'country' => $city->state && $city->state->country ? $city->state->country->name : null,
                        'region_id' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
                            ? $city->state->country->regions->first()->id
                            : null,
                        'region' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
                            ? $city->state->country->regions->first()->name
                            : null,
                    ];
                }),
            ];
        });

        // return response()->json($formattedActivities);
        if (collect($formattedActivities)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Activities not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $formattedActivities
        ], 200);
    }

    // -----------------------Code to get Itineraries based on city with location details--------------------------
    // public function getItinerariesByCity($region_slug, $city_slug)
    public function getFeaturedItinerariesByCity($city_slug)
    {

        $city = City::with(['state.country.regions'])->where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        // Itineraries ke saath schedules aur related data fetch karo
        $itineraries = $city->itineraries()->with([
            'basePricing.variations',
            'mediaGallery.media',
            'categories.category',
            'tags',
            'schedules.activities',
            'schedules.transfers.transfer.route',
            'schedules.transfers.transfer.pricingAvailability',
        ])->where('featured_itinerary', true)->get();

        if ($itineraries->isEmpty()) {
            return response()->json(['message' => 'No itineraries found for this city.'], 404);
        }

        $formattedItineraries = $itineraries->map(function ($itinerary) {
            return [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'item_type' => $itinerary->item_type,
                'featured_itinerary' => $itinerary->featured_itinerary,
                'featured_image' => $itinerary->mediaGallery->where('is_featured', true)->first()?->media?->url
                    ?? $itinerary->mediaGallery->first()?->media?->url,
                'locations' => $itinerary->locations->map(function ($location) {
                    $city = $location->city;
                    return [
                        'city_id' => $city->id,
                        'city' => $city->name,
                        'state_id' => $city->state ? $city->state->id : null,
                        'state' => $city->state ? $city->state->name : null,
                        'country_id' => $city->state && $city->state->country ? $city->state->country->id : null,
                        'country' => $city->state && $city->state->country ? $city->state->country->name : null,
                        'region_id' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
                            ? $city->state->country->regions->first()->id
                            : null,
                        'region' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
                            ? $city->state->country->regions->first()->name
                            : null,
                    ];
                }),
                'categories' => $itinerary->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                })->toArray(),
                'tags' => $itinerary->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                })->toArray(),
                'schedule_total_price' => $itinerary->schedule_total_price,
                'schedule_total_currency' => $itinerary->schedule_total_currency,
                'base_pricing' => $itinerary->basePricing,
                'media_gallery' => $itinerary->mediaGallery,
            ];
        });

        // return response()->json([
        //     'data' => $formattedItineraries
        // ]);
        if (collect($formattedItineraries)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Itineraries not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $formattedItineraries
        ], 200);
    }

    // -----------------------Code to get Packages based on city with location details--------------------------

    // public function getPackagesByCity($region_slug, $city_slug)
    public function getFeaturedPackagesByCity($city_slug)
    {
        $city = City::where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        // All tags from ALL city packages (unaffected by filters/pagination)
        $allTags = $city->packages()->with('tags.tag')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->filter(fn ($pt) => $pt->tag !== null)
            ->map(fn ($pt) => ['id' => $pt->tag->id, 'name' => $pt->tag->name])
            ->unique('id')
            ->values();

        // Build query with filters
        $query = $city->packages()->with([
            'basePricing.variations',
            'mediaGallery.media',
            'categories.category',
            'tags.tag'
        ]);

        // Tag filter — filter via the tag relationship through PackageTag
        $tagNames = request()->has('tags') ? array_filter(explode(',', request()->get('tags'))) : [];
        if (!empty($tagNames)) {
            $query->whereHas('tags.tag', fn ($q) => $q->whereIn('name', $tagNames));
        }

        $packages = $query->get();

        $formattedPackages = $packages->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'item_type' => $package->item_type,
                'featured_package' => $package->featured_package,
                'featured_image' => $package->mediaGallery->where('is_featured', true)->first()?->media?->url
                    ?? $package->mediaGallery->first()?->media?->url,
                'locations' => $package->locations->map(function ($location) {
                    $city = $location->city;
                    return [
                        'city_id' => $city->id,
                        'city' => $city->name,
                        'state_id' => $city->state ? $city->state->id : null,
                        'state' => $city->state ? $city->state->name : null,
                        'country_id' => $city->state && $city->state->country ? $city->state->country->id : null,
                        'country' => $city->state && $city->state->country ? $city->state->country->name : null,
                        'region_id' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
                            ? $city->state->country->regions->first()->id
                            : null,
                        'region' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
                            ? $city->state->country->regions->first()->name
                            : null,
                    ];
                }),
                'categories' => $package->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                })->toArray(),
                'tags' => $package->tags->filter(fn ($pt) => $pt->tag !== null)->map(function ($pt) {
                    return [
                        'id' => $pt->tag->id,
                        'name' => $pt->tag->name,
                    ];
                })->values()->toArray(),
                'base_pricing' => $package->basePricing,
                'media_gallery' => $package->mediaGallery,
            ];
        });

        // Sorting
        $sortBy = request()->get('sort_by', 'id_desc');
        switch ($sortBy) {
            case 'name_asc':
                $formattedPackages = $formattedPackages->sortBy('name');
                break;
            case 'name_desc':
                $formattedPackages = $formattedPackages->sortByDesc('name');
                break;
            case 'price_asc':
                $formattedPackages = $formattedPackages->sortBy(fn ($item) => $item['base_pricing']?->variations?->first()?->regular_price ?? 0);
                break;
            case 'price_desc':
                $formattedPackages = $formattedPackages->sortByDesc(fn ($item) => $item['base_pricing']?->variations?->first()?->regular_price ?? 0);
                break;
            default:
                $formattedPackages = $formattedPackages->sortByDesc('id');
        }

        // Pagination
        $perPage = (int) request()->get('per_page', 8);
        $page = (int) request()->get('page', 1);
        $total = $formattedPackages->count();
        $paginatedItems = $formattedPackages->forPage($page, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $paginatedItems,
            'all_tags' => $allTags,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
            'per_page' => $perPage,
            'total' => $total,
        ], 200);
    }

    // ----------------------------getting all items including activity itinerary and package behalf of city---------------------------------

    public function getAllItemsByCity($city_slug)
    {
        // dd(request()->all());
        $city = City::with('state.country.regions')->where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['success' => 'false', 'message' => 'City not found.'], 404);
        }

        $categorySlugs = request()->has('categories') ? explode(',', request()->get('categories')) : [];
        $tagSlugs = request()->has('tags') ? explode(',', request()->get('tags')) : [];
        $minPrice = request()->get('min_price', 0);
        $maxPrice = request()->get('max_price', null);
        // $minRating = request()->get('min_rating', 0);
        $sortBy = request()->get('sort_by', 'id_desc'); // Default: Newest First
        $itemType = request()->get('item_type', null); // Filter by item type: activity, itinerary, package

        $categoryIds = Category::whereIn('slug', $categorySlugs)->pluck('id')->toArray();
        $tagIds = Tag::whereIn('slug', $tagSlugs)->pluck('id')->toArray();

        $activities = (!$itemType || $itemType === 'activity')
            ? Activity::whereHas('locations', fn ($query) =>
                $query->where('city_id', $city->id)
            )->with(['pricing', 'groupDiscounts', 'categories.category', 'locations.city.state.country.regions', 'mediaGallery.media'])
            : null;

        $itineraries = (!$itemType || $itemType === 'itinerary')
            ? Itinerary::whereHas('locations', fn ($query) =>
                $query->where('city_id', $city->id)
            )->with([
                'basePricing.variations',
                'mediaGallery.media',
                'categories.category',
                'tags',
                'schedules.activities',
                'schedules.transfers.transfer.route',
                'schedules.transfers.transfer.pricingAvailability',
            ])
            : null;

        $packages = (!$itemType || $itemType === 'package')
            ? Package::whereHas('locations', fn ($query) =>
                $query->where('city_id', $city->id)
            )->with(['basePricing.variations', 'mediaGallery.media', 'categories.category', 'tags'])
            : null;

        
        if (!empty($categoryIds)) {
            $activities?->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $itineraries?->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $packages?->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
        }

        if (!empty($tagIds)) {
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
                'featured' => $activity->featured_activity,
                'featured_image' => $activity->mediaGallery->where('is_featured', true)->first()?->media?->url
                    ?? $activity->mediaGallery->first()?->media?->url,
                'pricing' => $activity->pricing,
                // 'rating' => $activity->rating,
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
                'featured' => $itinerary->featured_itinerary,
                'featured_image' => $itinerary->mediaGallery->where('is_featured', true)->first()?->media?->url
                    ?? $itinerary->mediaGallery->first()?->media?->url,
                'schedule_total_price' => $itinerary->schedule_total_price,
                'schedule_total_currency' => $itinerary->schedule_total_currency,
                'base_pricing' => $itinerary->basePricing,
                // 'rating' => $itinerary->rating,
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
                'featured' => $package->featured_package,
                'featured_image' => $package->mediaGallery->where('is_featured', true)->first()?->media?->url
                    ?? $package->mediaGallery->first()?->media?->url,
                'base_pricing' => $package->basePricing,
                // 'rating' => $package->rating,
                'categories' => $package->categories->map(fn ($category) => [
                    'slug' => $category->category->slug,
                    'name' => $category->category->name,
                ])->toArray(),
                'tags' => $package->tags->map(fn ($tag) => [
                    'slug' => $tag->slug,
                    'name' => $tag->name,
                ])->toArray(),
            ]));

        switch ($sortBy) {
            case 'name_asc':
                $allItems = $allItems->sortBy('name');
                break;
            case 'name_desc':
                $allItems = $allItems->sortByDesc('name');
                break;
            case 'price_asc':
                $allItems = $allItems->sortBy(fn ($item) => $item['base_pricing']['regular_price'] ?? $item['pricing']['regular_price'] ?? 0);
                break;
            case 'price_desc':
                $allItems = $allItems->sortByDesc(fn ($item) => $item['base_pricing']['regular_price'] ?? $item['pricing']['regular_price'] ?? 0);
                break;
            case 'rating_desc':
                $allItems = $allItems->sortByDesc('rating');
                break;
            case 'id_asc':
                $allItems = $allItems->sortBy('id');
                break;
            default:
                $allItems = $allItems->sortByDesc('id'); // Newest First
        }

        // Pagination
        $perPage = (int) request()->get('per_page', 8);
        $page = request()->get('page', 1);
        $paginatedItems = $allItems->forPage($page, $perPage);

        // Categories List
        $categoriesList = $allItems->flatMap(fn ($item) => 
            match ($item['item_type']) {
                'activity' => Activity::find($item['id'])->categories->map(fn ($category) => [
                    'id' => $category->category->id,
                    'name' => $category->category->name,
                ]),
                'itinerary' => Itinerary::find($item['id'])->categories->map(fn ($category) => [
                    'id' => $category->category->id,
                    'name' => $category->category->name,
                ]),
                'package' => Package::find($item['id'])->categories->map(fn ($category) => [
                    'id' => $category->category->id,
                    'name' => $category->category->name,
                ]),
                default => [],
            }
        )->unique('id')->values();

        // Response
        return response()->json([
            'success' => 'true',
            'data' => $paginatedItems->values(),
            // 'category_list' => $categoriesList,
            'current_page' => (int) $page,
            'last_page' => ceil($allItems->count() / $perPage),
            'per_page' => $perPage,
            'total' => $allItems->count(),
        ], 200);
    }

    // -----------------------Code to get Itineraries based on region--------------------------
    public function getItinerariesByRegion($region_slug): \Illuminate\Http\JsonResponse
    {
        $region = Region::where('slug', $region_slug)->first();
        if (! $region) {
            return response()->json(['success' => false, 'message' => 'Region not found'], 404);
        }

        $cityIds = City::whereExists(function ($query) use ($region) {
            $query->select(DB::raw(1))
                ->from('states')
                ->whereColumn('cities.state_id', 'states.id')
                ->whereExists(function ($subQuery) use ($region) {
                    $subQuery->select(DB::raw(1))
                        ->from('countries')
                        ->whereColumn('states.country_id', 'countries.id')
                        ->whereExists(function ($innerQuery) use ($region) {
                            $innerQuery->select(DB::raw(1))
                                ->from('region_country')
                                ->whereColumn('countries.id', 'region_country.country_id')
                                ->where('region_country.region_id', $region->id);
                        });
                });
        })->pluck('id');

        $query = Itinerary::with([
            'locations.city.state.country.regions',
            'schedules.activities',
            'schedules.transfers.transfer.route',
            'schedules.transfers.transfer.pricingAvailability',
            'basePricing.variations',
            'basePricing.blackoutDates',
            'inclusionsExclusions',
            'mediaGallery.media',
            'seo',
            'categories.category',
            'attributes',
            'tags.tag',
        ])
        ->where('featured_itinerary', true)
        ->whereHas('locations', fn($q) => $q->whereIn('city_id', $cityIds));

        $tagNames = request()->has('tags') ? array_filter(explode(',', request()->get('tags'))) : [];
        if (! empty($tagNames)) {
            $query->whereHas('tags.tag', fn($q) => $q->whereIn('name', $tagNames));
        }

        $itineraries = $query->get();

        $allTags = $itineraries->pluck('tags')->flatten()
            ->filter(fn($pt) => $pt->tag !== null)
            ->map(fn($pt) => ['id' => $pt->tag->id, 'name' => $pt->tag->name, 'is_featured' => (bool) $pt->tag->is_featured])
            ->unique('id')
            ->values();

        $formattedItineraries = $itineraries->map(function ($itinerary) {
            return [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'featured_itinerary' => $itinerary->featured_itinerary,
                'description' => $itinerary->description,
                'schedule_total_price' => $itinerary->schedule_total_price,
                'schedule_total_currency' => $itinerary->schedule_total_currency,
                'item_type' => $itinerary->item_type,
                'featured_image' => $itinerary->featured_image,
                'city_slug' => $itinerary->locations->first()?->city?->slug,
                'locations' => $itinerary->locations->map(function ($location) {
                    $city = $location->city;
                    return [
                        'city_id' => $city?->id,
                        'city' => $city?->name,
                        'state_id' => $city?->state ? $city->state->id : null,
                        'state' => $city?->state ? $city->state->name : null,
                        'country_id' => $city?->state && $city->state->country ? $city->state->country->id : null,
                        'country' => $city?->state && $city->state->country ? $city->state->country->name : null,
                        'region_id' => $city?->state && $city->state->country && $city->state->country->regions->isNotEmpty()
                            ? $city->state->country->regions->first()->id
                            : null,
                        'region' => $city?->state && $city->state->country && $city->state->country->regions->isNotEmpty()
                            ? $city->state->country->regions->first()->name
                            : null,
                    ];
                }),
                'categories' => $itinerary->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                })->toArray(),
                'attributes' => $itinerary->attributes->map(function ($attribute) {
                    return [
                        'name' => $attribute->attribute->name,
                        'value' => $attribute->attribute_value,
                    ];
                }),
                'tags' => $itinerary->tags->filter(fn($pt) => $pt->tag !== null)->map(function ($tag) {
                    return [
                        'id' => $tag->tag->id,
                        'name' => $tag->tag->name,
                    ];
                })->values()->toArray(),
                'media_gallery' => $itinerary->mediaGallery->map(function ($media) {
                    return [
                        'id' => $media->media->id,
                        'name' => $media->media->name,
                        'alt_text' => $media->media->alt_text,
                        'url' => $media->media->url,
                        'is_featured' => (bool) $media->is_featured,
                    ];
                })->toArray(),
                'seo' => $itinerary->seo ? [
                    'meta_title' => $itinerary->seo->meta_title,
                    'meta_description' => $itinerary->seo->meta_description,
                    'keywords' => $itinerary->seo->keywords,
                ] : null,
                'base_pricing' => $itinerary->basePricing,
            ];
        });

        $sortBy = request()->get('sort_by', 'id_desc');
        $formattedItineraries = match ($sortBy) {
            'name_asc'   => $formattedItineraries->sortBy('name'),
            'name_desc'  => $formattedItineraries->sortByDesc('name'),
            'price_asc'  => $formattedItineraries->sortBy(fn($item) => $item['base_pricing']?->variations?->first()?->regular_price ?? 0),
            'price_desc' => $formattedItineraries->sortByDesc(fn($item) => $item['base_pricing']?->variations?->first()?->regular_price ?? 0),
            default      => $formattedItineraries->sortByDesc('id'),
        };

        $perPage = (int) request()->get('per_page', 8);
        $page = (int) request()->get('page', 1);
        $total = $formattedItineraries->count();
        $paginatedItems = $formattedItineraries->forPage($page, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $paginatedItems,
            'all_tags' => $allTags,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }

    public function getAllItemsByRegion($region_slug)
    {
        $region = Region::with('countries.states.cities')->where('slug', $region_slug)->first();

        if (!$region) {
            return response()->json(['success' => 'false', 'message' => 'Region not found.'], 404);
        }

        $cities = $region->countries->flatMap(fn ($country) =>  
            $country->states->flatMap(fn ($state) => $state->cities)
        );

        if ($cities->isEmpty()) {
            return response()->json([
                'success' => 'false',
                'message' => 'No cities found under this region.'
            ], 404);
        }

        // **New City Filter & Validation**
        $citySlugs = request()->has('city') ? explode(',', request()->get('city')) : [];
        $selectedCities = City::whereIn('slug', $citySlugs)->get();

        // Extract IDs of cities found in the backend
        $selectedCityIds = $selectedCities->pluck('id')->toArray();

        // Ensure all requested cities exist
        if (!empty($citySlugs) && count($selectedCities) !== count($citySlugs)) {
            return response()->json(['success' => false, 'message' => 'One or more cities not found.'], 404);
        }

        // Ensure all requested cities belong to the region
        $validCityIds = $cities->pluck('id')->intersect($selectedCityIds);

        if (!empty($citySlugs) && $validCityIds->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Selected city does not belong to this region.'], 404);
        }

        // Get Filters from Request
        $categorySlugs = request()->has('categories') ? explode(',', request()->get('categories')) : [];
        $tagSlugs = request()->has('tags') ? explode(',', request()->get('tags')) : [];
        $minPrice = request()->get('min_price', 0);
        $maxPrice = request()->get('max_price', null);
        $sortBy = request()->get('sort_by', 'id_desc'); // Default: Newest First
        $itemType = request()->get('item_type');
        if ($itemType && !in_array($itemType, ['activity', 'itinerary'], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid item type.'], 404);
        }

        // Fetch Category and Tag IDs
        $categoryIds = Category::whereIn('slug', $categorySlugs)->pluck('id')->toArray();
        $tagIds = Tag::whereIn('slug', $tagSlugs)->pluck('id')->toArray();

        // check if category is not in backend
        if (!empty($categorySlugs) && empty($categoryIds)) {
            return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
        }
        // check if tag is not in backend
        if (!empty($tagSlugs) && empty($tagIds)) {
            return response()->json(['success' => false, 'message' => 'Tag not found.'], 404);
        }
        $cityIds = $validCityIds->isEmpty() ? $cities->pluck('id') : $validCityIds;

        $activities = (!$itemType || $itemType === 'activity')
            ? Activity::whereHas('locations', fn ($query) => $query->whereIn('city_id', $cityIds))
                ->with(['pricing', 'groupDiscounts', 'categories.category', 'locations.city.state.country.regions', 'mediaGallery.media'])
            : null;

        $itineraries = (!$itemType || $itemType === 'itinerary')
            ? Itinerary::whereHas('locations', fn ($query) => $query->whereIn('city_id', $cityIds))
                ->with([
                    'basePricing.variations',
                    'mediaGallery.media',
                    'categories.category',
                    'tags',
                    'schedules.activities',
                    'schedules.transfers.transfer.route',
                    'schedules.transfers.transfer.pricingAvailability',
                ])
            : null;

        if (!empty($categoryIds)) {
            $activities?->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $itineraries?->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
        }

        if (!empty($tagIds)) {
            $itineraries?->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
        }

        if ($maxPrice !== null) {
            $activities?->whereHas('pricing', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
            $itineraries?->whereHas('basePricing.variations', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
        }

        $activities = $activities?->get() ?? collect();
        $itineraries = $itineraries?->get() ?? collect();

        // **Check for Empty Data**
        if (!empty($citySlugs) && count($selectedCities) && $activities->isEmpty() && $itineraries->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No items found .'], 404);
        }

        if (!empty($categorySlugs) && ($activities->isEmpty() && $itineraries->isEmpty())) {
            return response()->json(['success' => false, 'message' => 'Category has no items.'], 404);
        }

        if (!empty($tagSlugs) && $itineraries->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Tag has no items.'], 404);
        }

        // Merge Results
        $allItems = collect()
            ->merge($activities->map(fn ($activity) => [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'item_type' => 'activity',
                'featured' => $activity->featured_activity,
                'city_slug' => $activity->locations->first()?->city?->slug,
                'featured_image' => $activity->mediaGallery->where('is_featured', true)->first()?->media?->url
                    ?? $activity->mediaGallery->first()?->media?->url,
                'pricing' => $activity->pricing,
                'categories' => $activity->categories->map(fn ($category) => [
                    'slug' => $category->category->slug,
                    'name' => $category->category->name,
                ])->toArray(),
                'locations' => $activity->locations->map(function ($location) {
                    $city = $location->city;
                    return [
                        'city_id' => $city->id,
                        'city' => $city->name,
                        'city_slug' => $city->slug,
                        'state_id' => $city->state?->id,
                        'state' => $city->state?->name,
                        'country_id' => $city->state?->country?->id,
                        'country' => $city->state?->country?->name,
                        'region_id' => $city->state?->country?->regions->first()?->id,
                        'region' => $city->state?->country?->regions->first()?->name,
                    ];
                }),
            ]))
            ->merge($itineraries->map(fn ($itinerary) => [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'item_type' => 'itinerary',
                'featured' => $itinerary->featured_itinerary,
                'featured_image' => $itinerary->featured_image,
                'city_slug' => $itinerary->locations->first()?->city?->slug,
                'base_pricing' => $itinerary->basePricing,
                'schedule_total_price' => $itinerary->schedule_total_price,
                'schedule_total_currency' => $itinerary->schedule_total_currency,
                'categories' => $itinerary->categories->map(fn ($category) => [
                    'slug' => $category->category->slug,
                    'name' => $category->category->name,
                ])->toArray(),
                'tags' => $itinerary->tags->map(fn ($tag) => [
                    'slug' => $tag->slug,
                    'name' => $tag->name,
                ])->toArray(),
                'locations' => $itinerary->locations->map(function ($location) {
                    $city = $location->city;
                    return [
                        'city_id' => $city->id,
                        'city' => $city->name,
                        'city_slug' => $city->slug,
                        'state_id' => $city->state?->id,
                        'state' => $city->state?->name,
                        'country_id' => $city->state?->country?->id,
                        'country' => $city->state?->country?->name,
                        'region_id' => $city->state?->country?->regions->first()?->id,
                        'region' => $city->state?->country?->regions->first()?->name,
                    ];
                }),
            ]));

        // Sorting
        switch ($sortBy) {
            case 'name_asc':
                $allItems = $allItems->sortBy('name');
                break;
            case 'name_desc':
                $allItems = $allItems->sortByDesc('name');
                break;
            case 'price_asc':
                $allItems = $allItems->sortBy(fn ($item) => (float) ($item['base_pricing']['variations'][0]['sale_price'] ?? $item['pricing']['regular_price'] ?? 0));
                break;
            case 'price_desc':
                $allItems = $allItems->sortByDesc(fn ($item) => (float) ($item['base_pricing']['variations'][0]['sale_price'] ?? $item['pricing']['regular_price'] ?? 0));
                break;
            case 'id_asc':
                $allItems = $allItems->sortBy('id');
                break;
            default:
                $allItems = $allItems->sortByDesc('id'); // Default: Newest First
        }

        // Pagination
        $perPage = 10;
        $page = request()->get('page', 1);
        $paginatedItems = $allItems->forPage($page, $perPage);

        // Response
        return response()->json([
            'success' => 'true',
            'data' => $paginatedItems->values(),
            'current_page' => (int) $page,
            'last_page' => ceil($allItems->count() / $perPage),
            'per_page' => $perPage,
            'total' => $allItems->count(),
        ], 200);
    }


    // -------------------------Getting places by city------------------------
    public function getPlacesByCity($region_slug, $city_slug)
    {
        $region = Region::where('name', $region_slug)->firstOrFail();
        $places = [];
    
        foreach ($region->countries as $country) {
            foreach ($country->states as $state) {
                $city = $state->cities()->where('slug', $city_slug)->first();
                if ($city) {
                    $places = $city->places()->get()->toArray();
                    break;
                }
            }
        }
    
        if (empty($places)) {
            return response()->json(['error' => 'City not found'], 404);
        }
    
        // return response()->json($places);
        if (collect($places)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Places not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $places
        ], 200);
    }

    // public function getStatesByCountry($region_slug, $country_slug)
    // {
    //     $region = Region::where('name', $region_slug)->firstOrFail();
    //     $country = $region->countries()->where('slug', $country_slug)->firstOrFail();
    
    //     // Fetching states from the country
    //     return response()->json($country->states()->get());
    // }

    // public function getCitiesByState($region_slug, $country_slug, $state_slug)
    // {
    //     $region = Region::where('name', $region_slug)->firstOrFail();
    //     $country = $region->countries()->where('slug', $country_slug)->firstOrFail();
    //     $state = $country->states()->where('slug', $state_slug)->firstOrFail();
    
    //     // Fetching cities from the state
    //     return response()->json($state->cities()->get());
    // }

    // public function getPlacesInCity($region_slug, $country_slug, $state_slug, $city_slug)
    // {
    //     $region = Region::where('name', $region_slug)->firstOrFail();
    //     $country = $region->countries()->where('slug', $country_slug)->firstOrFail();
    //     $state = $country->states()->where('slug', $state_slug)->firstOrFail();
    //     $city = $state->cities()->where('slug', $city_slug)->firstOrFail();
    
    //     // Fetching places from the city
    //     return response()->json($city->places()->get());
    // }    
}
