<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Itinerary;
use Illuminate\Http\JsonResponse;

class PublicItineraryController extends Controller
{
    //  -------------------Code to grt itineraries with location details-------------------
    public function index(): JsonResponse
    {
        $itineraries = Itinerary::with([
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
            'tags',
        ])->get()->map(function ($itinerary) {
            return [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'featured_itinerary' => $itinerary->featured_itinerary,
                'description' => $itinerary->description,
                'item_type' => $itinerary->item_type,
                'featured_image' => $itinerary->mediaGallery->where('is_featured', true)->first()?->media->url
                    ?? $itinerary->mediaGallery->first()?->media->url,
                'locations' => $itinerary->locations->map(function ($location) {
                    $city = $location->city;

                    return [
                        'city_id' => $city->id,
                        'city' => $city->name,
                        'state_id' => $city->state->id,
                        'state' => $city->state->name,
                        'country_id' => $city->state->country->id,
                        'country' => $city->state->country->name,
                        'region_id' => $city->state->country->regions->isNotEmpty()
                            ? $city->state->country->regions->first()->id
                            : null,
                        'region' => $city->state->country->regions->isNotEmpty()
                            ? $city->state->country->regions->first()->name
                            : null,

                    ];
                }),
                // 'categories' => $itinerary->categories->pluck('name')->toArray(),
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
                // 'tags' => $itinerary->tags->pluck('name')->toArray(),
                'tags' => $itinerary->tags->map(function ($tag) {
                    return [
                        'id' => $tag->tag->id,
                        'name' => $tag->tag->name,
                    ];
                })->toArray(),
                // 'media_gallery' => $itinerary->mediaGallery->pluck('url')->toArray(),
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
            ];
        });

        if ($itineraries->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Itineraries not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $itineraries,
        ]);
    }

    //  -------------------Code to grt itineraries featured based with location details-------------------
    public function getFeaturedItineraries(): JsonResponse
    {
        $citySlug = request()->query('city');

        $query = Itinerary::with([
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
            'tags',
        ])
            ->where('featured_itinerary', true);

        if ($citySlug) {
            $city = City::where('slug', $citySlug)->first();
            if (! $city) {
                return response()->json(['success' => false, 'message' => 'City not found'], 404);
            }
            $query->whereHas('locations', fn ($q) => $q->where('city_id', $city->id));
        }

        $itineraries = $query->get()
            ->map(function ($itinerary) {
                return [
                    'id' => $itinerary->id,
                    'name' => $itinerary->name,
                    'slug' => $itinerary->slug,
                    'featured_itinerary' => $itinerary->featured_itinerary,
                    'description' => $itinerary->description,
                    'item_type' => $itinerary->item_type,
                    'featured_image' => $itinerary->mediaGallery->where('is_featured', true)->first()?->media->url
                        ?? $itinerary->mediaGallery->first()?->media->url,
                    'city_slug' => $itinerary->locations->first()?->city?->slug,
                    'locations' => $itinerary->locations->map(function ($location) {
                        $city = $location->city;

                        return [
                            'city_id' => $city->id,
                            'city' => $city->name,
                            'state_id' => $city->state->id,
                            'state' => $city->state->name,
                            'country_id' => $city->state->country->id,
                            'country' => $city->state->country->name,
                            'region_id' => $city->state->country->regions->isNotEmpty()
                                ? $city->state->country->regions->first()->id
                                : null,
                            'region' => $city->state->country->regions->isNotEmpty()
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
                    // 'tags' => $itinerary->tags->pluck('name')->toArray(),
                    'tags' => $itinerary->tags->map(function ($tag) {
                        return [
                            'id' => $tag->tag->id,
                            'name' => $tag->tag->name,
                        ];
                    })->toArray(),
                    // 'media_gallery' => $itinerary->mediaGallery->pluck('url')->toArray(),
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
                ];
            });

        if ($itineraries->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Itineraries not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $itineraries,
        ]);
    }

    // ---------------------------New Code to get Single itinerary with location details--------------------------
    public function show($slug): JsonResponse
    {
        $itinerary = Itinerary::with([
            'locations.city',
            'schedules.activities.activity.locations.city',
            'schedules.activities.activity.mediaGallery.media',
            'schedules.transfers.transfer',
            'basePricing.variations',
            'basePricing.blackoutDates',
            'inclusionsExclusions',
            'mediaGallery.media',
            'seo',
            'categories.category',
            'attributes.attribute',
            'tags',
        ])->where('slug', $slug)->first();

        if (! $itinerary) {
            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found',
            ], 404);
        }

        $formattedItinerary = [
            'id' => $itinerary->id,
            'name' => $itinerary->name,
            'slug' => $itinerary->slug,
            'featured_itinerary' => $itinerary->featured_itinerary,
            'description' => $itinerary->description,
            'item_type' => $itinerary->item_type,
            'featured_image' => $itinerary->mediaGallery->where('is_featured', true)->first()?->media->url
                ?? $itinerary->mediaGallery->first()?->media->url,
            'locations' => $itinerary->locations->map(function ($location) {
                $city = $location->city;

                return [
                    'city_id' => $city->id,
                    'city' => $city->name,
                    'state_id' => $city->state->id,
                    'state' => $city->state->name,
                    'country_id' => $city->state->country->id,
                    'country' => $city->state->country->name,
                    'region_id' => $city->state->country->regions->isNotEmpty()
                        ? $city->state->country->regions->first()->id
                        : null,
                    'region' => $city->state->country->regions->isNotEmpty()
                        ? $city->state->country->regions->first()->name
                        : null,

                ];
            }),
            'schedules' => $itinerary->schedules->map(function ($schedule) {
                return [
                    'day' => $schedule->day,
                    'activities' => $schedule->activities->map(function ($activity) {
                        $activityModel = $activity->activity;
                        $primaryLocation = $activityModel->locations->where('location_type', 'primary')->first();
                        $featuredMedia = $activityModel->mediaGallery->where('is_featured', true)->first();

                        return [
                            'id' => $activity->id,
                            'name' => $activityModel->name,
                            'start_time' => $activity->start_time,
                            'end_time' => $activity->end_time,
                            'notes' => $activity->notes,
                            'price' => $activity->price,
                            'include_in_package' => $activity->included,
                            'main_location' => $primaryLocation?->city?->name,
                            'duration_minutes' => $primaryLocation?->duration,
                            'featured_image' => $featuredMedia?->media->url
                                ?? $activityModel->mediaGallery->first()?->media->url,
                        ];
                    }),
                    'transfers' => $schedule->transfers->map(function ($transfer) {
                        return [
                            'id' => $transfer->id,
                            'name' => $transfer->transfer->name,
                            'start_time' => $transfer->start_time,
                            'end_time' => $transfer->end_time,
                            'pickup_location' => $transfer->pickup_location,
                            'dropoff_location' => $transfer->dropoff_location,
                            'pax' => $transfer->pax,
                            'price' => $transfer->price,
                            'include_in_package' => $transfer->included,
                        ];
                    }),
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
            'tags' => $itinerary->tags->map(function ($tag) {
                return [
                    'id' => $tag->tag->id,
                    'name' => $tag->tag->name,
                ];
            })->toArray(),
            'base_pricing' => $itinerary->basePricing,
            'inclusions_exclusions' => $itinerary->inclusionsExclusions,
            // 'media_gallery' => $itinerary->mediaGallery,
            'media_gallery' => $itinerary->mediaGallery->map(function ($media) {
                return [
                    'id' => $media->media->id,
                    'name' => $media->media->name,
                    'alt_text' => $media->media->alt_text,
                    'url' => $media->media->url,
                    'is_featured' => (bool) $media->is_featured,
                ];
            })->toArray(),
            'seo' => $itinerary->seo,
        ];

        // return response()->json([
        //     'data' => $formattedItinerary
        // ]);
        if (empty($formattedItinerary)) {
            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $formattedItinerary,
        ]);
    }

    /**
     * Get addons for a specific itinerary
     * Used on: Single Itinerary Page - sidebar addon selection
     */
    public function getAddons($slug): JsonResponse
    {
        $itinerary = Itinerary::where('slug', $slug)->first();

        if (! $itinerary) {
            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found',
            ], 404);
        }

        // Get addons linked to this itinerary via itinerary_addons pivot table
        $addons = \App\Models\Addon::where('active_status', true)
            ->whereHas('itinerariesAddon', function ($query) use ($itinerary) {
                $query->where('itinerary_id', $itinerary->id);
            })
            ->get()
            ->map(function ($addon) {
                return [
                    'addon_id' => $addon->id,
                    'addon_name' => $addon->name,
                    'addon_description' => $addon->description,
                    'addon_price' => $addon->price,
                    'addon_sale_price' => $addon->sale_price,
                    'addon_type' => $addon->type,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $addons,
        ]);
    }
}
