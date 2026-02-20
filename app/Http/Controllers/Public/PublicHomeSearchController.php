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

class PublicHomeSearchController extends Controller
{
    // getting region and cities for Where to? search feild on home page.
    public function getRegionsAndCities()
    {
        // Get all regions
        $regions = Region::select('id', 'name')
            ->get()
            ->map(function ($region) {
                return [
                    'id' => 'region_' . $region->id, 
                    'name' => $region->name,
                    'type' => 'region'
                ];
            });

        // Get all cities
        $cities = City::select('id', 'name')
            ->get()
            ->map(function ($city) {
                return [
                    'id' => 'city_' . $city->id, 
                    'name' => $city->name,
                    'type' => 'city'
                ];
            });

        // Merge and sort
        $list = $regions->merge($cities)->sortBy('name')->values();

        if ($list->isEmpty()) {
            return response()->json([
                'success' => 'false',
                'message' => 'Locations not found'
            ]);
        }

        return response()->json([
            'success' => 'true',
            'data' => $list
        ]);
    }

    // Merging all activity, itinerary and packages in one function to return response in api
    public function homeSearch(Request $request)
    {


        $request->validate([
            'location'   => 'required|string',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'quantity'   => 'nullable|integer|min:1',
            'categories' => 'nullable|string', 
            'tags'       => 'nullable|string', 
            'featured' => 'nullable|string|in:true,false',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort_by'     => 'nullable|string|in:price_asc,price_desc,name_asc,name_desc,id_asc,id_desc',
            'page'       => 'nullable|integer|min:1',
            'item_type'  => 'nullable|string', // New filter added
        ]);

        $location = $request->location;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $quantity = $request->quantity;
        $categorySlugs = $request->categories ? explode(',', $request->categories) : [];
        $tagSlugs      = $request->tags ? explode(',', $request->tags) : [];
        $featured = $request->featured;
        $minPrice = $request->min_price;
        $maxPrice = $request->max_price;
        $sortBy = $request->sort_by;
        $page = $request->page ?? 1;
        $perPage = 4;
        $itemType = $request->item_type;

        $cityIds = $this->getCityIdsFromLocationSlug($location);
        // Get category & tag IDs from slugs
        $categoryIds = Category::whereIn('slug', $categorySlugs)->pluck('id')->toArray();
        $tagIds = Tag::whereIn('slug', $tagSlugs)->pluck('id')->toArray();

        $activities  = $this->searchActivities($cityIds, $startDate, $endDate, $quantity, $categoryIds, $tagIds, $sortBy, $minPrice, $maxPrice, $featured, $itemType);
        $itineraries = $this->searchItineraries($cityIds, $startDate, $endDate, $quantity, $categoryIds, $tagIds, $sortBy, $minPrice, $maxPrice, $featured, $itemType);
        $packages    = $this->searchPackages($cityIds, $startDate, $endDate, $quantity, $categoryIds, $tagIds, $sortBy, $minPrice, $maxPrice, $featured, $itemType);

        // Merge all items into a single list
        $allItems = $activities
            ->concat($itineraries)
            ->concat($packages);


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

        // Paginate (4 items per page)
        $paginatedItems = $allItems->forPage($page, $perPage)->values();

        // Get total pages
        $totalItems = $allItems->count();
        $totalPages = ceil($totalItems / $perPage);

        // Prepare category list
        $categoriesList = collect($allItems->flatMap(function ($item) {
            return $item['categories'];
        }))->unique('id')->values();

        $results = [
            'success' => 'true',
            'data' => $paginatedItems,
            // 'categories_list' => $categoriesList,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages
            ]
        ];

        if ($totalItems === 0) {
            return response()->json([
                'success' => 'false',
                'message' => 'No items found'
            ]);
        }

