<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItineraryRequest;
use App\Mail\ItineraryEditSubmittedMail;
use App\Mail\ItineraryRemovalRequestedMail;
use App\Mail\ItinerarySubmittedAdminMail;
use App\Mail\ItinerarySubmittedCreatorMail;
use App\Models\Itinerary;
use App\Models\Notification;
use App\Services\ItineraryDeepCopyService;
use App\Services\ItineraryDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreatorItineraryController extends Controller
{
    public function store(StoreItineraryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $original = Itinerary::original()->findOrFail($validated['parent_itinerary_id']);

        $service = new ItineraryDeepCopyService();
        $copy = $service->deepCopy(
            $original,
            $validated['schedules'],
            creatorId: Auth::id(),
        );

        $creator = Auth::user();
        try {
            Mail::to($creator->email)->send(new ItinerarySubmittedCreatorMail($copy, $creator));
            Mail::to(config('mail.admin_address', 'khawla@fanaticcoders.com'))->send(new ItinerarySubmittedAdminMail($copy, $creator));
        } catch (\Exception $e) {
            Log::error('Failed to send itinerary submission emails', [
                'itinerary_id' => $copy->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Itinerary submitted for approval.',
            'data' => $copy,
        ], 201);
    }

    public function myItineraries(): JsonResponse
    {
        $itineraries = Itinerary::whereHas('meta', fn($q) => $q->where('creator_id', Auth::id()))
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

    public function getDraft($id): JsonResponse
    {
        $draft = Itinerary::whereHas('meta', fn($q) => $q->where('creator_id', Auth::id())
            ->whereIn('status', ['draft', 'edit_pending']))
            ->with([
                'locations.city',
                'schedules.activities.activity.mediaGallery.media',
                'schedules.activities.activity.locations.city',
                'schedules.transfers.transfer',
            ])
            ->find($id);

        if (!$draft) {
            return response()->json(['success' => false, 'message' => 'Draft not found.'], 404);
        }

        $data = $draft->toArray();
        $data['schedules'] = $draft->schedules->map(function ($schedule) {
            return [
                'day' => $schedule->day,
                'title' => $schedule->title,
                'activities' => $schedule->activities->map(function ($activity) {
                    $activityModel = $activity->activity;
                    return [
                        'id' => $activity->id,
                        'activity_id' => $activity->activity_id,
                        'name' => $activityModel?->name,
                        'start_time' => $activity->start_time,
                        'end_time' => $activity->end_time,
                        'notes' => $activity->notes,
                        'price' => $activity->price,
                        'included' => $activity->included,
                    ];
                }),
                'transfers' => $schedule->transfers->map(function ($transfer) {
                    return [
                        'id' => $transfer->id,
                        'transfer_id' => $transfer->transfer_id,
                        'name' => $transfer->transfer?->name,
                        'start_time' => $transfer->start_time,
                        'end_time' => $transfer->end_time,
                        'pickup_location' => $transfer->pickup_location,
                        'dropoff_location' => $transfer->dropoff_location,
                        'pax' => $transfer->pax,
                        'price' => $transfer->price,
                        'included' => $transfer->included,
                    ];
                }),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function requestEdit($id): JsonResponse
    {
        $itinerary = Itinerary::whereHas('meta', fn($q) => $q->where('creator_id', Auth::id()))
            ->approved()
            ->find($id);

        if (!$itinerary) {
            return response()->json(['success' => false, 'message' => 'Approved itinerary not found.'], 404);
        }

        if ($itinerary->draft_itinerary_id) {
            return response()->json(['success' => false, 'message' => 'An edit draft already exists for this itinerary.'], 422);
        }

        if ($itinerary->removal_status === 'requested') {
            return response()->json(['success' => false, 'message' => 'Cannot edit while a removal request is pending.'], 422);
        }

        $service = new ItineraryDraftService();
        $draft = $service->createDraft($itinerary);

        return response()->json(['success' => true, 'message' => 'Edit draft created.', 'data' => $draft], 201);
    }

    public function updateDraft(Request $request, $id): JsonResponse
    {
        $draft = Itinerary::whereHas('meta', fn($q) => $q->where('creator_id', Auth::id()))
            ->draft()
            ->find($id);

        if (!$draft) {
            return response()->json(['success' => false, 'message' => 'Draft not found.'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'schedules' => 'nullable|array',
        ]);

        if ($request->has('name')) {
            $name = $request->name;
            $baseSlug = Str::slug($name);
            $slug = $baseSlug . '-c' . Auth::id() . '-' . time();

            while (Itinerary::where('slug', $slug)->where('id', '!=', $draft->id)->exists()) {
                $slug = $baseSlug . '-c' . Auth::id() . '-' . time() . '-' . Str::random(4);
            }

            $draft->update([
                'name' => $name,
                'slug' => $slug,
                'description' => $request->description ?? $draft->description,
            ]);
        } elseif ($request->has('description')) {
            $draft->update(['description' => $request->description]);
        }

        if ($request->has('schedules')) {
            $draft->schedules()->each(function ($schedule) {
                $schedule->activities()->delete();
                $schedule->transfers()->delete();
                $schedule->delete();
            });

            foreach ($request->schedules as $scheduleData) {
                $schedule = \App\Models\ItinerarySchedule::create([
                    'itinerary_id' => $draft->id,
                    'day' => $scheduleData['day'],
                    'title' => $scheduleData['title'] ?? null,
                ]);

                if (!empty($scheduleData['activities'])) {
                    foreach ($scheduleData['activities'] as $activityData) {
                        \App\Models\ItineraryActivity::create([
                            'schedule_id' => $schedule->id,
                            'activity_id' => $activityData['activity_id'],
                            'start_time' => $activityData['start_time'] ?? null,
                            'end_time' => $activityData['end_time'] ?? null,
                            'notes' => $activityData['notes'] ?? null,
                            'price' => $activityData['price'] ?? null,
                            'included' => $activityData['included'] ?? true,
                        ]);
                    }
                }

                if (!empty($scheduleData['transfers'])) {
                    foreach ($scheduleData['transfers'] as $transferData) {
                        \App\Models\ItineraryTransfer::create([
                            'schedule_id' => $schedule->id,
                            'transfer_id' => $transferData['transfer_id'],
                            'pickup_location' => $transferData['pickup_location'] ?? null,
                            'dropoff_location' => $transferData['dropoff_location'] ?? null,
                            'start_time' => $transferData['start_time'] ?? null,
                            'end_time' => $transferData['end_time'] ?? null,
                            'notes' => $transferData['notes'] ?? null,
                            'price' => $transferData['price'] ?? null,
                            'included' => $transferData['included'] ?? true,
                            'pax' => $transferData['pax'] ?? null,
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Draft updated.',
            'data' => $draft->fresh(['schedules.activities', 'schedules.transfers', 'locations']),
        ]);
    }

    public function submitDraft($id): JsonResponse
    {
        $draft = Itinerary::whereHas('meta', fn($q) => $q->where('creator_id', Auth::id()))
            ->draft()
            ->find($id);

        if (!$draft) {
            return response()->json(['success' => false, 'message' => 'Draft not found.'], 404);
        }

        $draft->update(['status' => 'edit_pending']);

        $approved = Itinerary::whereHas('meta', fn($q) => $q->where('draft_itinerary_id', $draft->id))->first();

        $creator = Auth::user();
        try {
            Mail::to(config('mail.admin_address', 'khawla@fanaticcoders.com'))
                ->send(new ItineraryEditSubmittedMail($approved ?? $draft, $creator));
        } catch (\Exception $e) {
            Log::error('Failed to send draft submission email', [
                'draft_id' => $draft->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Draft submitted for review.']);
    }

    public function requestRemoval(Request $request, $id): JsonResponse
    {
        $itinerary = Itinerary::whereHas('meta', fn($q) => $q->where('creator_id', Auth::id()))
            ->approved()
            ->find($id);

        if (!$itinerary) {
            return response()->json(['success' => false, 'message' => 'Approved itinerary not found.'], 404);
        }

        if ($itinerary->draft_itinerary_id) {
            return response()->json(['success' => false, 'message' => 'Cannot request removal while an edit draft exists.'], 422);
        }

        if ($itinerary->removal_status === 'requested') {
            return response()->json(['success' => false, 'message' => 'A removal request is already pending.'], 422);
        }

        $request->validate(['reason' => 'nullable|string|max:1000']);

        $itinerary->update([
            'removal_status' => 'requested',
            'removal_reason' => $request->reason,
        ]);

        $creator = Auth::user();
        try {
            Mail::to(config('mail.admin_address', 'khawla@fanaticcoders.com'))
                ->send(new ItineraryRemovalRequestedMail($itinerary, $creator));
        } catch (\Exception $e) {
            Log::error('Failed to send removal request email', [
                'itinerary_id' => $itinerary->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Removal request submitted.']);
    }

    /**
     * Create a new creator itinerary draft (fresh submission from Explore page)
     * @param Request $request
     * @return JsonResponse
     */
    public function createDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:itineraries,slug',
            'description' => 'required|string',
            'locations' => 'required|array|min:1',
            'locations.*' => 'exists:cities,id',
            'featured_itinerary' => 'boolean',
            'private_itinerary' => 'boolean',
            'schedules' => 'required|array|min:1',
            'schedules.*.day' => 'required|integer',
            'schedules.*.title' => 'nullable|string|max:255',
            'activities' => 'array',
            'activities.*.day' => 'required|integer',
            'activities.*.activity_id' => 'required|exists:activities,id',
            'activities.*.start_time' => 'nullable|string',
            'activities.*.end_time' => 'nullable|string',
            'activities.*.price' => 'nullable|numeric',
            'activities.*.included' => 'boolean',
            'activities.*.notes' => 'nullable|string',
            'transfers' => 'array',
            'transfers.*.day' => 'required|integer',
            'transfers.*.transfer_id' => 'required|exists:transfers,id',
            'transfers.*.start_time' => 'nullable|string',
            'transfers.*.end_time' => 'nullable|string',
            'transfers.*.pickup_location' => 'nullable|string',
            'transfers.*.dropoff_location' => 'nullable|string',
            'transfers.*.pax' => 'nullable|integer',
            'transfers.*.price' => 'nullable|numeric',
            'transfers.*.included' => 'boolean',
            'transfers.*.notes' => 'nullable|string',
        ]);

        $creator = Auth::user();

        // Create itinerary with pending status
        $itinerary = Itinerary::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'],
            'featured_itinerary' => $validated['featured_itinerary'] ?? false,
            'private_itinerary' => $validated['private_itinerary'] ?? false,
        ]);

        // Create itinerary_meta with creator_id and pending status
        $itinerary->meta()->create([
            'creator_id' => $creator->id,
            'user_id' => $creator->id,
            'status' => 'pending',
        ]);

        // Create locations
        foreach ($validated['locations'] as $cityId) {
            \App\Models\ItineraryLocation::create([
                'itinerary_id' => $itinerary->id,
                'city_id' => $cityId,
            ]);
        }

        // Create schedules
        $scheduleMap = [];
        foreach ($validated['schedules'] as $scheduleData) {
            $schedule = \App\Models\ItinerarySchedule::create([
                'itinerary_id' => $itinerary->id,
                'day' => $scheduleData['day'],
                'title' => $scheduleData['title'] ?? null,
            ]);
            $scheduleMap[$scheduleData['day']] = $schedule->id;
        }

        // Create activities with day mapping
        if (!empty($validated['activities'])) {
            foreach ($validated['activities'] as $activityData) {
                if (!isset($scheduleMap[$activityData['day']])) {
                    continue;
                }

                \App\Models\ItineraryActivity::create([
                    'schedule_id' => $scheduleMap[$activityData['day']],
                    'activity_id' => $activityData['activity_id'],
                    'start_time' => $activityData['start_time'] ?? null,
                    'end_time' => $activityData['end_time'] ?? null,
                    'price' => $activityData['price'] ?? null,
                    'included' => $activityData['included'] ?? true,
                    'notes' => $activityData['notes'] ?? null,
                ]);
            }
        }

        // Create transfers with day mapping
        if (!empty($validated['transfers'])) {
            foreach ($validated['transfers'] as $transferData) {
                if (!isset($scheduleMap[$transferData['day']])) {
                    continue;
                }

                \App\Models\ItineraryTransfer::create([
                    'schedule_id' => $scheduleMap[$transferData['day']],
                    'transfer_id' => $transferData['transfer_id'],
                    'start_time' => $transferData['start_time'] ?? null,
                    'end_time' => $transferData['end_time'] ?? null,
                    'pickup_location' => $transferData['pickup_location'] ?? null,
                    'dropoff_location' => $transferData['dropoff_location'] ?? null,
                    'pax' => $transferData['pax'] ?? null,
                    'price' => $transferData['price'] ?? null,
                    'included' => $transferData['included'] ?? true,
                    'notes' => $transferData['notes'] ?? null,
                ]);
            }
        }

        // Send email to admin
        try {
            Mail::to(config('mail.admin_address', 'khawla@fanaticcoders.com'))
                ->send(new ItinerarySubmittedAdminMail($itinerary, $creator));
        } catch (\Exception $e) {
            Log::error('Failed to send itinerary submission email', [
                'itinerary_id' => $itinerary->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Itinerary submitted for approval.',
            'data' => $itinerary,
        ], 201);
    }
}
