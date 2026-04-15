<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Models\Activity;
use App\Models\Transfer;
use App\Models\Place;
use App\Models\City;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class PublicItineraryController extends Controller
{

    //  -------------------Code to grt itineraries with location details-------------------
    public function index(): JsonResponse
    {
        $itineraries = Itinerary::with([
            'locations.city',
            'schedules.activities:id,schedule_id,price',
            'schedules.transfers:id,schedule_id,price',
            'basePricing.variations',
            'basePricing.blackoutDates',
            'inclusionsExclusions',
            'mediaGallery.media',
            'seo',
            'categories.category',
            'attributes',
            'tags'
        ])->original()->get()->map(function ($itinerary) {
            return [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'featured_itinerary' => $itinerary->featured_itinerary,
                'description' => $itinerary->description,
                'schedule_total_price' => $itinerary->schedule_total_price,
                'item_type' => $itinerary->item_type,
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
                'message' => 'Itineraries not found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $itineraries
        ]);
    }

    //  -------------------Code to grt itineraries featured based with location details-------------------
    public function getFeaturedItineraries(): JsonResponse
    {
        $citySlug = request()->query('city');

        $query = Itinerary::with([
            'locations.city',
            'schedules.activities:id,schedule_id,price',
            'schedules.transfers:id,schedule_id,price',
            'basePricing.variations',
            'basePricing.blackoutDates',
            'inclusionsExclusions',
            'mediaGallery.media',
            'seo',
            'categories.category',
            'attributes',
            'tags'
        ])
        ->original()
        ->where('featured_itinerary', true);

        if ($citySlug) {
            $city = City::where('slug', $citySlug)->first();
            if (!$city) {
                return response()->json(['success' => false, 'message' => 'City not found'], 404);
            }
            $query->whereHas('locations', fn($q) => $q->where('city_id', $city->id));
        }

        $itineraries = $query->get()
        ->map(function ($itinerary) {
            return [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'featured_itinerary' => $itinerary->featured_itinerary,
                'description' => $itinerary->description,
                'schedule_total_price' => $itinerary->schedule_total_price,
                'item_type' => $itinerary->item_type,
                'featured_image' => $itinerary->mediaGallery->where('is_featured', true)->first()?->media?->url
                    ?? $itinerary->mediaGallery->first()?->media?->url,
                'city_slug' => $itinerary->locations->first()?->city?->slug,
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
                'message' => 'Itineraries not found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $itineraries
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
            'schedules.transfers.transfer.mediaGallery.media',
            'basePricing.variations',
            'basePricing.blackoutDates',
            'inclusionsExclusions',
            'mediaGallery.media',
            'seo',
            'categories.category',
            'attributes.attribute',
            'tags'
        ])->where('slug', $slug)->first();

        if (!$itinerary) {
            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found'
            ], 404);
        }

        $formattedItinerary = [
            'id' => $itinerary->id,
            'name' => $itinerary->name,
            'slug' => $itinerary->slug,
            'featured_itinerary' => $itinerary->featured_itinerary,
            'description' => $itinerary->description,
            'schedule_total_price' => $itinerary->schedule_total_price,
            'item_type' => $itinerary->item_type,
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
            'schedules' => $itinerary->schedules->map(function ($schedule) {
                return [
                    'day' => $schedule->day,
                    'title' => $schedule->title,
                    'activities' => $schedule->activities->map(function ($activity) {
                        $activityModel = $activity->activity;
                        $primaryLocation = $activityModel?->locations->where('location_type', 'primary')->first();
                        $featuredMedia = $activityModel?->mediaGallery->where('is_featured', true)->first();

                        return [
                            'id' => $activity->id,
                            'activity_id' => $activity->activity_id,
                            'name' => $activityModel?->name,
                            'start_time' => $activity->start_time,
                            'end_time' => $activity->end_time,
                            'notes' => $activity->notes,
                            'price' => $activity->price,
                            'included' => $activity->included,
                            'include_in_package' => $activity->include_in_package,
                            'main_location' => $primaryLocation?->city?->name,
                            'duration_minutes' => $primaryLocation?->duration,
                            'featured_image' => $featuredMedia?->media?->url
                                ?? $activityModel?->mediaGallery->first()?->media?->url,
                        ];
                    }),
                    'transfers' => $schedule->transfers->map(function ($transfer) {
                        return [
                            'id' => $transfer->id,
                            'transfer_id' => $transfer->transfer_id,
                            'name' => $transfer->transfer ? $transfer->transfer->name : null,
                            'start_time' => $transfer->start_time,
                            'end_time' => $transfer->end_time,
                            'pickup_location' => $transfer->pickup_location,
                            'dropoff_location' => $transfer->dropoff_location,
                            'pax' => $transfer->pax,
                            'price' => $transfer->price,
                            'included' => $transfer->included,
                            'include_in_package' => $transfer->include_in_package,
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
            'media_gallery' => $this->resolveGalleryWithFallback($itinerary),
            'seo' => $itinerary->seo,
        ];

        // return response()->json([
        //     'data' => $formattedItinerary
        // ]);
        if (empty($formattedItinerary)) {
            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $formattedItinerary
        ]);
    }

    /**
     * Get addons for a specific itinerary
     * Used on: Single Itinerary Page - sidebar addon selection
     */
    public function getAddons($slug): JsonResponse
    {
        $itinerary = Itinerary::where('slug', $slug)->first();

        if (!$itinerary) {
            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found'
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
            'data' => $addons
        ]);
    }

    /**
     * Resolve the media_gallery for the public single-itinerary page.
     * Fallback chain: itinerary media → activity media → transfer media.
     * Dedupes by URL preserving insertion order.
     */
    private function resolveGalleryWithFallback(Itinerary $itinerary): array
    {
        $ownGallery = $itinerary->mediaGallery
            ->filter(fn ($mg) => $mg->media?->url)
            ->map(fn ($mg) => [
                'id' => $mg->media->id,
                'name' => $mg->media->name,
                'alt_text' => $mg->media->alt_text,
                'url' => $mg->media->url,
                'is_featured' => (bool) $mg->is_featured,
            ])
            ->values()
            ->toArray();

        if (!empty($ownGallery)) {
            return $ownGallery;
        }

        $collectFrom = function ($items) {
            $seen = [];
            $out = [];
            foreach ($items as $mg) {
                $media = $mg->media ?? null;
                if (!$media?->url || in_array($media->url, $seen, true)) {
                    continue;
                }
                $seen[] = $media->url;
                $out[] = [
                    'id' => $media->id,
                    'name' => $media->name,
                    'alt_text' => $media->alt_text,
                    'url' => $media->url,
                    'is_featured' => false,
                ];
            }
            return $out;
        };

        $activityMedia = $itinerary->schedules->flatMap(
            fn ($schedule) => $schedule->activities->flatMap(
                fn ($activity) => $activity->activity?->mediaGallery ?? collect()
            )
        );
        $activityGallery = $collectFrom($activityMedia);
        if (!empty($activityGallery)) {
            return $activityGallery;
        }

        $transferMedia = $itinerary->schedules->flatMap(
            fn ($schedule) => $schedule->transfers->flatMap(
                fn ($transfer) => $transfer->transfer?->mediaGallery ?? collect()
            )
        );
        return $collectFrom($transferMedia);
    }

}
