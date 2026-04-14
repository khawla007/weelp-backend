<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Activity;
use App\Models\Place;
use App\Models\Transfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SharedItineraryResourcesController extends Controller
{
    public function getCities(Request $request): JsonResponse
    {
        $query = City::with(['state.country'])
            ->select('id', 'name', 'slug', 'state_id');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $cities = $query->orderBy('name')
            ->paginate($request->get('per_page', 100));

        $data = $cities->items();
        // Transform each city to include state and country as nested objects
        $transformed = collect($data)->map(function ($city) {
            return [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'state' => $city->state ? ['id' => $city->state->id, 'name' => $city->state->name] : null,
                'country' => $city->state && $city->state->country ? ['id' => $city->state->country->id, 'name' => $city->state->country->name] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformed,
            'total' => $cities->total(),
        ]);
    }

    public function getActivities(Request $request): JsonResponse
    {
        $request->validate(['city_id' => 'required|integer|exists:cities,id']);

        $query = Activity::whereHas('locations', function ($q) use ($request) {
            $q->where('city_id', $request->city_id);
        })
            ->with(['tags.tag', 'mediaGallery' => function ($q) {
                $q->where('is_featured', true);
            }, 'mediaGallery.media', 'locations.city', 'locations.place'])
            ->select('id', 'name', 'slug', 'description', 'item_type', 'featured_activity');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
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
                'tags' => $activity->tags->map(fn($t) => [
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

        $transfers = Transfer::whereHas('vendorRoutes', function ($query) use ($placeIds) {
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

    public function getPlaces(Request $request): JsonResponse
    {
        $request->validate(['city_id' => 'required|integer|exists:cities,id']);

        $places = \App\Models\Place::where('city_id', $request->city_id)
            ->select('id', 'name', 'city_id')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $places]);
    }
}
