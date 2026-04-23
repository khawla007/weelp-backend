<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\City;
use App\Services\ActivityDiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicActivityController extends Controller
{
    // ----------------------Code to get all activities with all location details----------------------
    public function getActivities()
    {
        $activities = Activity::with([
            'categories.category',
            'attributes.attribute',
            'locations.city.state.country.regions',
            'pricing',
            'seasonalPricing',
            'groupDiscounts',
            'earlyBirdDiscount',
            'lastMinuteDiscount',
            'promoCodes',
            'availability',
            'mediaGallery.media',
        ])->get()->map(function (Activity $activity, int $key) {
            return [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'featured_activity' => $activity->featured_activity,
                'description' => $activity->description,
                'item_type' => $activity->item_type,
                'short_description' => $activity->short_description,
                'featured_image' => $activity->mediaGallery->where('is_featured', true)->first()?->media->url
                    ?? $activity->mediaGallery->first()?->media->url,
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
                        'is_featured' => (bool) $media->is_featured,
                    ];
                })->toArray(),
            ];
        });

        // return response()->json($activities);
        if ($activities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Activities not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    // ----------------------Code to get all activities featured based with all location details----------------------
    public function getFeaturedActivities()
    {
        $citySlug = request()->query('city');

        $query = Activity::with([
            'categories.category',
            'attributes.attribute',
            'locations.city.state.country.regions',
            'pricing',
            'seasonalPricing',
            'groupDiscounts',
            'earlyBirdDiscount',
            'lastMinuteDiscount',
            'promoCodes',
            'availability',
            'mediaGallery.media',
        ])
            ->where('featured_activity', true);

        if ($citySlug) {
            $city = City::where('slug', $citySlug)->first();
            if (! $city) {
                return response()->json(['success' => false, 'message' => 'City not found'], 404);
            }
            $query->whereHas('locations', fn ($q) => $q->where('city_id', $city->id));
        }

        $activities = $query->get()
            ->map(function (Activity $activity, int $key) {
                return [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'slug' => $activity->slug,
                    'featured_activity' => $activity->featured_activity,
                    'description' => $activity->description,
                    'item_type' => $activity->item_type,
                    'short_description' => $activity->short_description,
                    'featured_image' => $activity->mediaGallery->where('is_featured', true)->first()?->media->url
                        ?? $activity->mediaGallery->first()?->media->url,
                    'city_slug' => $activity->locations->first()?->city?->slug,
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
                            'city_slug' => $city->slug,
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
                            'is_featured' => (bool) $media->is_featured,
                        ];
                    })->toArray(),
                ];
            });

        // return response()->json($activities);
        if ($activities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Activities not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    // ----------------------Code to get Single activities with all required data----------------------
    public function getActivityBySlug($activityslug)
    {
        $activity = Activity::with([
            'categories.category',
            'attributes.attribute',
            'locations.city.state.country.regions',
            'pricing',
            'seasonalPricing',
            'groupDiscounts',
            'earlyBirdDiscount',
            'lastMinuteDiscount',
            'promoCodes',
            'mediaGallery.media',
            'addons.addon',
        ])->where('slug', $activityslug)->first();

        if (! $activity) {
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
            'featured_image' => $activity->mediaGallery->where('is_featured', true)->first()?->media->url
                ?? $activity->mediaGallery->first()?->media->url,
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
                    'city_slug' => $city->slug,
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
                    'is_featured' => (bool) $media->is_featured,
                ];
            })->toArray(),

            'addons' => $activity->addons
                ->filter(fn ($a) => $a->addon->active_status)
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'addon_id' => $a->addon_id,
                    'addon_name' => $a->addon->name,
                    'addon_type' => $a->addon->type,
                    'addon_description' => $a->addon->description,
                    'addon_price' => $a->addon->price,
                    'addon_sale_price' => $a->addon->sale_price,
                    'addon_price_calculation' => $a->addon->price_calculation,
                ])->values()->toArray(),

            'review_summary' => [
                'average_rating' => round(
                    $activity->reviews()->where('status', 'approved')->avg('rating') ?? 0, 1
                ),
                'total_reviews' => $activity->reviews()->where('status', 'approved')->count(),
                'total_photos' => \App\Models\ReviewMediaGallery::whereIn(
                    'review_id',
                    fn ($q) => $q->select('id')->from('reviews')
                        ->where('item_type', 'activity')
                        ->where('item_id', $activity->id)
                        ->where('status', 'approved')
                )->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $formattedActivity,
        ]);
    }

    public function quote(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'adults' => 'required|integer|min:0|max:999',
            'children' => 'nullable|integer|min:0|max:999',
            'start_date' => 'nullable|date|after_or_equal:today',
        ]);

        $adults = (int) $validated['adults'];
        $children = (int) ($validated['children'] ?? 0);
        $headcount = $adults + $children;

        if ($headcount === 0) {
            return response()->json([
                'error' => 'invalid_headcount',
                'message' => 'Total headcount must be at least 1.',
            ], 422);
        }

        $activity = Activity::where('slug', $slug)
            ->with(['pricing', 'groupDiscounts', 'earlyBirdDiscount', 'lastMinuteDiscount'])
            ->first();
        if (! $activity) {
            return response()->json(['error' => 'activity_not_found'], 404);
        }

        $travelDate = isset($validated['start_date'])
            ? \Carbon\CarbonImmutable::parse($validated['start_date'])
            : null;

        try {
            $quote = app(ActivityDiscountService::class)->quote($activity, $headcount, $travelDate);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => 'activity_pricing_missing',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'activity_slug' => $slug,
            'headcount' => $quote['headcount'],
            'adults' => $adults,
            'children' => $children,
            'per_pax' => $quote['per_pax'],
            'subtotal' => $quote['subtotal'],
            'selected_tier' => $quote['selected_tier'] ? [
                'id' => $quote['selected_tier']->id,
                'min_people' => $quote['selected_tier']->min_people,
                'discount_amount' => (float) $quote['selected_tier']->discount_amount,
                'discount_type' => $quote['selected_tier']->discount_type,
            ] : null,
            'complete_groups' => $quote['complete_groups'],
            'discount_total' => $quote['discount_total'],
            'early_bird_discount' => $quote['early_bird_discount'],
            'last_minute_discount' => $quote['last_minute_discount'],
            'combined_discount' => $quote['combined_discount'],
            'selected_early_bird' => $quote['selected_early_bird'] ? [
                'id' => $quote['selected_early_bird']->id,
                'enabled' => (bool) $quote['selected_early_bird']->enabled,
                'days_before_start' => (int) $quote['selected_early_bird']->days_before_start,
                'discount_amount' => (float) $quote['selected_early_bird']->discount_amount,
                'discount_type' => $quote['selected_early_bird']->discount_type,
            ] : null,
            'selected_last_minute' => $quote['selected_last_minute'] ? [
                'id' => $quote['selected_last_minute']->id,
                'enabled' => (bool) $quote['selected_last_minute']->enabled,
                'days_before_start' => (int) $quote['selected_last_minute']->days_before_start,
                'discount_amount' => (float) $quote['selected_last_minute']->discount_amount,
                'discount_type' => $quote['selected_last_minute']->discount_type,
            ] : null,
            'days_ahead' => $quote['days_ahead'],
            'final_amount' => $quote['final_amount'],
            'currency' => $quote['currency'],
        ]);
    }
}
