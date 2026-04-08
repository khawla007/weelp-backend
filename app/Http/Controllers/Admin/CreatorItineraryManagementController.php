<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreatorItineraryManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Itinerary::creatorCopies()
            ->with(['creator', 'parentItinerary.locations.city', 'locations', 'mediaGallery.media']);

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
                'parentItinerary.locations.city',
                'locations.city',
                'schedules.activities.activity.locations.city',
                'schedules.activities.activity.mediaGallery.media',
                'schedules.transfers.transfer',
                'basePricing.variations',
                'basePricing.blackoutDates',
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

        // Transform to match the format expected by frontend components
        $data = $itinerary->toArray();

        // Flatten media_gallery: { media: { url } } → { url }
        $data['media_gallery'] = $itinerary->mediaGallery->map(function ($media) {
            return [
                'id' => $media->media->id,
                'name' => $media->media->name,
                'alt_text' => $media->media->alt_text,
                'url' => $media->media->url,
                'is_featured' => (bool) $media->is_featured,
            ];
        })->toArray();

        // Transform schedules with flattened activities and transfers
        $data['schedules'] = $itinerary->schedules->map(function ($schedule) {
            return [
                'day' => $schedule->day,
                'title' => $schedule->title,
                'activities' => $schedule->activities->map(function ($activity) {
                    $activityModel = $activity->activity;
                    $primaryLocation = $activityModel?->locations->where('location_type', 'primary')->first();
                    $featuredMedia = $activityModel?->mediaGallery->where('is_featured', true)->first();

                    return [
                        'id' => $activity->id,
                        'name' => $activityModel?->name,
                        'start_time' => $activity->start_time,
                        'end_time' => $activity->end_time,
                        'notes' => $activity->notes,
                        'price' => $activity->price,
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
                        'name' => $transfer->transfer ? $transfer->transfer->name : null,
                        'start_time' => $transfer->start_time,
                        'end_time' => $transfer->end_time,
                        'pickup_location' => $transfer->pickup_location,
                        'dropoff_location' => $transfer->dropoff_location,
                        'pax' => $transfer->pax,
                        'price' => $transfer->price,
                        'include_in_package' => $transfer->include_in_package,
                    ];
                }),
            ];
        });

        // Ensure base_pricing includes variations
        $data['base_pricing'] = $itinerary->basePricing;

        return response()->json([
            'success' => true,
            'data' => $data,
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

        Notification::create([
            'user_id' => $itinerary->creator_id,
            'type' => 'itinerary_approved',
            'title' => 'Itinerary Approved',
            'message' => "Your itinerary \"{$itinerary->name}\" has been approved and is now visible on the Explore page.",
            'data' => ['itinerary_id' => $itinerary->id],
        ]);

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

        Notification::create([
            'user_id' => $itinerary->creator_id,
            'type' => 'itinerary_rejected',
            'title' => 'Itinerary Rejected',
            'message' => "Your itinerary \"{$itinerary->name}\" was not approved.",
            'data' => ['itinerary_id' => $itinerary->id],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Itinerary rejected.',
            'data' => $itinerary,
        ]);
    }
}
