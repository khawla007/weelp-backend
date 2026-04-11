<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItineraryRequest;
use App\Models\Itinerary;
use App\Services\ItineraryDeepCopyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CustomerItineraryController extends Controller
{
    public function store(StoreItineraryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $original = Itinerary::original()->findOrFail($validated['parent_itinerary_id']);

        $service = new ItineraryDeepCopyService();
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

    public function myItineraries(): JsonResponse
    {
        $userId = Auth::id();

        $itineraries = Itinerary::whereHas('meta', function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    $q2->where('user_id', $userId)
                       ->orWhere('creator_id', $userId);
                })->where('status', '!=', 'draft');
            })
            ->with([
                'parentItinerary',
                'mediaGallery.media',
                'locations.city',
                'schedules.activities',
                'schedules.transfers',
            ])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $itineraries,
        ]);
    }
}
