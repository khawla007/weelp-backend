<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ItineraryApprovedMail;
use App\Mail\ItineraryEditApprovedMail;
use App\Mail\ItineraryEditRejectedMail;
use App\Mail\ItineraryRejectedMail;
use App\Mail\ItineraryRemovalApprovedMail;
use App\Mail\ItineraryRemovalRejectedMail;
use App\Models\Itinerary;
use App\Models\Notification;
use App\Services\ItineraryDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreatorItineraryManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Itinerary::creatorCopies()
            ->with(['creator', 'parentItinerary.locations.city', 'locations', 'mediaGallery.media']);

        if ($request->has('status')) {
            $query->whereHas('meta', fn($q) => $q->where('status', $request->status));
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

        // Add computed image attributes with fallback
        $data['featured_image'] = $itinerary->featured_image;
        $data['gallery_images'] = $itinerary->gallery_images;

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
                        'activity_id' => $activity->activity_id,
                        'name' => $activityModel?->name,
                        'start_time' => $activity->start_time,
                        'end_time' => $activity->end_time,
                        'notes' => $activity->notes,
                        'price' => $activity->price,
                        'included' => $activity->included,
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
                        'transfer_id' => $transfer->transfer_id,
                        'name' => $transfer->transfer ? $transfer->transfer->name : null,
                        'start_time' => $transfer->start_time,
                        'end_time' => $transfer->end_time,
                        'pickup_location' => $transfer->pickup_location,
                        'dropoff_location' => $transfer->dropoff_location,
                        'pax' => $transfer->pax,
                        'price' => $transfer->price,
                        'included' => $transfer->included,
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

        if ($itinerary->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending itineraries can be approved.',
            ], 422);
        }

        $itinerary->update(['status' => 'approved']);

        Notification::create([
            'user_id' => $itinerary->creator_id,
            'type' => 'itinerary_approved',
            'title' => 'Itinerary Approved',
            'message' => "Your itinerary \"{$itinerary->name}\" has been approved and is now visible on the Explore page.",
            'data' => ['itinerary_id' => $itinerary->id],
        ]);

        $itinerary->load('creator');
        try {
            Mail::to($itinerary->creator->email)->send(new ItineraryApprovedMail($itinerary, $itinerary->creator));
        } catch (\Exception $e) {
            Log::error('Failed to send itinerary approved email', [
                'itinerary_id' => $itinerary->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Itinerary approved.',
            'data' => $itinerary,
        ]);
    }

    public function updateAndApprove(Request $request, $id): JsonResponse
    {
        $itinerary = Itinerary::creatorCopies()->find($id);
        if (!$itinerary) {
            return response()->json(['success' => false, 'message' => 'Creator itinerary not found'], 404);
        }

        if ($itinerary->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending itineraries can be approved.'], 422);
        }

        // Wrap everything in a transaction for atomicity
        $response = DB::transaction(function () use ($request, $id, $itinerary) {
            // Update using existing ItineraryController logic
            $adminController = app(\App\Http\Controllers\Admin\ItineraryController::class);
            $updateResponse = $adminController->update($request, $id);

            if ($updateResponse->getStatusCode() !== 200) {
                // Transaction will rollback automatically
                return $updateResponse;
            }

            // Reload to get fresh data after update
            $itinerary->fresh();

            // Approve the itinerary
            $itinerary->update(['status' => 'approved']);

            // Send notification (within transaction for consistency)
            Notification::create([
                'user_id' => $itinerary->creator_id,
                'type' => 'itinerary_approved',
                'title' => 'Itinerary Approved',
                'message' => "Your itinerary \"{$itinerary->name}\" has been approved.",
                'data' => ['itinerary_id' => $itinerary->id],
            ]);

            // Load creator before exiting transaction
            $itinerary->load('creator');

            return response()->json([
                'success' => true,
                'message' => 'Itinerary updated and approved.',
                'data' => $itinerary,
            ]);
        });

        // Email is sent OUTSIDE transaction (after commit)
        // We don't want email sending failure to rollback the database changes
        try {
            Mail::to($itinerary->creator->email)->send(new ItineraryApprovedMail($itinerary, $itinerary->creator));
        } catch (\Exception $e) {
            Log::error('Failed to send itinerary approved email', [
                'itinerary_id' => $itinerary->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
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

        if ($itinerary->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending itineraries can be rejected.',
            ], 422);
        }

        $itinerary->update(['status' => 'rejected']);

        Notification::create([
            'user_id' => $itinerary->creator_id,
            'type' => 'itinerary_rejected',
            'title' => 'Itinerary Rejected',
            'message' => "Your itinerary \"{$itinerary->name}\" was not approved.",
            'data' => ['itinerary_id' => $itinerary->id],
        ]);

        $itinerary->load('creator');
        try {
            Mail::to($itinerary->creator->email)->send(new ItineraryRejectedMail($itinerary, $itinerary->creator));
        } catch (\Exception $e) {
            Log::error('Failed to send itinerary rejected email', [
                'itinerary_id' => $itinerary->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Itinerary rejected.',
            'data' => $itinerary,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $itinerary = Itinerary::creatorCopies()->find($id);
        if (!$itinerary) {
            return response()->json(['success' => false, 'message' => 'Creator itinerary not found'], 404);
        }

        $adminController = app(\App\Http\Controllers\Admin\ItineraryController::class);
        return $adminController->update($request, $id);
    }

    public function destroy($id): JsonResponse
    {
        $itinerary = Itinerary::creatorCopies()->find($id);
        if (!$itinerary) {
            return response()->json(['success' => false, 'message' => 'Creator itinerary not found'], 404);
        }

        DB::transaction(function () use ($itinerary) {
            if ($itinerary->draft_itinerary_id) {
                $draft = Itinerary::find($itinerary->draft_itinerary_id);
                if ($draft) {
                    (new ItineraryDraftService())->deleteDraft($draft);
                }
            }

            $itinerary->schedules()->each(function ($schedule) {
                $schedule->activities()->delete();
                $schedule->transfers()->delete();
                $schedule->delete();
            });
            $itinerary->locations()->delete();
            $itinerary->basePricing?->variations()?->delete();
            $itinerary->basePricing?->delete();
            $itinerary->inclusionsExclusions()->delete();
            $itinerary->mediaGallery()->delete();
            $itinerary->seo?->delete();
            $itinerary->categories()->delete();
            $itinerary->tags()->delete();
            $itinerary->attributes()->delete();
            $itinerary->addons()->delete();
            $itinerary->delete();
        });

        return response()->json(['success' => true, 'message' => 'Itinerary removed.']);
    }

    public function approveEdit($id): JsonResponse
    {
        $itinerary = Itinerary::creatorCopies()->find($id);
        if (!$itinerary || !$itinerary->draft_itinerary_id) {
            return response()->json(['success' => false, 'message' => 'No pending edit found for this itinerary.'], 404);
        }

        $draft = Itinerary::find($itinerary->draft_itinerary_id);
        if (!$draft || $draft->status !== 'edit_pending') {
            return response()->json(['success' => false, 'message' => 'Draft is not pending approval.'], 422);
        }

        $service = new ItineraryDraftService();
        $updated = $service->mergeDraft($itinerary, $draft);

        Notification::create([
            'user_id' => $itinerary->creator_id,
            'type' => 'itinerary_edit_approved',
            'title' => 'Itinerary Edit Approved',
            'message' => "Your edits to \"{$updated->name}\" have been approved.",
            'data' => ['itinerary_id' => $updated->id],
        ]);

        $updated->load('creator');
        try {
            Mail::to($updated->creator->email)->send(new ItineraryEditApprovedMail($updated, $updated->creator));
        } catch (\Exception $e) {
            Log::error('Failed to send edit approved email', [
                'itinerary_id' => $updated->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Edit approved and changes applied.', 'data' => $updated]);
    }

    public function rejectEdit($id): JsonResponse
    {
        $itinerary = Itinerary::creatorCopies()->find($id);
        if (!$itinerary || !$itinerary->draft_itinerary_id) {
            return response()->json(['success' => false, 'message' => 'No pending edit found for this itinerary.'], 404);
        }

        $draft = Itinerary::find($itinerary->draft_itinerary_id);
        if (!$draft || $draft->status !== 'edit_pending') {
            return response()->json(['success' => false, 'message' => 'Draft is not pending approval.'], 422);
        }

        $service = new ItineraryDraftService();
        $service->deleteDraft($draft);

        Notification::create([
            'user_id' => $itinerary->creator_id,
            'type' => 'itinerary_edit_rejected',
            'title' => 'Itinerary Edit Rejected',
            'message' => "Your edits to \"{$itinerary->name}\" were not approved.",
            'data' => ['itinerary_id' => $itinerary->id],
        ]);

        $itinerary->load('creator');
        try {
            Mail::to($itinerary->creator->email)->send(new ItineraryEditRejectedMail($itinerary, $itinerary->creator));
        } catch (\Exception $e) {
            Log::error('Failed to send edit rejected email', [
                'itinerary_id' => $itinerary->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Edit rejected.']);
    }

    public function approveRemoval($id): JsonResponse
    {
        $itinerary = Itinerary::creatorCopies()->find($id);
        if (!$itinerary || $itinerary->removal_status !== 'requested') {
            return response()->json(['success' => false, 'message' => 'No pending removal request found.'], 404);
        }

        $itinerary->update([
            'status' => 'deleted',
            'removal_status' => 'approved',
        ]);

        Notification::create([
            'user_id' => $itinerary->creator_id,
            'type' => 'itinerary_removal_approved',
            'title' => 'Itinerary Removed',
            'message' => "Your itinerary \"{$itinerary->name}\" has been removed.",
            'data' => ['itinerary_id' => $itinerary->id],
        ]);

        $itinerary->load('creator');
        try {
            Mail::to($itinerary->creator->email)->send(new ItineraryRemovalApprovedMail($itinerary, $itinerary->creator));
        } catch (\Exception $e) {
            Log::error('Failed to send removal approved email', [
                'itinerary_id' => $itinerary->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Removal approved.']);
    }

    public function rejectRemoval($id): JsonResponse
    {
        $itinerary = Itinerary::creatorCopies()->find($id);
        if (!$itinerary || $itinerary->removal_status !== 'requested') {
            return response()->json(['success' => false, 'message' => 'No pending removal request found.'], 404);
        }

        $itinerary->update([
            'removal_status' => null,
            'removal_reason' => null,
        ]);

        Notification::create([
            'user_id' => $itinerary->creator_id,
            'type' => 'itinerary_removal_rejected',
            'title' => 'Removal Request Declined',
            'message' => "Your removal request for \"{$itinerary->name}\" was declined.",
            'data' => ['itinerary_id' => $itinerary->id],
        ]);

        $itinerary->load('creator');
        try {
            Mail::to($itinerary->creator->email)->send(new ItineraryRemovalRejectedMail($itinerary, $itinerary->creator));
        } catch (\Exception $e) {
            Log::error('Failed to send removal rejected email', [
                'itinerary_id' => $itinerary->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Removal rejected.']);
    }
}