        return response()->json($results);
    }

    // Get City IDs based on Slug
    private function getCityIdsFromLocationSlug($slug)
    {
        $cityIds = [];
    
        $city = City::where('slug', $slug)->first();
        if ($city) {
            $cityIds[] = $city->id;
        }
    
        $region = Region::where('slug', $slug)->first();
        if ($region) {
            $regionCities = City::whereHas('state.country.regions', function ($query) use ($region) {
                $query->where('regions.id', $region->id); 
            })->pluck('id')->toArray();
    
            $cityIds = array_merge($cityIds, $regionCities);
        }
    
        return $cityIds;
    }

    // Activity Search Function

    // private function searchActivities($cityIds, $startDate, $endDate, $quantity)
    private function searchActivities($cityIds, $startDate, $endDate, $quantity, $categoryIds, $tagIds, $sortBy, $minPrice, $maxPrice, $featured, $itemType)
    {
        $query = Activity::with([
            'categories' => function ($q) {
                $q->with('category:id,name'); 
            },
            'pricing',
            'groupDiscounts',
            'earlyBirdDiscount'
        ])->whereHas('locations', function ($q) use ($cityIds) {
            $q->whereIn('city_id', $cityIds);
        });

        if ($startDate && $endDate) {
            $query->whereHas('availability', function ($q) use ($startDate, $endDate) {
                $q->where('date_based_activity', true)
                    ->where('start_date', '<=', $startDate)
                    ->where('end_date', '>=', $endDate);
            });
        }

        if ($quantity) {
            $query->whereHas('availability', function ($q) use ($quantity) {
                $q->where(function ($q) use ($quantity) {
                    $q->where('quantity_based_activity', false)
                        ->orWhere(function ($q) use ($quantity) {
                            $q->where('quantity_based_activity', true)
                                ->where('max_quantity', '>=', $quantity);
                        });
                });
            });
        }

        if ($featured !== null) {
            $query->where('featured_activity', (bool) $featured);
        }

        if ($minPrice || $maxPrice) {
            $query->whereHas('pricing', function ($q) use ($minPrice, $maxPrice) {
                if ($minPrice) {
                    $q->where('regular_price', '>=', $minPrice);
                }
                if ($maxPrice) {
                    $q->where('regular_price', '<=', $maxPrice);
                }
            });
        }

        if ($itemType) {
            $query->where('item_type', $itemType);
        }

        // **Handle Category & Tag Filtering Correctly**
        if (!empty($categoryIds)) {
            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('activity_categories.category_id', $categoryIds);
            });
        }

        if (!empty($categorySlugs) && empty($categoryIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 200);
        }

        // return $query->get();
        return $query->get()->map(function ($activity) {
            $categories = $activity->categories->map(function ($activityCategory) {
                return [
                    'id' => $activityCategory->category->id,
                    'name' => $activityCategory->category->name,
                ];
            })->unique()->values();
        
            return [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'item_type' => $activity->item_type,
                'featured'  => $activity->featured_activity,
                'categories' => $categories,
                'pricing' => $activity->pricing ? [
                    'regular_price' => $activity->pricing->regular_price,
                    'currency' => $activity->pricing->currency,
                ] : null,
                'group_discount' => $activity->groupDiscounts ? $activity->groupDiscounts->map(function ($discount) {
                    return [
                        'min_people' => $discount->min_people,
                        'discount_amount' => $discount->discount_amount,
                        'discount_type' => $discount->discount_type,
                    ];
                }) : [],
                'early_bird_discount' => $activity->earlyBirdDiscount ? [
                    'days_before_start' => $activity->earlyBirdDiscount->first()?->days_before_start,
                    'discount_amount' => $activity->earlyBirdDiscount->first()?->discount_amount,
                    'discount_type' => $activity->earlyBirdDiscount->first()?->discount_type,
                ] : null,
            ];
        });
    }


    // Itinerary Search Function

    // private function searchItineraries($cityIds, $startDate, $endDate, $quantity)
    private function searchItineraries($cityIds, $startDate, $endDate, $quantity, $categoryIds, $tagIds, $sortBy, $minPrice, $maxPrice, $featured, $itemType)
    {
        $query = Itinerary::with([
            'categories' => function ($q) {
                $q->with('category:id,name'); 
            },
            // 'tags:id,name',
            'locations',
            'basePricing.variations',
        ])->whereHas('locations', function ($q) use ($cityIds) {
            $q->whereIn('city_id', $cityIds);
        });
    
        if ($startDate && $endDate) {
            $query->whereHas('availability', function ($q) use ($startDate, $endDate) {
                $q->where('date_based_itinerary', true)
                    ->where('start_date', '<=', $startDate)
                    ->where('end_date', '>=', $endDate);
            });
        }
    
        if ($quantity) {
            $query->whereHas('availability', function ($q) use ($quantity) {
                $q->where(function ($q) use ($quantity) {
                    $q->where('quantity_based_itinerary', false)
                        ->orWhere(function ($q) use ($quantity) {
                            $q->where('quantity_based_itinerary', true)
                                ->where('max_quantity', '>=', $quantity);
                        });
                });
            });
        }
        

        // **Min Price & Max Price Filtering**
        if ($minPrice !== null || $maxPrice !== null) {
            $query->whereHas('basePricing.variations', function ($q) use ($minPrice, $maxPrice) {
                if ($minPrice !== null) {
                    $q->where('regular_price', '>=', $minPrice);
                }
                if ($maxPrice !== null) {
                    $q->where('regular_price', '<=', $maxPrice);
                }
            });
        }

        // **Featured filter**
        if ($featured !== null) {
            $query->where('featured_itinerary', (bool) $featured);
        }

        if ($itemType) {
            $query->where('item_type', $itemType);
        }

        // **Handle Category & Tag Filtering Correctly**
        if (!empty($categoryIds)) {
            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('itinerary_categories.category_id', $categoryIds);
            });
        }

        if (!empty($categorySlugs) && empty($categoryIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 200);
        }

        if (!empty($tagIds)) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
        }

        if (!empty($tagSlugs) && empty($tagIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found',
            ], 200);
        }

        $itineraries = $query->get();
    
        $itineraries->transform(function ($itinerary) {

            $categories = $itinerary->categories->map(function ($itineraryCategory) {
                return $itineraryCategory->category ? [
                    'id' => $itineraryCategory->category->id,
                    'name' => $itineraryCategory->category->name,
                ] : null;
            })->filter()->unique()->values(); // Remove null values
            
            return [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'item_type' => $itinerary->item_type,
                'featured'  => $itinerary->featured_itinerary,
                'categories' => $categories,
                'tags' => $itinerary->tags->map(fn ($tag) => [
                    'slug' => $tag->slug,
                    'name' => $tag->name,
                ])->toArray(),
                'base_pricing' => $itinerary->basePricing ? [
                    'currency' => $itinerary->basePricing->currency,
                    'availability' => $itinerary->basePricing->availability,
                    'start_date' => $itinerary->basePricing->start_date,
                    'end_date' => $itinerary->basePricing->end_date,
                    'variations' => $itinerary->basePricing->variations->map(function ($variation) {
                        return [
                            'id' => $variation->id,
                            'name' => $variation->name,
                            'regular_price' => $variation->regular_price,
                            'sale_price' => $variation->sale_price,
                            'max_guests' => $variation->max_guests,
                            'description' => $variation->description,
                        ];
                    })->toArray(),
                ] : null,
            ];
        });
    
        return $itineraries;
    }
    

    // Package Search function

    // private function searchPackages($cityIds, $startDate, $endDate, $quantity)
    private function searchPackages($cityIds, $startDate, $endDate, $quantity, $categoryIds, $tagIds, $sortBy, $minPrice, $maxPrice, $featured, $itemType)
    {
        $query = Package::with([
            'categories' => function ($q) {
                $q->with('category:id,name'); 
            },
            // 'tags:id,name',
            'locations',
            'basePricing.variations',
        ])->whereHas('locations', function ($q) use ($cityIds) {
            $q->whereIn('city_id', $cityIds);
        });

        if ($startDate && $endDate) {
            $query->whereHas('availability', function ($q) use ($startDate, $endDate) {
                $q->where('date_based_package', true)
                    ->where('start_date', '<=', $startDate)
                    ->where('end_date', '>=', $endDate);
            });
        }

        if ($quantity) {
            $query->whereHas('availability', function ($q) use ($quantity) {
                $q->where(function ($q) use ($quantity) {
                    $q->where('quantity_based_package', false)
                        ->orWhere(function ($q) use ($quantity) {
                            $q->where('quantity_based_package', true)
                                ->where('max_quantity', '>=', $quantity);
                        });
                });
            });
        }

        // **Min Price & Max Price Filtering**
        if ($minPrice !== null || $maxPrice !== null) {
            $query->whereHas('basePricing.variations', function ($q) use ($minPrice, $maxPrice) {
                if ($minPrice !== null) {
                    $q->where('regular_price', '>=', $minPrice);
                }
                if ($maxPrice !== null) {
                    $q->where('regular_price', '<=', $maxPrice);
                }
            });
        }

        // **Featured filter**
        if ($featured !== null) {
            $query->where('featured_package', (bool) $featured);
        }

        if ($itemType) {
            $query->where('item_type', $itemType);
        }

        // **Handle Category & Tag Filtering Correctly**
        if (!empty($categoryIds)) {
            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('package_categories.category_id', $categoryIds);
            });
        }

        if (!empty($categorySlugs) && empty($categoryIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 200);
        }

        if (!empty($tagIds)) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
        }

        if (!empty($tagSlugs) && empty($tagIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found',
            ], 200);
        }

        // return $query->get();
        $packages = $query->get();
    
        $packages->transform(function ($package) {

            $categories = $package->categories->map(function ($packageCategory) {
                return $packageCategory->category ? [
                    'id' => $packageCategory->category->id,
                    'name' => $packageCategory->category->name,
                ] : null;
            })->filter()->unique()->values(); // Remove null values

            return [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'item_type' => $package->item_type,
                'featured'  => $package->featured_package,
                'categories' => $categories,
                'tags' => $package->tags->map(fn ($tag) => [
                    'slug' => $tag->slug,
                    'name' => $tag->name,
                ])->toArray(),
                'base_pricing' => $package->basePricing ? [
                    'currency' => $package->basePricing->currency,
                    'availability' => $package->basePricing->availability,
                    'start_date' => $package->basePricing->start_date,
                    'end_date' => $package->basePricing->end_date,
                    'variations' => $package->basePricing->variations->map(function ($variation) {
                        return [
                            'id' => $variation->id,
                            'name' => $variation->name,
                            'regular_price' => $variation->regular_price,
                            'sale_price' => $variation->sale_price,
                            'max_guests' => $variation->max_guests,
                            'description' => $variation->description,
                        ];
                    })->toArray(),
                ] : null,
            ];
        });
    
        return $packages;
    }

}
