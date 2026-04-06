<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreatorItineraryManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Itinerary::creatorCopies()
            ->with(['creator', 'parentItinerary', 'locations', 'mediaGallery.media']);

        if ($request->has('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        $itineraries = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $itineraries,
        ]);
    }

    public function show($id): JsonResponse
    {
        $itinerary = Itinerary::creatorCopies()
            ->with([
                'creator',
                'creator.profile',
                'parentItinerary',
                'locations',
                'schedules.activities',
                'schedules.transfers',
                'basePricing',
                'inclusionsExclusions',
                'mediaGallery.media',
                'seo',
            ])
            ->find($id);

        if (!$itinerary) {
            return response()->json([
                'success' => false,
                'message' => 'Creator itinerary not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $itinerary,
        ]);
    }

    public function original($id): JsonResponse
    {
        $creatorCopy = Itinerary::creatorCopies()->find($id);

        if (!$creatorCopy) {
            return response()->json([
                'success' => false,
                'message' => 'Creator itinerary not found',
            ], 404);
        }

        $original = Itinerary::with([
            'locations',
            'schedules.activities',
            'schedules.transfers',
            'basePricing',
            'inclusionsExclusions',
            'mediaGallery.media',
            'seo',
        ])->find($creatorCopy->parent_itinerary_id);

        if (!$original) {
            return response()->json([
                'success' => false,
                'message' => 'Original itinerary not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $original,
        ]);
    }

    public function approve($id): JsonResponse
    {
        $itinerary = Itinerary::creatorCopies()->find($id);

        if (!$itinerary) {
            return response()->json([
                'success' => false,
                'message' => 'Creator itinerary not found',
            ], 404);
        }

        if ($itinerary->approval_status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending itineraries can be approved.',
            ], 422);
        }

        $itinerary->update(['approval_status' => 'approved']);

        return response()->json([
            'success' => true,
            'message' => 'Itinerary approved.',
            'data' => $itinerary,
        ]);
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $itinerary = Itinerary::creatorCopies()->find($id);

        if (!$itinerary) {
            return response()->json([
                'success' => false,
                'message' => 'Creator itinerary not found',
            ], 404);
        }

        if ($itinerary->approval_status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending itineraries can be rejected.',
            ], 422);
        }

        $itinerary->update(['approval_status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'Itinerary rejected.',
            'data' => $itinerary,
        ]);
    }
}
