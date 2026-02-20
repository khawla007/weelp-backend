<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Package;
use App\Models\City;
use App\Models\Region;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PublicShopController extends Controller
{
    // public function index()
    // {
    //     $perPage = 8;
    //     $currentPage = request()->get('page', 1);

    //     $activities = Activity::with([
    //         'categories.category',
    //         'locations.city.state.country.regions',
    //         'pricing',
    //         'groupDiscounts',
    //         'earlyBirdDiscount',
    //     ])->get()->map(fn ($activity) => $this->formatItem($activity, 'activity'));

    //     $itineraries = Itinerary::with([
    //         'categories.category',
    //         'locations.city.state.country.regions',
    //         'basePricing.variations',
    //     ])->get()->map(fn ($itinerary) => $this->formatItem($itinerary, 'itinerary'));

    //     $packages = Package::with([
    //         'categories.category',
    //         'locations.city.state.country.regions',
    //         'basePricing.variations',
    //     ])->get()->map(fn ($package) => $this->formatItem($package, 'package'));

    //     // Collecting the list of categories of all shop items.
    //     $categoriesList = collect([]);

    //     $categoriesList = $categoriesList
    //     ->merge($activities->flatMap(function ($activity) {
    //         return $activity['categories'];
    //     }))
    //     ->merge($itineraries->flatMap(function ($itinerary) {
    //         return $itinerary['categories'];
    //     }))
    //     ->merge($packages->flatMap(function ($package) {
    //         return $package['categories'];
    //     }))
    //     ->unique('id')
    //     ->values();

    //     // Collectiing the list of city and region of all shop items
    //     $locationList = collect([]);

    //     $locationList = $locationList
    //         ->merge($activities->flatMap(function ($activity) {
    //             return $activity['locations']->map(function ($location) {
    //                 return [
    //                     'id' => $location['city_id'],
    //                     'name' => $location['city'],
    //                     'type' => 'city',
    //                 ];
    //             })->merge(
    //                 $activity['locations']->map(function ($location) {
    //                     return [
    //                         'id' => $location['region_id'],
    //                         'name' => $location['region'],
    //                         'type' => 'region', 
    //                     ];
    //                 })
    //             );
    //         }))
    //         ->merge($itineraries->flatMap(function ($itinerary) {
    //             return $itinerary['locations']->map(function ($location) {
    //                 return [
    //                     'id' => $location['city_id'],
    //                     'name' => $location['city'],
    //                     'type' => 'city',
    //                 ];
    //             })->merge(
    //                 $itinerary['locations']->map(function ($location) {
    //                     return [
    //                         'id' => $location['region_id'],
    //                         'name' => $location['region'],
    //                         'type' => 'region',
    //                     ];
    //                 })
    //             );
    //         }))
    //         ->merge($packages->flatMap(function ($package) {
    //             return $package['locations']->map(function ($location) {
    //                 return [
    //                     'id' => $location['city_id'],
    //                     'name' => $location['city'],
    //                     'type' => 'city',
    //                 ];
    //             })->merge(
    //                 $package['locations']->map(function ($location) {
    //                     return [
    //                         'id' => $location['region_id'],
    //                         'name' => $location['region'],
    //                         'type' => 'region',
    //                     ];
    //                 })
    //             );
    //         }))
    //         ->unique('id')
    //         ->values();
        

    //     $allItems = collect()
    //         ->merge($activities)
    //         ->merge($itineraries)
    //         ->merge($packages);

    //     $paginatedItems = new LengthAwarePaginator(
    //         $allItems->forPage($currentPage, $perPage)->values(),
    //         $allItems->count(),
    //         $perPage,
    //         $currentPage,
    //         ['path' => request()->url(), 'query' => request()->query()]
    //     );

    //     if ($currentPage > $paginatedItems->lastPage()) {
    //         return response()->json([
    //             'message' => 'No more items'
    //         ], 422);
    //     }

    //     // return response()->json([
    //     //     'success' => 'true',
    //     //     'data' => $paginatedItems->items(),
    //     //     'categories_list' => $categoriesList,
    //     //     'location_list' => $locationList,
    //     //     'current_page' => $paginatedItems->currentPage(),
    //     //     'per_page' => $paginatedItems->perPage(),
    //     //     'total' => $paginatedItems->total(),
    //     //     'last_page' => $paginatedItems->lastPage(),
    //     // ]);
    //     if ($paginatedItems->isEmpty()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Shop items not found'
    //         ], 404);
    //     }
        
    //     return response()->json([
    //         'success' => true,
    //         'data' => $paginatedItems->items(),
    //         'categories_list' => $categoriesList,
    //         'location_list' => $locationList,
    //         'current_page' => $paginatedItems->currentPage(),
    //         'per_page' => $paginatedItems->perPage(),
    //         'total' => $paginatedItems->total(),
    //         'last_page' => $paginatedItems->lastPage(),
    //     ], 200);
    // }

    // ------------**********************correct code of filtration*********************---------------------------

    public function index()
    {
        $perPage = 10;
        $page = request()->get('page', 1);
    
        // Slug-based filtering
        $regionSlug = request()->get('region');
        $citySlug = request()->get('city');
        $itemType = request()->get('item_type'); // NEW: item_type filter
    
        $region = $regionSlug ? Region::with('countries.states.cities')->where('slug', $regionSlug)->first() : null;

        if ($regionSlug && !$region) {
            return response()->json([
                'success' => 'false',
                'message' => 'Region not found'
            ], 404);
        }

        $cities = $region ? $region->countries->flatMap(fn ($country) => $country->states->flatMap(fn ($state) => $state->cities)) : collect();

        // If region exists but has no cities
        if ($region && $cities->isEmpty()) {
            return response()->json([
                'success' => 'false',
                'message' => 'City not found'
            ], 404);
        }

        $city = $citySlug ? City::where('slug', $citySlug)->first() : null;

        if ($citySlug && !$city) {
            return response()->json([
                'success' => 'false',
                'message' => 'City not found'
            ], 404);
        }

        $cityIds = $city ? [$city->id] : ($cities->isNotEmpty() ? $cities->pluck('id')->toArray() : []);
    
        // Filters
        $categorySlugs = request()->has('categories') ? explode(',', request()->get('categories')) : [];
        $tagSlugs = request()->has('tags') ? explode(',', request()->get('tags')) : [];
        $minPrice = request()->get('min_price', 0);
        $maxPrice = request()->get('max_price', null);
        $sortBy = request()->get('sort_by', 'id_desc');
        $featured = request()->filled('featured') ? request()->get('featured') === 'true' : null;
    
        // Fetch Category & Tag IDs
        $categoryIds = Category::whereIn('slug', $categorySlugs)->pluck('id')->toArray();
        $tagIds = Tag::whereIn('slug', $tagSlugs)->pluck('id')->toArray();

        // Fetch All Activities, Itineraries, Packages with item_type filter
        $activities = Activity::when(!empty($cityIds), fn ($query) => $query->whereHas('locations', fn ($q) => $q->whereIn('city_id', $cityIds)))
        ->when($itemType, fn ($query) => $query->where('item_type', $itemType))
        ->when($featured !== null, fn ($query) => $query->where('featured_activity', $featured))
        ->with(['pricing', 'groupDiscounts', 'categories.category', 'locations.city.state.country.regions']);

        $itineraries = Itinerary::when(!empty($cityIds), fn ($query) => $query->whereHas('locations', fn ($q) => $q->whereIn('city_id', $cityIds)))
            ->when($itemType, fn ($query) => $query->where('item_type', $itemType))
            ->when($featured !== null, fn ($query) => $query->where('featured_itinerary', $featured))
            ->with(['basePricing.variations', 'categories.category', 'tags']);

        $packages = Package::when(!empty($cityIds), fn ($query) => $query->whereHas('locations', fn ($q) => $q->whereIn('city_id', $cityIds)))
            ->when($itemType, fn ($query) => $query->where('item_type', $itemType))
            ->when($featured !== null, fn ($query) => $query->where('featured_package', $featured))
            ->with(['basePricing.variations', 'categories.category', 'tags']);
    
        // Apply Filters
        if (!empty($categoryIds)) {
            $activities->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $itineraries->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $packages->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds));
        } 
        
        if (!empty($categorySlugs) && empty($categoryIds)) {
            return response()->json([
                'success' => 'false',
                'message' => 'category not found'
            ], 200);
        }

        if (!empty($tagIds)) {
            $itineraries->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
            $packages->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
        }

        if (!empty($tagSlugs) && empty($tagIds)) {
            return response()->json([
                'success' => 'false',
                'message' => 'tag not found'
            ], 200);
        }
    
        if ($maxPrice !== null) {
            $activities->whereHas('pricing', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
            $itineraries->whereHas('basePricing.variations', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
            $packages->whereHas('basePricing.variations', fn ($q) => $q->whereBetween('regular_price', [$minPrice, $maxPrice]));
        }
    
        // Retrieve Data
        $activities = $activities->get();
        $itineraries = $itineraries->get();
        $packages = $packages->get();
    
        // Merge Results (Using formatItem)
        $allItems = collect()
            ->merge($activities->map(fn ($activity) => $this->formatItem($activity, 'activity')))
            ->merge($itineraries->map(fn ($itinerary) => $this->formatItem($itinerary, 'itinerary')))
            ->merge($packages->map(fn ($package) => $this->formatItem($package, 'package')));


        // Check if no items are found within the region's cities
        if ($region && $allItems->isEmpty()) {
            return response()->json([
                'success' => 'false',
                'message' => 'Item not found in this region'
            ], 404);
        }

        // Check if no items are found within the selected city
        if ($city && $allItems->isEmpty()) {
            return response()->json([
                'success' => 'false',
                'message' => 'Item not found in this city'
            ], 404);
        }

        // Check if no items exist at all
        if ($allItems->isEmpty()) {
            return response()->json([
                'success' => 'false',
                'message' => 'Item not found'
            ], 404);
        }
    
        // Sorting
        switch ($sortBy) {
            case 'name_asc':
                $allItems = $allItems->sortBy('name');
                break;
            case 'name_desc':
                $allItems = $allItems->sortByDesc('name');
                break;
            case 'price_asc':
                $allItems = $allItems->sortBy(fn ($item) => (float) ($item['price']['variations'][0]['sale_price'] ?? $item['price']['regular_price'] ?? 0));
                break;
            case 'price_desc':
                $allItems = $allItems->sortByDesc(fn ($item) => (float) ($item['price']['variations'][0]['sale_price'] ?? $item['price']['regular_price'] ?? 0));
                break;
            case 'id_asc':
                $allItems = $allItems->sortBy('id');
                break;
            default:
                $allItems = $allItems->sortByDesc('id'); // Default: Newest First
        }
      
    
        // Pagination
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


    private function formatItem($item, $type)
    {
        $price = null;
        $groupDiscount = null;
        $earlyBirdDiscount = null;
        $variations = null;

        switch ($type) {
            case 'activity':
                $price = $item->pricing ? [
                    'regular_price' => $item->pricing->regular_price,
                    'currency' => $item->pricing->currency,
                ] : null;
                $groupDiscount = $item->groupDiscounts ? $item->groupDiscounts->map(function ($discount) {
                    return [
                        'min_people' => $discount->min_people,
                        'discount_amount' => $discount->discount_amount,
                        'discount_type' => $discount->discount_type,
                    ];
                }) : [];
                $earlyBirdDiscount = $item->earlyBirdDiscount ? [
                    'days_before_start' => $item->earlyBirdDiscount->first()?->days_before_start,
                    'discount_amount' => $item->earlyBirdDiscount->first()?->discount_amount,
                    'discount_type' => $item->earlyBirdDiscount->first()?->discount_type,
                ] : null;
                $featured = $item->featured_activity;
                break;
            case 'itinerary':
            case 'package':
                $price = $item->basePricing ? [
                    'currency' => $item->basePricing->currency,
                    'availability' => $item->basePricing->availability,
                    'start_date' => $item->basePricing->start_date,
                    'end_date' => $item->basePricing->end_date,
                    'variations' => $item->basePricing->variations->map(function ($variation) {
                        return [
                            'id' => $variation->id,
                            'name' => $variation->name,
                            'regular_price' => $variation->regular_price,
                            'sale_price' => $variation->sale_price,
                            'max_guests' => $variation->max_guests,
                            'description' => $variation->description,
                        ];
                    })->toArray(),
                ] : null;
                $featured = ($type === 'itinerary') ? $item->featured_itinerary : $item->featured_package;
        }

        return [
            'id' => $item->id,
            'name' => $item->name,
            'slug' => $item->slug,
            'item_type' => $type,
            'featured' => $featured,
            'price' => $price,
            'group_discount' => $groupDiscount,
            'early_bird_discount' => $earlyBirdDiscount,
            'categories' => $item->categories->map(fn ($category) => [
                'id' => $category->category->id ?? null,
                'name' => $category->category->name ?? null,
            ])->filter(),

            'locations' => $item->locations->map(function ($location) {
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
        ];
    }

}
