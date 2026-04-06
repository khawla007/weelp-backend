<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Services\ItineraryDeepCopyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreatorItineraryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_itinerary_id' => 'required|exists:itineraries,id',
            'schedules' => 'required|array|min:1',
            'schedules.*.day' => 'required|integer|min:1',
            'schedules.*.title' => 'nullable|string|max:255',
            'schedules.*.activities' => 'nullable|array',
            'schedules.*.activities.*.activity_id' => 'required|exists:activities,id',
            'schedules.*.activities.*.start_time' => 'nullable|string',
            'schedules.*.activities.*.end_time' => 'nullable|string',
            'schedules.*.activities.*.notes' => 'nullable|string',
            'schedules.*.activities.*.price' => 'nullable|numeric',
            'schedules.*.activities.*.included' => 'nullable|boolean',
            'schedules.*.transfers' => 'nullable|array',
            'schedules.*.transfers.*.transfer_id' => 'required|exists:transfers,id',
            'schedules.*.transfers.*.pickup_location' => 'nullable|string|max:255',
            'schedules.*.transfers.*.dropoff_location' => 'nullable|string|max:255',
            'schedules.*.transfers.*.start_time' => 'nullable|string',
            'schedules.*.transfers.*.end_time' => 'nullable|string',
            'schedules.*.transfers.*.notes' => 'nullable|string',
            'schedules.*.transfers.*.price' => 'nullable|numeric',
            'schedules.*.transfers.*.included' => 'nullable|boolean',
            'schedules.*.transfers.*.pax' => 'nullable|integer',
        ]);

        $original = Itinerary::original()->findOrFail($validated['parent_itinerary_id']);

        $service = new ItineraryDeepCopyService();
        $copy = $service->deepCopy(
            $original,
            $validated['schedules'],
            creatorId: Auth::id(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Itinerary submitted for approval.',
            'data' => $copy,
        ], 201);
    }

    public function myItineraries(): JsonResponse
    {
        $itineraries = Itinerary::where('creator_id', Auth::id())
            ->with([
                'parentItinerary',
                'mediaGallery.media',
                'locations',
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
