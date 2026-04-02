<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\City;
use App\Models\Package;
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
            'tags',
        ])->get()->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'featured_package' => $package->featured_package,
                'description' => $package->description,
                'item_type' => $package->item_type,
                'featured_image' => $package->mediaGallery->where('is_featured', true)->first()?->media->url
                    ?? $package->mediaGallery->first()?->media->url,
                'locations' => $package->locations->map(function ($location) {
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
                        'is_featured' => (bool) $media->is_featured,
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
                'message' => 'Packages not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }

    //  -------------------Code to get Packages featured based with location details-------------------
    public function getFeaturedPackages(): JsonResponse
    {
        $citySlug = request()->query('city');

        $query = Package::with([
            'locations.city',
            'basePricing.variations',
            'mediaGallery.media',
            'categories.category',
            'attributes',
            'tags.tag',
        ])
            ->where('featured_package', true);

        if ($citySlug) {
            $city = City::where('slug', $citySlug)->first();
            if (! $city) {
                return response()->json(['success' => false, 'message' => 'City not found'], 404);
            }
            $query->whereHas('locations', fn ($q) => $q->where('city_id', $city->id));
        }

        // Tag filter
        $tagNames = request()->has('tags') ? array_filter(explode(',', request()->get('tags'))) : [];
        if (! empty($tagNames)) {
            $query->whereHas('tags.tag', fn ($q) => $q->whereIn('name', $tagNames));
        }

        $packages = $query->get();

        // Get all tags from result set (for filter UI)
        $allTags = $packages->pluck('tags')->flatten()
            ->filter(fn ($pt) => $pt->tag !== null)
            ->map(fn ($pt) => ['id' => $pt->tag->id, 'name' => $pt->tag->name])
            ->unique('id')
            ->values();

        $formattedPackages = $packages->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'description' => $package->description,
                'item_type' => $package->item_type,
                'featured_package' => $package->featured_package,
                'city_slug' => $package->locations->first()?->city?->slug,
                'featured_image' => $package->mediaGallery->where('is_featured', true)->first()?->media->url
                    ?? $package->mediaGallery->first()?->media->url,
                'locations' => $package->locations->map(function ($location) {
                    $city = $location->city;

                    return [
                        'city_id' => $city->id,
                        'city' => $city->name,
                        'city_slug' => $city->slug,
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
                'attributes' => $package->attributes->map(function ($attribute) {
                    return [
                        'name' => $attribute->attribute->name,
                        'attribute_value' => $attribute->attribute_value,
                    ];
                }),
                'media_gallery' => $package->mediaGallery->map(function ($media) {
                    return [
                        'url' => $media->media->url,
                    ];
                })->toArray(),
                'base_pricing' => $package->basePricing,
            ];
        });

        // Sorting
        $sortBy = request()->get('sort_by', 'id_desc');
        $formattedPackages = match ($sortBy) {
            'name_asc' => $formattedPackages->sortBy('name'),
            'name_desc' => $formattedPackages->sortByDesc('name'),
            'price_asc' => $formattedPackages->sortBy(fn ($item) => $item['base_pricing']?->variations?->first()->regular_price ?? 0),
            'price_desc' => $formattedPackages->sortByDesc(fn ($item) => $item['base_pricing']?->variations?->first()->regular_price ?? 0),
            default => $formattedPackages->sortByDesc('id'),
        };

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
        ]);
    }

    // ---------------------------New Code to get Single Package with location details--------------------------
    public function show($slug): JsonResponse
    {
        $package = Package::with([
            'locations.city',
            'information',
            'schedules.activities.activity.locations.city',
            'schedules.activities.activity.mediaGallery.media',
            'schedules.transfers.transfer',
            'basePricing.variations',
            'basePricing.blackoutDates',
            'inclusionsExclusions',
            'mediaGallery.media',
            'seo',
            'faqs',
            'categories.category',
            'attributes.attribute',
            'tags',
        ])->where('slug', $slug)->first();

        if (! $package) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found',
            ], 404);
        }

        $formattedPackage = [
            'id' => $package->id,
            'name' => $package->name,
            'slug' => $package->slug,
            'featured_package' => $package->featured_package,
            'description' => $package->description,
            'item_type' => $package->item_type,
            'featured_image' => $package->mediaGallery->where('is_featured', true)->first()?->media->url
                ?? $package->mediaGallery->first()?->media->url,
            'locations' => $package->locations->map(function ($location) {
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
            'schedules' => $package->schedules->map(function ($schedule) {
                return [
                    'day' => $schedule->day,
                    'activities' => $schedule->activities->map(function ($scheduleActivity) {
                        $activityModel = $scheduleActivity->activity;
                        $primaryLocation = $activityModel->locations->where('location_type', 'primary')->first();
                        $featuredMedia = $activityModel->mediaGallery->where('is_featured', true)->first();

                        return [
                            'id' => $scheduleActivity->id,
                            'name' => $activityModel->name,
                            'start_time' => $scheduleActivity->start_time,
                            'end_time' => $scheduleActivity->end_time,
                            'notes' => $scheduleActivity->notes,
                            'price' => $scheduleActivity->price,
                            'include_in_package' => $scheduleActivity->included,
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
                    'is_featured' => (bool) $media->is_featured,
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
                'message' => 'Package not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $formattedPackage,
        ]);
    }

    /**
     * Get addons for a specific package
     * Used on: Single Package Page - sidebar addon selection
     */
    public function getAddons($slug): JsonResponse
    {
        $package = Package::where('slug', $slug)->first();

        if (! $package) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found',
            ], 404);
        }

        // Get addons linked to this package via package_addons pivot table
        $addons = \App\Models\Addon::where('active_status', true)
            ->whereHas('packagesAddon', function ($query) use ($package) {
                $query->where('package_id', $package->id);
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
