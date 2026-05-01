<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\City;
use Illuminate\Http\Request;

class PublicToursSearchController extends Controller
{
    /**
     * Search activities and itineraries matching the hero filter.
     * Mirrors PublicHomeSearchController but:
     * - Restrices to activities + itineraries only (NO packages)
     * - Add a `from` (origin) filter for activities
     * - Response rows tagged with type: "activity" or type: "itinerary"
     */
    public function search(Request $request)
    {
        $request->validate([
            'from'       => 'nullable|string|max:200',
            'to'         => 'nullable|string|max:200',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'quantity'   => 'nullable|integer|min:1',
        ]);

        $from      = $request->query('from');        // city slug (origin)
        $to        = $request->query('to');          // city slug (destination)
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $quantity  = $request->query('quantity');

        // Get city IDs from destination slug
        $toCityIds = $to ? $this->getCityIdsFromSlug($to) : [];

        // Get city ID from origin slug
        $fromCityId = $from ? $this->getCityIdFromSlug($from) : null;

        $activities  = $this->buildActivityQuery($toCityIds, $fromCityId, $startDate, $endDate, $quantity)->get();
        $itineraries = $this->buildItineraryQuery($toCityIds, $startDate, $endDate, $quantity)->get();

        // Serialize and tag with type
        $activityRows   = $activities->map(fn($a) => $this->serializeActivity($a));
        $itineraryRows  = $itineraries->map(fn($i) => $this->serializeItinerary($i));

        $rows = $activityRows->concat($itineraryRows)->values();

        if ($rows->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No items found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $rows
        ]);
    }

    /**
     * Get city IDs from a slug (handles both city and region slugs like homesearch)
     */
    private function getCityIdsFromSlug(?string $slug): array
    {
        if (!$slug) {
            return [];
        }

        $cityIds = [];

        // Check if slug is a city
        $city = City::where('slug', $slug)->first();
        if ($city) {
            $cityIds[] = $city->id;
        }

        // Check if slug is a region
        $region = \App\Models\Region::where('slug', $slug)->first();
        if ($region) {
            $regionCities = City::whereHas('state.country.regions', function ($query) use ($region) {
                $query->where('regions.id', $region->id);
            })->pluck('id')->toArray();

            $cityIds = array_merge($cityIds, $regionCities);
        }

        return $cityIds;
    }

    /**
     * Get a single city ID from a slug
     */
    private function getCityIdFromSlug(?string $slug): ?int
    {
        if (!$slug) {
            return null;
        }

        $city = City::where('slug', $slug)->first();
        return $city?->id;
    }

    /**
     * Build activity query with all filters
     */
    private function buildActivityQuery(array $toCityIds, ?int $fromCityId, ?string $startDate, ?string $endDate, ?string $quantity)
    {
        $query = Activity::with([
            'categories' => function ($q) {
                $q->with('category:id,name');
            },
            'pricing',
            'groupDiscounts',
            'earlyBirdDiscount',
            'locations.city',
            'mediaGallery.media',
        ]);

        // Destination filter (to)
        if (!empty($toCityIds)) {
            $query->whereHas('locations', function ($q) use ($toCityIds) {
                $q->whereIn('city_id', $toCityIds);
            });
        }

        // Origin filter (from) - match primary location with city slug
        if ($fromCityId) {
            $query->whereHas('locations', function ($ql) use ($fromCityId) {
                $ql->where('location_type', 'primary')
                   ->where('city_id', $fromCityId);
            });
        }

        // Date filters (from homesearch)
        if ($startDate && $endDate) {
            $query->whereHas('availability', function ($q) use ($startDate, $endDate) {
                $q->where('date_based_activity', true)
                  ->where('start_date', '<=', $startDate)
                  ->where('end_date', '>=', $endDate);
            });
        }

        // Quantity filter (from homesearch)
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

        return $query;
    }

    /**
     * Build itinerary query with all filters
     * NOTE: `from` filter is intentionally NOT applied to itineraries in v1
     */
    private function buildItineraryQuery(array $toCityIds, ?string $startDate, ?string $endDate, ?string $quantity)
    {
        $query = Itinerary::with([
            'categories' => function ($q) {
                $q->with('category:id,name');
            },
            'locations.city',
            'basePricing.variations',
            'mediaGallery.media',
            'schedules.activities',
            'schedules.transfers.transfer.route',
            'schedules.transfers.transfer.pricingAvailability',
        ]);

        // Destination filter (to)
        if (!empty($toCityIds)) {
            $query->whereHas('locations', function ($q) use ($toCityIds) {
                $q->whereIn('city_id', $toCityIds);
            });
        }

        // Date filters (from homesearch)
        if ($startDate && $endDate) {
            $query->whereHas('availability', function ($q) use ($startDate, $endDate) {
                $q->where('date_based_itinerary', true)
                  ->where('start_date', '<=', $startDate)
                  ->where('end_date', '>=', $endDate);
            });
        }

        // Quantity filter (from homesearch)
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

        return $query;
    }

    /**
     * Serialize activity row (from homesearch) + type tag
     */
    private function serializeActivity($activity): array
    {
        $categories = $activity->categories->map(function ($activityCategory) {
            return [
                'id' => $activityCategory->category->id,
                'name' => $activityCategory->category->name,
            ];
        })->unique()->values();

        return [
            'type' => 'activity',
            'id' => $activity->id,
            'name' => $activity->name,
            'slug' => $activity->slug,
            'item_type' => $activity->item_type,
            'featured'  => $activity->featured_activity,
            'featured_image' => $activity->mediaGallery->where('is_featured', true)->first()?->media?->url
                ?? $activity->mediaGallery->first()?->media?->url,
            'city_slug' => $activity->locations->first()?->city?->slug,
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
    }

    /**
     * Serialize itinerary row (from homesearch) + type tag
     */
    private function serializeItinerary($itinerary): array
    {
        $categories = $itinerary->categories->map(function ($itineraryCategory) {
            return $itineraryCategory->category ? [
                'id' => $itineraryCategory->category->id,
                'name' => $itineraryCategory->category->name,
            ] : null;
        })->filter()->unique()->values();

        return [
            'type' => 'itinerary',
            'id' => $itinerary->id,
            'name' => $itinerary->name,
            'slug' => $itinerary->slug,
            'item_type' => $itinerary->item_type,
            'featured'  => $itinerary->featured_itinerary,
            'featured_image' => $itinerary->mediaGallery->where('is_featured', true)->first()?->media?->url
                ?? $itinerary->mediaGallery->first()?->media?->url,
            'city_slug' => $itinerary->locations->first()?->city?->slug,
            'categories' => $categories,
            'tags' => $itinerary->tags->map(fn ($tag) => [
                'slug' => $tag->slug,
                'name' => $tag->name,
            ])->toArray(),
            'schedule_total_price' => $itinerary->schedule_total_price,
            'schedule_total_currency' => $itinerary->schedule_total_currency,
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
    }
}
