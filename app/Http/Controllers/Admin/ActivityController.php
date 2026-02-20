<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityTag;
use App\Models\ActivityLocation;
use App\Models\ActivityAttribute;
use App\Models\ActivityPricing;
use App\Models\ActivitySeasonalPricing;
use App\Models\ActivityGroupDiscount;
use App\Models\ActivityEarlyBirdDiscount;
use App\Models\ActivityLastMinuteDiscount;
use App\Models\ActivityPromoCode;
use App\Models\ActivityMediaGallery;
use App\Models\ActivityAvailability;
use App\Models\ActivityAddon;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Attribute;
use App\Models\City;
use Illuminate\Support\Facades\DB;


class ActivityController extends Controller
{

    /**
     * Display a listing of the activities.
    */
    public function index(Request $request)
    {
        $perPage        = 3; 
        $page           = $request->get('page', 1); 
        
        $search         = $request->get('search'); // Search by activity name
        $categorySlug   = $request->get('category');
        $difficulty     = $request->get('difficulty_level');
        $duration       = $request->get('duration');
        $ageGroup       = $request->get('age_restriction');
        $season         = $request->get('season');
        $minPrice       = $request->get('min_price', 0);
        $maxPrice       = $request->get('max_price');
        $sortBy         = $request->get('sort_by', 'id_desc'); // Default: Newest First
        
        $category       = $categorySlug ? Category::where('slug', $categorySlug)->first() : null;
        $categoryId     = $category ? $category->id : null;
    
        $difficultyAttr = Attribute::where('slug', 'difficulty-level')->first();
        $durationAttr   = Attribute::where('slug', 'duration')->first();
        $ageGroupAttr   = Attribute::where('slug', 'age-restriction')->first(); // Adjust slug if different
        
        $query = Activity::query()
            ->select('activities.*')  // Select all fields from activities
            ->join('activity_pricing', 'activity_pricing.activity_id', '=', 'activities.id') // Join with activity_pricing table
            ->with(['categories.category', 'tags.tag', 'locations.city', 'pricing', 'attributes', 'mediaGallery.media', 'addons.addon']) // Eager load relationships

            ->when($search, fn($query) =>
                $query->where('activities.name', 'like', "%{$search}%")
            )
            
            ->when($categoryId, fn($query) => 
                $query->whereHas('categories', fn($q) => 
                    $q->where('category_id', $categoryId)
                )
            )
            ->when($difficulty && $difficultyAttr, fn($query) => 
                $query->whereHas('attributes', fn($q) => 
                    $q->where('attribute_id', $difficultyAttr->id)
                      ->where('attribute_value', $difficulty)
                )
            )
            ->when($duration && $durationAttr, fn($query) => 
                $query->whereHas('attributes', fn($q) => 
                    $q->where('attribute_id', $durationAttr->id)
                      ->where('attribute_value', $duration)
                )
            )
            ->when($ageGroup && $ageGroupAttr, fn($query) => 
                $query->whereHas('attributes', fn($q) => 
                    $q->where('attribute_id', $ageGroupAttr->id)
                      ->where('attribute_value', $ageGroup)
                )
            )
            ->when($season, fn($query) => 
                $query->whereHas('seasonalPricing', fn($q) => 
                    $q->where('season_name', $season)
                )
            )
            ->when($maxPrice !== null, fn($query) => 
                $query->whereHas('pricing', fn($q) => 
                    $q->whereBetween('regular_price', [$minPrice, $maxPrice])
                )
            );

            // Sorting based on the 'sort_by' parameter
            switch ($sortBy) {
                case 'price_asc':
                    $query->orderBy('activity_pricing.regular_price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('activity_pricing.regular_price', 'desc');
                    break;
                case 'name_asc':
                    $query->orderBy('activities.name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('activities.name', 'desc');
                    break;
                case 'id_asc':
                    $query->orderBy('activities.id', 'asc'); // Sort by ID ascending (oldest first)
                    break;
                case 'id_desc':
                    $query->orderBy('activities.id', 'desc'); // Sort by ID descending (newest first)
                    break;
                case 'featured':
                    $query->orderByRaw('activities.featured_activity DESC'); // Sort featured=true first
                    break;
                default:
                    $query->orderBy('activities.id', 'desc'); // Default to newest first (created_at_desc)
                    break;
            }
            $allItems = $query->get(); 
            // ->get();
        
        $paginatedItems = $allItems->forPage($page, $perPage);
        

        $transformed = $paginatedItems->map(function ($activity) {
            $data = $activity->toArray(); // keep all original fields
        
            // Replace transformed fields for addons
            $data['addons'] = collect($activity->addons)->map(function ($addon) {
                return [
                    'id'                      => $addon->id,
                    'addon_id'                => $addon->addon_id,
                    'addon_name'              => $addon->addon->name ?? null,
                    'addon_type'              => $addon->addon->type ?? null,
                    'addon_description'       => $addon->addon->description ?? null,
                    'addon_price'             => $addon->addon->price ?? null,
                    'addon_sale_price'        => $addon->addon->sale_price ?? null,
                    'addon_price_calculation' => $addon->addon->price_calculation ?? null,
                    'addon_active_status'     => $addon->addon->active_status ?? null,
                ];
            });

            // Replace transformed fields
            $data['locations'] = collect($activity->locations)->map(function ($location) {
                return [
                    'id'         => $location->id,
                    'city_id'    => $location->city_id,
                    'city_name'  => $location->city->name ?? null,
                ];
            });
        
            $data['media_gallery'] = collect($activity->mediaGallery)->map(function ($media) {
                return [
                    'id'         => $media->id,
                    'media_id'   => $media->media_id,
                    'name'       => $media->media->name ?? null,
                    'alt_text'   => $media->media->alt_text ?? null,
                    'url'        => $media->media->url ?? null,
                ];
            });
        
            $data['attributes'] = collect($activity->attributes)->map(function ($attribute) {
                return [
                    'id'              => $attribute->id,
                    'attribute_id'    => $attribute->attribute_id,
                    'attribute_name'  => $attribute->attribute->name ?? null,
                    'attribute_value' => $attribute->attribute_value,
                ];
            });
        
            $data['categories'] = collect($activity->categories)->map(function ($category) {
                return [
                    'id'            => $category->id,
                    'category_id'   => $category->category_id,
                    'category_name' => $category->category->name ?? null,
                ];
            });
            $data['tags'] = collect($activity->tags)->map(function ($tag) {
                return [
                    'id'       => $tag->id,
                    'tag_id'   => $tag->tag_id,
                    'tag_name' => $tag->tag->name ?? null,
                ];
            });
        
            return $data;
        });

        return response()->json([
            'success'      => true,
            'data'         => $transformed->values(),
            'current_page' => (int) $page,
            'per_page'     => $perPage,
            'total'        => $allItems->count(),
        ], 200);
    }    



    /**
     * Store a newly created activity in storage.
    */
    public function store(Request $request)
    {
        $request->validate([
            'name'                 => 'required|string|max:255',
            'slug'                 => 'required|string|unique:activities,slug',
            'description'          => 'nullable|string',
            'short_description'    => 'nullable|string',
            'featured_activity'    => 'boolean',
            'categories'           => 'nullable|array',
            'tags'                 => 'nullable|array',
            'locations'            => 'nullable|array',
            'attributes'           => 'nullable|array',
            'pricing'              => 'nullable|array',
            'seasonal_pricing'     => 'nullable|array',
            'group_discounts'      => 'nullable|array',
            'early_bird_discount'  => 'nullable|array',
            'last_minute_discount' => 'nullable|array',
            'promo_codes'          => 'nullable|array',
            'media_gallery'        => 'nullable|array',
            'addons'               => 'nullable|array',
            'availability'         => 'nullable|array',
        ]);
    
        try {
            DB::beginTransaction();
    
            $activity = Activity::create([
                'name'              => $request->name,
                'slug'              => $request->slug,
                'description'       => $request->description,
                'short_description' => $request->short_description,
                'featured_activity' => $request->featured_activity ?? false,
            ]);
    
            // Categories
            if ($request->has('categories')) {
                foreach ($request->categories as $category_id) {
                    ActivityCategory::create([
                        'activity_id' => $activity->id,
                        'category_id' => $category_id,
                    ]);
                }
            }


            // Activity Addons
            if ($request->has('addons')) {
                foreach ($request->addons as $addon_id) {
                    ActivityAddon::create([
                        'activity_id' => $activity->id,
                        'addon_id'    => $addon_id,
                    ]);
                }
            }

            // Tags
            if ($request->has('tags')) {
                foreach ($request->tags as $tag_id) {
                    ActivityTag::create([
                        'activity_id' => $activity->id,
                        'tag_id'      => $tag_id,
                    ]);
                }
            }
    
            // Locations
            if ($request->has('locations')) {
                foreach ($request->locations as $location) {
                    ActivityLocation::create([
                        'activity_id'    => $activity->id,
                        'city_id'        => $location['city_id'],
                        'location_type'  => $location['location_type'],
                        'location_label' => $location['location_label'],
                        'duration'       => $location['duration'] ?? null,
                    ]);
                }
            }
    
            // Attributes
            if ($request->has('attributes')) {
                foreach ($request->input('attributes') as $attribute) {
                    ActivityAttribute::create([
                        'activity_id'     => $activity->id,
                        'attribute_id'    => $attribute['attribute_id'],
                        'attribute_value' => $attribute['attribute_value'],
                    ]);
                }
            }
    
            // Pricing
            if ($request->has('pricing')) {
                ActivityPricing::create([
                    'activity_id'   => $activity->id,
                    'regular_price' => $request->pricing['regular_price'],
                    'currency'      => $request->pricing['currency'],
                ]);
    
                // Seasonal Pricing
                if ($request->has('seasonal_pricing')) {
                    foreach ($request->seasonal_pricing as $season) {
                        ActivitySeasonalPricing::create([
                            'activity_id'             => $activity->id,
                            'season_name'             => $season['season_name'],
                            'enable_seasonal_pricing' => true,
                            'season_start'            => $season['season_start'],
                            'season_end'              => $season['season_end'],
                            'season_price'            => $season['season_price'],
                        ]);
                    }
                }
            }
    
            // Group Discounts
            if ($request->has('group_discounts')) {
                // dd($request->group_discounts);
                foreach ($request->group_discounts as $discount) {
                    ActivityGroupDiscount::create([
                        'activity_id'     => $activity->id,
                        'min_people'      => $discount['min_people'],
                        'discount_amount' => $discount['discount_amount'],
                        'discount_type'   => $discount['discount_type'],
                    ]);
                }
            }
    
            // Early Bird Discount
            if ($request->has('early_bird_discount')) {
                ActivityEarlyBirdDiscount::create([
                    'activity_id'       => $activity->id,
                    'enabled'           => $request->last_minute_discount['enabled'],
                    'days_before_start' => $request->early_bird_discount['days_before_start'],
                    'discount_amount'   => $request->early_bird_discount['discount_amount'],
                    'discount_type'     => $request->early_bird_discount['discount_type'],
                ]);
            }
    
            // Last Minute Discount
            if ($request->has('last_minute_discount')) {
                ActivityLastMinuteDiscount::create([
                    'activity_id'       => $activity->id,
                    'enabled'           => $request->last_minute_discount['enabled'],
                    'days_before_start' => $request->last_minute_discount['days_before_start'],
                    'discount_amount'   => $request->last_minute_discount['discount_amount'],
                    'discount_type'     => $request->last_minute_discount['discount_type'],
                ]);
            }
    
            // Promo Codes
            if ($request->has('promo_codes')) {
                foreach ($request->promo_codes as $promo) {
                    ActivityPromoCode::create([
                        'activity_id'     => $activity->id,
                        'promo_code'      => $promo['promo_code'],
                        'max_uses'        => $promo['max_uses'],
                        'discount_amount' => $promo['discount_amount'],
                        'discount_type'   => $promo['discount_type'],
                        'valid_from'      => $promo['valid_from'],
                        'valid_to'        => $promo['valid_to'],
                    ]);
                }
            }

            // Media Gallery 
            if ($request->has('media_gallery')) {
                foreach ($request->media_gallery as $media) {
                    ActivityMediaGallery::create([
                        'activity_id' => $activity->id,
                        'media_id'    => $media['media_id'],
                    ]);
                }
            }
    
            // Availability
            if ($request->has('availability')) {
                ActivityAvailability::create([
                    'activity_id'             => $activity->id,
                    'date_based_activity'     => $request->availability['date_based_activity'],
                    'start_date'              => $request->availability['start_date'] ?? null,
                    'end_date'                => $request->availability['end_date'] ?? null,
                    'quantity_based_activity' => $request->availability['quantity_based_activity'],
                    'max_quantity'            => $request->availability['max_quantity'] ?? null,
                ]);
            }
    
            DB::commit();
    
            return response()->json(['message' => 'Activity created successfully', 'activity' => $activity], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Display the specified activity.
     */
    public function show(string $id)
    {
        $activity = Activity::with([
            'categories', 
            'locations.city', 
            'attributes.attribute',
            'tags.tag',
            'pricing', 'seasonalPricing', 
            'groupDiscounts', 'earlyBirdDiscount', 
            'lastMinuteDiscount', 'promoCodes', 
            'mediaGallery.media', 'availability', 'addons.addon'
        ])->find($id);
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        // Transform response
        $activityData = $activity->toArray();
    
        // Replace location city object with just `city_name`
        $activityData['locations'] = collect($activity->locations)->map(function ($location) {
            return [
                'id'             => $location->id,
                'activity_id'    => $location->activity_id,
                'location_type'  => $location->location_type,
                'city_id'        => $location->city_id,
                'city_name'      => $location->city->name ?? null, // Get city name
                'location_label' => $location->location_label,
                'duration'       => $location->duration,
                'created_at'     => $location->created_at,
                'updated_at'     => $location->updated_at,
            ];
        });
    
        // Replace attributes with just `attribute_name`
        $activityData['attributes'] = collect($activity->attributes)->map(function ($attribute) {
            return [
                'id'              => $attribute->id,
                'attribute_id'    => $attribute->attribute_id,
                'attribute_name'  => $attribute->attribute->name ?? null, // Get attribute name
                'attribute_value' => $attribute->attribute_value,
            ];
        });
    
        // Replace categories with just `category_name`
        $activityData['categories'] = collect($activity->categories)->map(function ($category) {
            return [
                'id'            => $category->id,
                'category_id'   => $category->category_id,
                'category_name' => $category->category->name ?? null, // Get category name
            ];
        });
        $activityData['tags'] = collect($activity->tags)->map(function ($tag) {
            return [
                'id'       => $tag->id,
                'tag_id'   => $tag->tag_id,
                'tag_name' => $tag->tag->name ?? null, // Get tag name
            ];
        });

        $activityData['addons'] = collect($activity->addons)->map(function ($addon) {
            return [
                'id'                      => $addon->id,
                'addon_id'                => $addon->addon_id,
                'addon_name'              => $addon->addon->name ?? null,
                'addon_type'              => $addon->addon->type ?? null,
                'addon_description'       => $addon->addon->description ?? null,
                'addon_price'             => $addon->addon->price ?? null,
                'addon_sale_price'        => $addon->addon->sale_price ?? null,
                'addon_price_calculation' => $addon->addon->price_calculation ?? null,
                'addon_active_status'     => $addon->addon->active_status ?? null,
            ];
        });

        $activityData['media_gallery'] = collect($activity->mediaGallery)->map(function ($media) {
            return [
                'id'       => $media->id,
                'media_id' => $media->media_id,
                'name'     => $media->media->name ?? null,
                'url'      => $media->media->url ?? null,
            ];
        });
    
        return response()->json($activityData, 200);
    }

    /**
     * Update the specified activity in storage.
     */
    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);
    
        $rules = [
            'name'                 => 'sometimes|required|string|max:255',
            'slug'                 => 'sometimes|required|string|unique:activities,slug,' . $activity->id,
            'description'          => 'nullable|string',
            'short_description'    => 'nullable|string',
            'featured_activity'    => 'boolean',
            'categories'           => 'nullable|array',
            'tags'                 => 'nullable|array',
            'locations'            => 'nullable|array',
            'attributes'           => 'nullable|array',
            'pricing'              => 'nullable|array',
            'seasonal_pricing'     => 'nullable|array',
            'group_discounts'      => 'nullable|array',
            'early_bird_discount'  => 'nullable|array',
            'last_minute_discount' => 'nullable|array',
            'promo_codes'          => 'nullable|array',
            'media_gallery'        => 'nullable|array',
            'addons'               => 'nullable|array',
            'availability'         => 'nullable|array',
        ];

        $request->validate($rules);
    
        try {
            DB::beginTransaction();
    
            $activity->fill($request->only([
                'name', 'slug', 'description', 'short_description', 'featured_activity'
            ]));
    
            if ($request->has('featured_images')) {
                $activity->featured_images = json_encode($request->featured_images);
            }
    
            $activity->save();
    
            $fullDeleteRelations = [
                'categories' => 'category_id',
                'tags'       => 'tag_id',
                'addons'     => 'addon_id',
            ];
            
            foreach ($fullDeleteRelations as $relation => $foreignKey) {
                if ($request->has($relation)) {
                    // Delete old relations first
                    $activity->$relation()->delete();
            
                    // Create new relations
                    foreach ($request->$relation as $id) {
                        $activity->$relation()->create([
                            $foreignKey => $id
                        ]);
                    }
                }
            }

            // Handle attributes (requires full object)
            if ($request->has('attributes')) {
                $activity->attributes()->delete();
            
                $attributes = collect($request->input('attributes'))->map(function ($attr) use ($activity) {
                    return array_merge($attr, ['activity_id' => $activity->id]);
                })->toArray();
            
                $activity->attributes()->createMany($attributes);
            }  
            
            // Handle Media (requires full object)
            if ($request->has('media_gallery')) {
                $activity->mediaGallery()->delete();
            
                $mediaGallery = collect($request->input('media_gallery'))->map(function ($item) use ($activity) {
                    return array_merge($item, ['activity_id' => $activity->id]);
                })->toArray();
            
                $activity->mediaGallery()->createMany($mediaGallery);
            }

            // // Handle Addons (requires full object)
            // if ($request->has('addons')) {
            //     $activity->activitiesAddon()->delete();
            
            //     $mediaGallery = collect($request->input('addons'))->map(function ($item) use ($activity) {
            //         return array_merge($item, ['activity_id' => $activity->id]);
            //     })->toArray();
            
            //     $activity->mediaGallery()->createMany($mediaGallery);
            // }

            $updateOrCreateRelation = function ($relationName, $data) use ($activity) {
                $relation = $activity->$relationName();
                $relatedModel = $relation->getRelated();
            
                foreach ($data as $item) {
                    if (!empty($item['id'])) {
                        $relatedModel->where('id', $item['id'])
                                     ->where('activity_id', $activity->id) // safety check
                                     ->update($item);
                    } else {
                        $relation->create($item);
                    }
                }
            };
    
            foreach (['locations'] as $relation) {
            // foreach (['locations', 'seasonalPricing', 'group_discounts', 'early_bird_discount', 'last_minute_discount', 'promo_codes', 'availability'] as $relation) {
                if ($request->has($relation)) {
                    $updateOrCreateRelation($relation, $request->$relation);
                }
            }
    
            $pricing = $activity->pricing()->first();
    
            if ($request->has('pricing')) {
                $pricing = $activity->pricing()->firstOrCreate([]);
                $pricing->fill($request->pricing)->save();
            }
    
            $updateOrCreateChild = function ($data, $modelClass, $foreignKey) use ($pricing, $activity) {
                foreach ($data as $item) {
                    if (!empty($item['id'])) {
                        $model = $modelClass::find($item['id']);
                        if ($model) {
                            $model->fill($item)->save();
                        }
                    } else {
                        $item[$foreignKey]   = $pricing->id;
                        $item['activity_id'] = $activity->id;
                        $modelClass::create($item);
                    }
                }
            };
    
            
            if ($request->has('seasonal_pricing')) {
                $updateOrCreateChild($request->seasonal_pricing, \App\Models\ActivitySeasonalPricing::class, 'base_pricing_id');
            }
            
            if ($request->has('group_discounts')) {
                $updateOrCreateChild($request->group_discounts, \App\Models\ActivityGroupDiscount::class, 'base_pricing_id');
            }

            // Early Bird Discount
            if ($request->has('early_bird_discount')) {
                $earlyBird = $activity->earlyBirdDiscount()->firstOrCreate([]);
                $earlyBird->fill($request->early_bird_discount)->save();
            }

            // Last Minute Discount
            if ($request->has('last_minute_discount')) {
                $lastMinute = $activity->lastMinuteDiscount()->firstOrCreate([]);
                $lastMinute->fill($request->last_minute_discount)->save();
            }
    
            if ($request->has('promo_codes')) {
                $updateOrCreateChild($request->promo_codes, \App\Models\ActivityPromoCode::class, 'base_pricing_id');
            }
    
            if ($request->has('availability')) {
                $availabilityData = $request->availability;
            
                // Only nullify if explicitly set to false
                if (array_key_exists('date_based_activity', $availabilityData) && $availabilityData['date_based_activity'] === false) {
                    $availabilityData['start_date'] = null;
                    $availabilityData['end_date'] = null;
                }
            
                if (array_key_exists('quantity_based_activity', $availabilityData) && $availabilityData['quantity_based_activity'] === false) {
                    $availabilityData['max_quantity'] = null;
                }
            
                $activity->availability()->updateOrCreate([], $availabilityData);
            }

            DB::commit();
    
            return response()->json([
                'message'  => 'Activity updated successfully',
                'activity' => $activity->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error'   => 'Something went wrong',
                'details' => $e->getMessage(),
            ], 500);
        }
    }    

    /**
     * Remove the specified activity from storage.
     */
    public function destroy(string $id)
    {
        $activity = Activity::find($id);
        
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
        
        $activity->delete();

        return response()->json(['message' => 'Activity deleted successfully']);
    }

    /**
     * Remove the specified activity specific fields from storage.
     */
    public function partialDelete(Request $request, string $id)
    {
        $activity = Activity::with('locations', 'seasonalPricing', 'groupDiscounts', 'promoCodes')->find($id);

        if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
        }

        // Delete selected locations directly
        if ($request->has('deleted_location_ids') && $activity->locations) {
            $activity->locations()
                ->whereIn('id', $request->deleted_location_ids)
                ->delete();
        }

        // Delete selected Seasonal Pricing directly
        if ($request->has('deleted_seasonal_pricing_ids') && $activity->seasonalPricing) {
            $activity->seasonalPricing()
                ->whereIn('id', $request->deleted_seasonal_pricing_ids)
                ->delete();
        }

        // Delete selected Group Discounts directly
        if ($request->has('deleted_group_discounts_ids') && $activity->groupDiscounts) {
            $activity->groupDiscounts()
                ->whereIn('id', $request->deleted_group_discounts_ids)
                ->delete();
        }

        // Delete selected Promo Codes directly
        if ($request->has('deleted_promo_codes_ids') && $activity->promoCodes) {
            $activity->promoCodes()
                ->whereIn('id', $request->deleted_promo_codes_ids)
                ->delete();
        }

        return response()->json(['message' => 'Selected items deleted successfully']);
    }

    /**
     * Remove the bulk activities from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'activity_ids' => 'required|array',
            'activity_ids.*' => 'integer|exists:activities,id',
        ]);

        DB::beginTransaction();
        try {
            // This will automatically cascade delete related rows if foreign keys are set correctly
            Activity::whereIn('id', $validated['activity_ids'])->delete();

            DB::commit();

            return response()->json([
                'message' => 'Selected activities deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to delete selected activities.',
                'message' => $e->getMessage()
            ], 500);
        }
    }


}
