<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItineraryRequest;
use App\Models\Itinerary;
use App\Services\ItineraryDeepCopyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomerItineraryController extends Controller
{
    // ==================== RESOURCE METHODS ====================

    public function getCities(Request $request): JsonResponse
    {
        $query = \App\Models\City::with(['state.country'])
            ->select('id', 'name', 'slug', 'state_id');

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $cities = $query->orderBy('name')
            ->paginate($request->get('per_page', 100));

        return response()->json([
            'success' => true,
            'data' => $cities->items(),
            'total' => $cities->total(),
        ]);
    }

    public function getActivities(Request $request): JsonResponse
    {
        $request->validate(['city_id' => 'required|integer|exists:cities,id']);

        $query = \App\Models\Activity::whereHas('locations', function ($q) use ($request) {
            $q->where('city_id', $request->city_id);
        })
            ->with(['tags.tag', 'mediaGallery' => function ($q) {
                $q->where('is_featured', true);
            }, 'mediaGallery.media', 'locations.city', 'locations.place', 'pricing'])
            ->select('id', 'name', 'slug', 'description', 'item_type', 'featured_activity');

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $activities = $query->get()->map(function ($activity) {
            $primaryLocation = $activity->locations->where('location_type', 'primary')->first()
                ?? $activity->locations->first();
            $featuredMedia = $activity->mediaGallery->where('is_featured', true)->first();

            return [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'city_name' => $primaryLocation?->city?->name,
                'place_name' => $primaryLocation?->place?->name,
                'duration_minutes' => $primaryLocation?->duration,
                'type' => $activity->item_type,
                'featured_image' => $featuredMedia?->media?->url,
                'pricing' => $activity->pricing,
                'tags' => $activity->tags->map(fn ($t) => [
                    'id' => $t->tag?->id,
                    'name' => $t->tag?->name,
                ]),
            ];
        });

        return response()->json(['success' => true, 'data' => $activities]);
    }

    public function getTransfers(Request $request): JsonResponse
    {
        $request->validate(['city_id' => 'required|integer|exists:cities,id']);

        $placeIds = \App\Models\Place::where('city_id', $request->city_id)->pluck('id');

        $transfers = \App\Models\Transfer::whereHas('vendorRoutes', function ($query) use ($placeIds) {
            $query->whereIn('pickup_place_id', $placeIds);
        })
            ->select('id', 'name', 'slug', 'transfer_type', 'description')
            ->with(['vendorRoutes.pickupPlace.city', 'vendorRoutes.dropoffPlace.city', 'mediaGallery' => function ($q) {
                $q->where('is_featured', true);
            }, 'mediaGallery.media'])
            ->get()
            ->map(function ($transfer) {
                $featuredMedia = $transfer->mediaGallery->where('is_featured', true)->first();
                $route = $transfer->vendorRoutes?->first();

                return [
                    'id' => $transfer->id,
                    'name' => $transfer->name,
                    'slug' => $transfer->slug,
                    'vehicle_type' => $route?->vehicle_type,
                    'featured_image' => $featuredMedia?->media?->url,
                    'pickup_city_name' => $route?->pickupPlace?->city?->name,
                    'dropoff_city_name' => $route?->dropoffPlace?->city?->name,
                ];
            });

        return response()->json(['success' => true, 'data' => $transfers]);
    }

    // ==================== ITINERARY METHODS ====================

    public function getEditData(string $slug): JsonResponse
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
        ])->original()->where('slug', $slug)->first();

        if (! $itinerary) {
            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $itinerary,
        ]);
    }

    public function saveCustomized(StoreItineraryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $original = Itinerary::original()->findOrFail($validated['parent_itinerary_id']);

        $service = new ItineraryDeepCopyService;
        $copy = $service->deepCopy(
            $original,
            $validated['schedules'],
            userId: Auth::id(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Itinerary saved to your collection.',
            'data' => $copy,
        ], 201);
    }

    public function myItineraries(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'view' => ['sometimes', \Illuminate\Validation\Rule::in(['active', 'trash'])],
            'status' => ['sometimes', \Illuminate\Validation\Rule::in(['draft'])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $view = $validated['view'] ?? 'active';
        $status = $validated['status'] ?? null;

        if ($view === 'trash') {
            $query = Itinerary::onlyTrashed()
                ->standaloneCreator()
                ->whereHas('meta', fn ($meta) => $meta->where('creator_id', $userId));
        } else {
            $query = Itinerary::query()->where(function ($query) use ($userId, $status): void {
                if ($status === null) {
                    $query->whereHas('meta', function ($meta) use ($userId): void {
                        $meta->where('user_id', $userId)
                            ->where(fn ($statusQuery) => $statusQuery
                                ->whereNull('status')
                                ->orWhere('status', '!=', 'draft'));
                    });
                }

                $query->orWhere(function ($creatorQuery) use ($userId, $status): void {
                    $creatorQuery->standaloneCreator()
                        ->whereHas('meta', function ($meta) use ($userId, $status): void {
                            $meta->where('creator_id', $userId);
                            if ($status !== null) {
                                $meta->where('status', $status);
                            }
                        });
                });
            });
        }

        $itineraries = $query
            ->with([
                'parentItinerary',
                'mediaGallery.media',
                'locations.city',
                'schedules' => fn ($q) => $q->orderBy('day'),
                'schedules.activities.activity.mediaGallery.media',
                'schedules.transfers.transfer.mediaGallery.media',
            ])
            ->latest()
            ->paginate(15);

        $itineraries->getCollection()->each->append(['featured_image', 'gallery_images']);
        $itineraries->getCollection()->transform(function (Itinerary $itinerary): Itinerary {
            if ($itinerary->trashed()) {
                $purgeAt = $itinerary->deleted_at->copy()->addDays(30);
                $today = now(config('app.timezone'))->startOfDay();
                $purgeDay = $purgeAt->copy()->timezone(config('app.timezone'))->startOfDay();
                $itinerary->setAttribute('purge_at', $purgeAt->toIso8601String());
                $itinerary->setAttribute('days_until_purge', max(0, $today->diffInDays($purgeDay, false)));
            }

            return $itinerary;
        });

        return response()->json([
            'success' => true,
            'data' => $itineraries,
        ]);
    }

    public function bookItinerary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'itinerary_id' => 'required|integer',
            'travel_date' => 'required|date|after:today',
            'number_of_travelers' => 'required|integer|min:1',
            'addons' => 'array',
            'addons.*.addon_id' => 'integer|exists:addons,id',
            'addons.*.quantity' => 'integer|min:1',
        ]);

        if (! Itinerary::publiclyVisible()->whereKey($validated['itinerary_id'])->exists()) {
            throw ValidationException::withMessages([
                'itinerary_id' => 'The selected itinerary is not available for booking.',
            ]);
        }

        // This will integrate with existing order creation flow
        // For now, return a placeholder response
        return response()->json([
            'success' => true,
            'message' => 'Booking flow to be integrated with existing order system',
            'data' => $validated,
        ]);
    }
}
