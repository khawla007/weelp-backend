<?php

namespace App\Http\Controllers\Public;

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
        $region = Region::where('name', $region_slug)->firstOrFail();

        if (!$region) {
            return response()->json(['success' => false, 'message' => 'Region not found'], 404);
        }

        $cities = [];
    
        foreach ($region->countries as $country) {
            foreach ($country->states as $state) {
                $cities = array_merge($cities, $state->cities()->get()->toArray());
            }
        }
        
        if (collect($cities)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No cities found in this region'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $cities
        ], 200);
    }

    // -----------------------Code to get activity based on location type primary city with location details--------------------------
    // public function getActivityByCity($region_slug, $city_slug)
    public function getActivityByCity($city_slug)
    {
        $city = City::with(['state.country.regions'])->where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        $activities = Activity::whereHas('locations', function ($query) use ($city) {
            $query->where('city_id', $city->id)
                ->where('location_type', 'primary');
        })
        ->with(['pricing', 'groupDiscounts', 'categories.category', 'locations.city.state.country.regions'])
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
    public function getItinerariesByCity($city_slug)
    {

        $city = City::with(['state.country.regions'])->where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        // Itineraries ke saath schedules aur related data fetch karo
        $itineraries = $city->itineraries()->with([
            'basePricing.variations',
            'mediaGallery',
            'categories.category',
            'tags'
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
    public function getPackagesByCity($city_slug)
    {
        $city = City::where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        $packages = $city->packages()->with([
            'basePricing.variations',
            'mediaGallery',
            'categories.category',
            'tags'
        ])->where('featured_package', true)->get();

        // if ($packages->isEmpty()) {
        //     return response()->json(['message' => 'No packages found for this city.'], 404);
        // }
        if ($packages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No packages found for this city.'
            ], 404);
        }

        $formattedPackages = $packages->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'item_type' => $package->item_type,
                'featured_package' => $package->featured_package,
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
                'tags' => $package->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                })->toArray(),
                'base_pricing' => $package->basePricing,
                'media_gallery' => $package->mediaGallery,
            ];
        });

        // Tag list
        $tagList = $formattedPackages->flatMap(fn ($item) => $item['tags'])
        ->unique('id')
        ->values();

        if (collect($formattedPackages)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Packages not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $formattedPackages,
            'tag_list' => $tagList
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

        $categoryIds = Category::whereIn('slug', $categorySlugs)->pluck('id')->toArray();
        $tagIds = Tag::whereIn('slug', $tagSlugs)->pluck('id')->toArray();
        // dd($categoryIds, $tagIds);
        
        $activities = Activity::whereHas('locations', fn ($query) =>  
            $query->where('city_id', $city->id)
        )->with(['pricing', 'groupDiscounts', 'categories.category', 'locations.city.state.country.regions']);

        $itineraries = Itinerary::whereHas('locations', fn ($query) =>  
            $query->where('city_id', $city->id)
        )->with(['basePricing.variations', 'mediaGallery', 'categories.category', 'tags']);

        $packages = Package::whereHas('locations', fn ($query) =>  
            $query->where('city_id', $city->id)
        )->with(['basePricing.variations', 'mediaGallery', 'categories.category', 'tags']);

        
        if (!empty($categoryIds)) {
            $activities->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $itineraries->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $packages->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
        }

        if (!empty($tagIds)) {
            // $activities->whereHas('tags', fn ($q) => $q->whereIn('id', $tagIds));
            $itineraries->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
            $packages->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));

        }

        if ($maxPrice !== null) {
            $activities->whereHas('pricing', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
            $itineraries->whereHas('basePricing.variations', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
            $packages->whereHas('basePricing.variations', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
        }

        // if ($minRating > 0) {
        //     $activities->where('rating', '>=', $minRating);
        //     $itineraries->where('rating', '>=', $minRating);
        //     $packages->where('rating', '>=', $minRating);
        // }

        $activities = $activities->get();
        $itineraries = $itineraries->get();
        $packages = $packages->get();

        $allItems = collect()
            ->merge($activities->map(fn ($activity) => [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'item_type' => 'activity',
                'featured' => $activity->featured_activity,
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
        $perPage = 10;
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

    

    // -----------------------Code to get All Packages based on region with location details--------------------------
    public function getPackagesByRegion($region_slug)
    {
        // Find Region
        $region = Region::where('slug', $region_slug)->first();

        if (!$region) {
            return response()->json([
                'success' => false,
                'message' => 'Region not found'
            ], 404);
        }

        // Get city IDs linked to the region
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

        if ($cityIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No cities and Packages found in this region'
            ], 404);
        }

        // Get all packages linked to those cities using the pivot table
        $packages = Package::whereHas('locations', function ($query) use ($cityIds) {
            $query->whereIn('city_id', $cityIds);
        })
            ->with([
                'basePricing.variations',
                'mediaGallery',
                'categories.category',
                'tags.tag',
                'locations.city.state.country.regions'
            ])
            ->where('featured_package', true)
            ->get();

        if ($packages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No packages found for this region.'
            ], 404);
        }

        // Format the package data
        $formattedPackages = $packages->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'item_type' => $package->item_type,
                'featured_package' => $package->featured_package,
                'locations' => $package->locations->map(function ($location) {
                    $city = $location->city;
                    return [
                        'city_id' => $city->id,
                        'city' => $city->name,
                        'state_id' => $city->state->id ?? null,
                        'state' => $city->state->name ?? null,
                        'country_id' => $city->state->country->id ?? null,
                        'country' => $city->state->country->name ?? null,
                        'region_id' => $city->state->country->regions->first()->id ?? null,
                        'region' => $city->state->country->regions->first()->name ?? null,
                    ];
                })->toArray(),
                'categories' => $package->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id ?? null,
                        'name' => $category->category->name ?? null,
                    ];
                })->toArray(),
                'tags' => $package->tags->map(function ($tag) {
                    return [
                        'id' => $tag->tag->id ?? null,
                        'name' => $tag->tag->name ?? null,
                    ];
                })->toArray(),
                'base_pricing' => $package->basePricing,
                'media_gallery' => $package->mediaGallery,
            ];
        });
        
        // tag list
        $tagList = $formattedPackages->flatMap(fn ($item) => $item['tags'])
        ->unique('id')
        ->values();

        if (collect($formattedPackages)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Packages not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $formattedPackages,
            'tag_list' => $tagList
        ], 200);
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
        $itemTypes = request()->has('item_types') ? explode(',', request()->get('item_types')) : [];

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
        // Fetch Activities, Itineraries, and Packages
        $activities = Activity::whereHas('locations', fn ($query) =>  
            $query->whereIn('city_id', $validCityIds->isEmpty() ? $cities->pluck('id') : $validCityIds)
            ->when($itemTypes, fn ($query) => $query->where('item_type', $itemTypes))
        )->with(['pricing', 'groupDiscounts', 'categories.category', 'locations.city.state.country.regions']);

        $itineraries = Itinerary::whereHas('locations', fn ($query) =>  
            $query->whereIn('city_id', $validCityIds->isEmpty() ? $cities->pluck('id') : $validCityIds)
            ->when($itemTypes, fn ($query) => $query->where('item_type', $itemTypes))
        )->with(['basePricing.variations', 'mediaGallery', 'categories.category', 'tags']);

        $packages = Package::whereHas('locations', fn ($query) =>  
            $query->whereIn('city_id', $validCityIds->isEmpty() ? $cities->pluck('id') : $validCityIds)
            ->when($itemTypes, fn ($query) => $query->where('item_type', $itemTypes))
        )->with(['basePricing.variations', 'mediaGallery', 'categories.category', 'tags']);
        
        $validItemTypes = ['activity', 'itinerary', 'package'];
        if (!empty($itemTypes) && array_diff($itemTypes, $validItemTypes)) {
            return response()->json(['success' => false, 'message' => 'Invalid item type.'], 404);
        }

        // Apply Category Filter
        // if (!empty($categoryIds)) {
        //     $activities->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
        //     $itineraries->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
        //     $packages->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
        // }

        if (!empty($categoryIds)) {
            $activities = $activities->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $itineraries = $itineraries->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $packages = $packages->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
        }

        // Apply Tag Filter
        // if (!empty($tagIds)) {
        //     $itineraries->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
        //     $packages->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
        // }

        if (!empty($tagIds)) {
            $itineraries = $itineraries->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
            $packages = $packages->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
        }

        // Apply Price Filter
        // if ($maxPrice !== null) {
        //     $activities->whereHas('pricing', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
        //     $itineraries->whereHas('basePricing.variations', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
        //     $packages->whereHas('basePricing.variations', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
        // }

        if ($maxPrice !== null) {
            $activities = $activities->whereHas('pricing', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
            $itineraries = $itineraries->whereHas('basePricing.variations', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
            $packages = $packages->whereHas('basePricing.variations', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
        }

        // Retrieve Data
        $activities = $activities->get();
        $itineraries = $itineraries->get();
        $packages = $packages->get();

        // **Check for Empty Data**
        if (!empty($citySlugs) && count($selectedCities) && $activities->isEmpty() && $itineraries->isEmpty() && $packages->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No items found .'], 404);
        }

        if (!empty($categorySlugs) && ($activities->isEmpty() && $itineraries->isEmpty() && $packages->isEmpty())) {
            return response()->json(['success' => false, 'message' => 'Category has no items.'], 404);
        }

        if (!empty($tagSlugs) && ($itineraries->isEmpty() && $packages->isEmpty())) {
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
                'pricing' => $itinerary->basePricing,
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
                        'state_id' => $city->state?->id,
                        'state' => $city->state?->name,
                        'country_id' => $city->state?->country?->id,
                        'country' => $city->state?->country?->name,
                        'region_id' => $city->state?->country?->regions->first()?->id,
                        'region' => $city->state?->country?->regions->first()?->name,
                    ];
                }),
            ]))
            ->merge($packages->map(fn ($package) => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'item_type' => 'package',
                'featured' => $package->featured_package,
                'pricing' => $package->basePricing,
                'categories' => $package->categories->map(fn ($category) => [
                    'slug' => $category->category->slug,
                    'name' => $category->category->name,
                ])->toArray(),
                'tags' => $package->tags->map(fn ($tag) => [
                    'slug' => $tag->slug,
                    'name' => $tag->name,
                ])->toArray(),
                'locations' => $package->locations->map(function ($location) {
                    $city = $location->city;
                    return [
                        'city_id' => $city->id,
                        'city' => $city->name,
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
                $allItems = $allItems->sortBy(fn ($item) => (float) ($item['base_pricing']['variations'][0]['sale_price'] ?? $item['price']['regular_price'] ?? 0));
                break;
            case 'price_desc':
                $allItems = $allItems->sortByDesc(fn ($item) => (float) ($item['base_pricing']['variations'][0]['sale_price'] ?? $item['price']['regular_price'] ?? 0));
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
