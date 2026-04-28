<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Models\TransferRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicTransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $hasFilter = $request->filled('origin_type')
            || $request->filled('origin_id')
            || $request->filled('destination_type')
            || $request->filled('destination_id')
            || $request->filled('from_zone_id')
            || $request->filled('to_zone_id')
            || $request->filled('passengers')
            || $request->filled('city_id')
            || $request->filled('q');

        // Resolve effective origin_type/origin_id, letting explicit origin_* params
        // take precedence over the legacy `city_id` alias.
        $effectiveOriginType = $request->input('origin_type');
        $effectiveOriginId   = $request->integer('origin_id') ?: null;
        if (! $effectiveOriginType && ! $effectiveOriginId && $request->filled('city_id')) {
            $effectiveOriginType = 'city';
            $effectiveOriginId   = $request->integer('city_id') ?: null;
        }

        $destType = $request->input('destination_type');
        $destId   = $request->integer('destination_id') ?: null;

        $routeIds        = null;
        $hasLocationFilter = ($effectiveOriginType && $effectiveOriginId) || ($destType && $destId);

        // --- Priority 1: exact origin/destination match on TransferRoute rows ---
        if ($hasLocationFilter) {
            $exact = TransferRoute::query()
                ->where('is_active', true)
                ->when(
                    $effectiveOriginType && $effectiveOriginId,
                    fn ($q) => $q
                        ->where('origin_type', $effectiveOriginType)
                        ->where('origin_id', $effectiveOriginId)
                )
                ->when(
                    $destType && $destId,
                    fn ($q) => $q
                        ->where('destination_type', $destType)
                        ->where('destination_id', $destId)
                )
                ->pluck('id');

            if ($exact->isNotEmpty()) {
                $routeIds = $exact;
            }
        }

        // --- Priority 2: zone-derived fallback (only when exact match found nothing) ---
        if ($routeIds === null) {
            $fromZoneId = $request->integer('from_zone_id')
                ?: $this->resolveZoneId($effectiveOriginType, $effectiveOriginId);

            $toZoneId = $request->integer('to_zone_id')
                ?: $this->resolveZoneId($destType, $destId);

            if ($fromZoneId || $toZoneId) {
                $routeIds = TransferRoute::query()
                    ->where('is_active', true)
                    ->when($fromZoneId, fn ($q) => $q->where('from_zone_id', $fromZoneId))
                    ->when($toZoneId, fn ($q) => $q->where('to_zone_id', $toZoneId))
                    ->pluck('id');
            }
        }

        $applyRouteFilter = $routeIds !== null;

        $query = Transfer::with([
            'vendorRoutes.vendor',
            'vendorRoutes.route',
            'pricingAvailability.pricingTier',
            'pricingAvailability.availability',
            'mediaGallery.media',
            'seo',
            'schedule',
            'route.fromZone',
            'route.toZone',
            'route.origin',
            'route.destination',
        ])
            ->when($applyRouteFilter, fn ($q) => $q->whereIn('transfer_route_id', $routeIds ?? []))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = addcslashes((string) $request->string('q'), '%_\\');
                $q->where('name', 'like', '%' . $term . '%');
            })
            ->when($request->integer('passengers'), function ($q, $pax) {
                $q->where(function ($w) use ($pax) {
                    $w->whereDoesntHave('schedule')
                      ->orWhereHas('schedule', fn ($s) => $s
                          ->whereNull('maximum_passengers')
                          ->orWhere('maximum_passengers', '>=', $pax));
                });
            });

        // Back-compat: unpaginated legacy shape when caller sent no filters
        // AND did not ask for pagination (per_page/page absent).
        $wantsPagination = $request->filled('per_page') || $request->filled('page');

        if (! $hasFilter && ! $wantsPagination) {
            $transfers = $query->get();
            $items = $transfers->map(fn ($t) => $this->transformTransfer($t));

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transfers not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $items,
            ], 200);
        }

        $perPage = $request->integer('per_page', 12) ?: 12;
        $paginated = $query->paginate($perPage);

        $items = collect($paginated->items())->map(fn ($t) => $this->transformTransfer($t));

        // Preserve legacy 404 only when there is no filter AND no transfers at all.
        if (! $hasFilter && $items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Transfers not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ], 200);
    }

    /**
     * Shape a single Transfer model into the public API response array.
     */
    private function transformTransfer(Transfer $transfer): array
    {
        $data = $transfer->toArray();
        $featuredImage = $transfer->mediaGallery->where('is_featured', true)->first();
        $data['featured_image'] = $featuredImage?->media?->url
            ?? $transfer->mediaGallery->first()?->media?->url;
        $data['media_gallery'] = $transfer->mediaGallery->map(function ($media) {
            return [
                'id' => $media->media->id,
                'name' => $media->media->name,
                'alt_text' => $media->media->alt_text,
                'url' => $media->media->url,
                'is_featured' => (bool) $media->is_featured,
            ];
        })->toArray();
        unset($data['media_gallery_raw']);

        $zoneBasePrice = (float) ($transfer->resolvedZonePrice()?->base_price ?? 0);
        $pricingAvailability = $transfer->pricingAvailability;
        $nonVendorPricing    = ($pricingAvailability && ! $pricingAvailability->is_vendor) ? $pricingAvailability : null;
        $transferPrice       = $nonVendorPricing ? (float) $nonVendorPricing->transfer_price : 0.0;

        $data['zone_base_price']           = $zoneBasePrice;
        $data['transfer_price']            = $transferPrice;
        $data['luggage_per_bag_rate']      = $transfer->luggagePerBagRate();
        $data['waiting_per_minute_rate']   = $transfer->waitingPerMinuteRate();
        // route_price is the unit price (per-person or per-vehicle).
        // Frontend multiplies by headcount when price_type === 'per_person'.
        $data['route_price']               = $transfer->computeRoutePrice(1);
        $data['price_type']                = $transfer->pricingPriceType();
        $data['route_currency']            = $transfer->routeCurrency();
        $data['route_duration_minutes'] = $transfer->route?->duration_minutes;

        $origin = $transfer->route?->origin;
        $destination = $transfer->route?->destination;
        $data['origin_name'] = $origin?->name ?? $origin?->title;
        $data['destination_name'] = $destination?->name ?? $destination?->title;
        $data['route_name'] = $transfer->route?->name;
        $data['vehicle_type'] = $transfer->vendorRoutes?->vehicle_type;

        return $data;
    }

    /**
     * Resolve the first active zone ID that contains the given location.
     */
    private function resolveZoneId(?string $type, ?int $id): ?int
    {
        if (! $type || ! $id) {
            return null;
        }

        return DB::table('transfer_zone_locations')
            ->join('transfer_zones', 'transfer_zones.id', '=', 'transfer_zone_locations.transfer_zone_id')
            ->where('transfer_zone_locations.locatable_type', $type)
            ->where('transfer_zone_locations.locatable_id', $id)
            ->where('transfer_zones.is_active', true)
            ->orderBy('transfer_zones.sort_order')
            ->value('transfer_zone_locations.transfer_zone_id');
    }

    public function show($id): JsonResponse
    {
        $transfer = Transfer::with([
            'vendorRoutes.vendor',
            'vendorRoutes.route',
            'pricingAvailability.pricingTier',
            'pricingAvailability.availability',
            'mediaGallery.media',
            'seo'
        ])->find($id);

        // if (!$transfer) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Transfer not found'
        //     ], 404);
        // }

        // return response()->json([
        //     'success' => true,
        //     'data' => $transfer
        // ]);
        if (empty($transfer)) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found'
            ], 404);
        }

        $data = $transfer->toArray();
        $featuredImage = $transfer->mediaGallery->where('is_featured', true)->first();
        $data['featured_image'] = $featuredImage?->media?->url
            ?? $transfer->mediaGallery->first()?->media?->url;
        $data['media_gallery'] = $transfer->mediaGallery->map(function ($media) {
            return [
                'id' => $media->media->id,
                'name' => $media->media->name,
                'alt_text' => $media->media->alt_text,
                'url' => $media->media->url,
                'is_featured' => (bool) $media->is_featured,
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }
}
