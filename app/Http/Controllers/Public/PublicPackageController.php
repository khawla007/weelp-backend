<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Package;
// use App\Models\City;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class PublicPackageController extends Controller
{
    
    //  -------------------Code to get Packages with location details-------------------
    public function index(): JsonResponse
    {
        $packages = Package::with([
            'locations.city',
            'schedules.activities',
            'schedules.transfers',
            'basePricing.variations',
            'basePricing.blackoutDates',
            'inclusionsExclusions',
            'mediaGallery.media',
            'seo',
            'categories.category', 
            'attributes',
            'tags'
        ])->get()->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'featured_package' => $package->featured_package,
                'description' => $package->description,
                'item_type' => $package->item_type,
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
                'attributes' => $package->attributes->map(function ($attribute) {
                    return [
                        // 'id' => $attribute->attribute->id,
                        'name' => $attribute->attribute->name,
                        'attribute_value' => $attribute->attribute_value,
                    ];
                }),
                // 'tags' => $package->tags->pluck('name')->toArray(),
                'tags' => $package->tags->map(function ($tag) {
                    return [
                        'id' => $tag->tag->id,
                        'name' => $tag->tag->name,
                    ];
                })->toArray(),
                // 'media_gallery' => $package->mediaGallery->pluck('url')->toArray(),
                'media_gallery' => $package->mediaGallery->map(function ($media) {
                    return [
                        'id' => $media->media->id,
                        'name' => $media->media->name,
                        'alt_text' => $media->media->alt_text,
                        'url' => $media->media->url,
                    ];
                })->toArray(),
            ];
        });

        // return response()->json([
        //     'data' => $packages
        // ]);

        if ($packages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Packages not found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $packages
        ]);
    }

    //  -------------------Code to get Packages featured based with location details-------------------
    public function getFeaturedPackages(): JsonResponse
    {
        $packages = Package::with([
            'locations.city',
            'schedules.activities',
            'schedules.transfers',
            'basePricing.variations',
            'basePricing.blackoutDates',
            'inclusionsExclusions',
            'mediaGallery.media',
            'seo',
            'categories.category', 
            'attributes',
            'tags'
        ])
        ->where('featured_package', true)
        ->get()
        ->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'featured_package' => $package->featured_package,
                'description' => $package->description,
                'item_type' => $package->item_type,
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
                'attributes' => $package->attributes->map(function ($attribute) {
                    return [
                        'name' => $attribute->attribute->name,
                        'attribute_value' => $attribute->attribute_value,
                    ];
                }),
                // 'tags' => $package->tags->pluck('name')->toArray(),
                'tags' => $package->tags->map(function ($tag) {
                    return [
                        'id' => $tag->tag->id,
                        'name' => $tag->tag->name,
                    ];
                })->toArray(),
                // 'media_gallery' => $package->mediaGallery->pluck('url')->toArray(),
                'media_gallery' => $package->mediaGallery->map(function ($media) {
                    return [
                        'id' => $media->media->id,
                        'name' => $media->media->name,
                        'alt_text' => $media->media->alt_text,
                        'url' => $media->media->url,
                    ];
                })->toArray(),
            ];
        });

        // return response()->json([
        //     'data' => $packages
        // ]);
        if ($packages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Packages not found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $packages
        ]);
    }

    // ---------------------------New Code to get Single Package with location details--------------------------
    public function show($slug): JsonResponse
    {
        $package = Package::with([
            'locations.city',
            'information',
            'schedules.activities.activity',
            'schedules.transfers.transfer',
            'basePricing.variations',
            'basePricing.blackoutDates',
            'inclusionsExclusions',
            'mediaGallery.media',
            'seo',
            'faqs',
            'categories.category', 
            'attributes.attribute',
            'tags'
        ])->where('slug', $slug)->first();

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found'
            ], 404);
        }

        $formattedPackage = [
            'id' => $package->id,
            'name' => $package->name,
            'slug' => $package->slug,
            'featured_package' => $package->featured_package,
            'description' => $package->description,
            'item_type' => $package->item_type,
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
            'schedules' => $package->schedules->map(function ($schedule) {
                return [
                    'day' => $schedule->day,
                    'activities' => $schedule->activities->map(function ($package) {
                        return [
                            'id' => $package->id,
                            'name' => $package->activity ? $package->activity->name : null,
                            'start_time' => $package->start_time,
                            'end_time' => $package->end_time,
                            'notes' => $package->notes,
                            'price' => $package->price,
                            'include_in_package' => $package->include_in_package,
                        ];
                    }),
                    'transfers' => $schedule->transfers->map(function ($transfer) {
                        return [
                            'id' => $transfer->id,
                            'name' => $transfer->transfer ? $transfer->transfer->name : null,
                            'start_time' => $transfer->start_time,
                            'end_time' => $transfer->end_time,
                            'pickup_location' => $transfer->pickup_location,
                            'dropoff_location' => $transfer->dropoff_location,
                            'pax' => $transfer->pax,
                            'price' => $transfer->price,
                            'include_in_package' => $transfer->include_in_package,
                        ];
                    }),
                ];
            }),
            'categories' => $package->categories->map(function ($category) {
                return [
                    'id' => $category->category->id,
                    'name' => $category->category->name,
                ];
            })->toArray(),
            'attributes' => $package->attributes->map(function ($attribute) {
                return [
                    // 'id' => $attribute->attribute->id,
                    'name' => $attribute->attribute->name,
                    'attribute_value' => $attribute->attribute_value,
                ];
            }),
            'tags' => $package->tags->map(function ($tag) {
                return [
                    'id' => $tag->tag->id,
                    'name' => $tag->tag->name,
                ];
            })->toArray(),
            'base_pricing' => $package->basePricing,
            'inclusions_exclusions' => $package->inclusionsExclusions,
            // 'media_gallery' => $package->mediaGallery,
            'media_gallery' => $package->mediaGallery->map(function ($media) {
                return [
                    'id' => $media->media->id,
                    'name' => $media->media->name,
                    'alt_text' => $media->media->alt_text,
                    'url' => $media->media->url,
                ];
            })->toArray(),
            'information' => $package->information,
            'faqs' => $package->faqs,
            'seo' => $package->seo,
        ];

        // return response()->json([
        //     'data' => $formattedPackage
        // ]);

        if (empty($formattedPackage)) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $formattedPackage
        ]);
    }

}
