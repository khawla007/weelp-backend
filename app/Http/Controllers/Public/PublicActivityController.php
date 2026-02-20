<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Tag;

class PublicActivityController extends Controller
{

    // ----------------------Code to get all activities with all location details----------------------
    public function getActivities()
    {
        $activities = Activity::with([
            'categories.category', 
            'attributes.attribute', 
            'locations.city',
            'pricing', 
            'seasonalPricing', 
            'groupDiscounts', 
            'earlyBirdDiscount', 
            'lastMinuteDiscount', 
            'promoCodes',
            'availability',
            'mediaGallery.media',
        ])->get()->map(function ($activity) {
            return [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'featured_activity' => $activity->featured_activity,
                'description' => $activity->description,
                'item_type' => $activity->item_type,
                'short_description' => $activity->short_description,
                'featured_images' => $activity->featured_images,
                // 'categories' => $activity->categories->pluck('category.name')->join(', '),
                'categories' => $activity->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                })->toArray(),
                'attributes' => $activity->attributes->map(function ($attribute) {
                    return [
                        'name' => $attribute->attribute->name,
                        'attribute_value' => $attribute->attribute_value,
                    ];
                }),

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
                'pricing' => $activity->pricing,  
                'seasonalPricing' => $activity->seasonalPricing,
                'groupDiscounts' => $activity->groupDiscounts,
                'earlyBirdDiscount' => $activity->earlyBirdDiscount,
                'lastMinuteDiscount' => $activity->lastMinuteDiscount,
                'promoCodes' => $activity->promoCodes,
                'availability' => $activity->availability ? [
                    'date_based_activity' => $activity->availability->date_based_activity,
                    'start_date' => $activity->availability->start_date,
                    'end_date' => $activity->availability->end_date,
                    'quantity_based_activity' => $activity->availability->quantity_based_activity,
                    'max_quantity' => $activity->availability->max_quantity,
                ] : null,

                'media_gallery' => $activity->mediaGallery->map(function ($media) {
                    return [
                        'id' => $media->media->id,
                        'name' => $media->media->name,
                        'alt_text' => $media->media->alt_text,
                        'url' => $media->media->url,
                    ];
                })->toArray(),
            ];
        });
        
        // return response()->json($activities);
        if ($activities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Activities not found'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    // ----------------------Code to get all activities featured based with all location details----------------------
    public function getFeaturedActivities()
    {
        $activities = Activity::with([
            'categories.category', 
            'attributes.attribute', 
            'locations.city',
            'pricing', 
            'seasonalPricing', 
            'groupDiscounts', 
            'earlyBirdDiscount', 
            'lastMinuteDiscount', 
            'promoCodes',
            'availability',
            'mediaGallery.media',
        ])
        ->where('featured_activity', true) 
        ->get()
        ->map(function ($activity) {
            return [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'featured_activity' => $activity->featured_activity,
                'description' => $activity->description,
                'item_type' => $activity->item_type,
                'short_description' => $activity->short_description,
                'featured_images' => $activity->featured_images,
                'categories' => $activity->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                })->toArray(),
                'attributes' => $activity->attributes->map(function ($attribute) {
                    return [
                        'name' => $attribute->attribute->name,
                        'attribute_value' => $attribute->attribute_value,
                    ];
                }),
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
                'pricing' => $activity->pricing,  
                'seasonalPricing' => $activity->seasonalPricing,
                'groupDiscounts' => $activity->groupDiscounts,
                'earlyBirdDiscount' => $activity->earlyBirdDiscount,
                'lastMinuteDiscount' => $activity->lastMinuteDiscount,
                'promoCodes' => $activity->promoCodes,
                'availability' => $activity->availability ? [
                    'date_based_activity' => $activity->availability->date_based_activity,
                    'start_date' => $activity->availability->start_date,
                    'end_date' => $activity->availability->end_date,
                    'quantity_based_activity' => $activity->availability->quantity_based_activity,
                    'max_quantity' => $activity->availability->max_quantity,
                ] : null,

                'media_gallery' => $activity->mediaGallery->map(function ($media) {
                    return [
                        'id' => $media->media->id,
                        'name' => $media->media->name,
                        'alt_text' => $media->media->alt_text,
                        'url' => $media->media->url,
                    ];
                })->toArray(),
            ];
        });

        // return response()->json($activities);
        if ($activities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Activities not found'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    // ----------------------Code to get Single activities with all required data----------------------
    public function getActivityBySlug($activityslug)
    {
        $activity = Activity::with([
            'categories.category', 
            'attributes.attribute', 
            'locations.city',
            'pricing', 
            'seasonalPricing', 
            'groupDiscounts', 
            'earlyBirdDiscount', 
            'lastMinuteDiscount', 
            'promoCodes',
            'mediaGallery.media',
        ])->where('slug', $activityslug)->first(); 
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        $formattedActivity = [
            'id' => $activity->id,
            'name' => $activity->name,
            'slug' => $activity->slug,
            'featured_activity' => $activity->featured_activity,
            'description' => $activity->description,
            'item_type' => $activity->item_type,
            'short_description' => $activity->short_description,
            'featured_images' => $activity->featured_images,
            // 'categories' => $activity->categories->pluck('category.name')->join(', '),
            'categories' => $activity->categories->map(function ($category) {
                return [
                    'id' => $category->category->id,
                    'name' => $category->category->name,
                ];
            })->toArray(),
            'attributes' => $activity->attributes->map(function ($attribute) {
                return [
                    'name' => $attribute->attribute->name,
                    'attribute_value' => $attribute->attribute_value,
                ];
            }),

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

            'pricing' => $activity->pricing,  
            'seasonalPricing' => $activity->seasonalPricing,
            'groupDiscounts' => $activity->groupDiscounts,
            'earlyBirdDiscount' => $activity->earlyBirdDiscount,
            'lastMinuteDiscount' => $activity->lastMinuteDiscount,
            'promoCodes' => $activity->promoCodes,

            'media_gallery' => $activity->mediaGallery->map(function ($media) {
                return [
                    'id' => $media->media->id,
                    'name' => $media->media->name,
                    'alt_text' => $media->media->alt_text,
                    'url' => $media->media->url,
                ];
            })->toArray(),
        ];
    
        // return response()->json($formattedActivity);
        if (empty($formattedActivity)) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $formattedActivity
        ]);
    }



    
}
