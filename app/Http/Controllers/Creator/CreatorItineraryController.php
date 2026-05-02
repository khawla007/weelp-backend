<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Mail\ItineraryEditSubmittedMail;
use App\Mail\ItineraryRemovalRequestedMail;
use App\Mail\ItinerarySubmittedAdminMail;
use App\Models\Itinerary;
use App\Services\ItineraryDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreatorItineraryController extends Controller
{
    public function myItineraries(): JsonResponse
    {
        $itineraries = Itinerary::whereHas('meta', fn ($q) => $q->where('creator_id', Auth::id()))
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
        $draft = Itinerary::whereHas('meta', fn ($q) => $q->where('creator_id', Auth::id())
            ->whereIn('status', ['draft', 'edit_pending']))
            ->with([
                'locations.city',
                'schedules.activities.activity.mediaGallery.media',
                'schedules.activities.activity.locations.city',
                'schedules.transfers.transfer.mediaGallery.media',
            ])
            ->find($id);

        if (! $draft) {
            return response()->json(['success' => false, 'message' => 'Draft not found.'], 404);
        }

        $shapeMediaGallery = function ($model) {
            return collect($model?->mediaGallery ?? [])->map(function ($m) {
                return [
                    'id' => $m->id,
                    'media_id' => $m->media_id,
                    'name' => $m->media->name ?? null,
                    'alt_text' => $m->media->alt_text ?? null,
                    'url' => $m->media->url ?? null,
                    'is_featured' => $m->is_featured ?? false,
                ];
            })->values();
        };

        $data = $draft->toArray();
        $data['schedules'] = $draft->schedules->map(function ($schedule) use ($shapeMediaGallery) {
            return [
                'day' => $schedule->day,
                'title' => $schedule->title,
                'activities' => $schedule->activities->map(function ($activity) use ($shapeMediaGallery) {
                    $activityModel = $activity->activity;
                    $activitydata = $activityModel ? [
                        'id' => $activityModel->id,
                        'name' => $activityModel->name,
                        'slug' => $activityModel->slug,
                        'media_gallery' => $shapeMediaGallery($activityModel),
                    ] : null;

                    return [
                        'id' => $activity->id,
                        'activity_id' => $activity->activity_id,
                        'name' => $activityModel?->name,
                        'start_time' => $activity->start_time,
                        'end_time' => $activity->end_time,
                        'notes' => $activity->notes,
                        'price' => $activity->price,
                        'included' => $activity->included,
                        'activitydata' => $activitydata,
                    ];
                }),
                'transfers' => $schedule->transfers->map(function ($transfer) use ($shapeMediaGallery) {
                    $transferModel = $transfer->transfer;
                    $transferData = $transferModel ? [
                        'id' => $transferModel->id,
                        'name' => $transferModel->name,
                        'slug' => $transferModel->slug,
                        'media_gallery' => $shapeMediaGallery($transferModel),
                    ] : null;

                    return [
                        'id' => $transfer->id,
                        'transfer_id' => $transfer->transfer_id,
                        'name' => $transferModel?->name,
                        'start_time' => $transfer->start_time,
                        'end_time' => $transfer->end_time,
                        'pickup_location' => $transfer->pickup_location,
                        'dropoff_location' => $transfer->dropoff_location,
                        'pax' => $transfer->pax,
                        'bag_count' => (int) ($transfer->bag_count ?? 0),
                        'waiting_minutes' => (int) ($transfer->waiting_minutes ?? 0),
                        'price' => $transfer->price,
                        'included' => $transfer->included,
                        'transferData' => $transferData,
                    ];
                }),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function requestEdit($id): JsonResponse
    {
        $itinerary = Itinerary::whereHas('meta', fn ($q) => $q->where('creator_id', Auth::id()))
            ->approved()
            ->find($id);

        if (! $itinerary) {
            return response()->json(['success' => false, 'message' => 'Approved itinerary not found.'], 404);
        }

        if ($itinerary->draft_itinerary_id) {
            return response()->json(['success' => false, 'message' => 'An edit draft already exists for this itinerary.'], 422);
        }

        if ($itinerary->removal_status === 'requested') {
            return response()->json(['success' => false, 'message' => 'Cannot edit while a removal request is pending.'], 422);
        }

        $service = new ItineraryDraftService;
        $draft = $service->createDraft($itinerary);

        return response()->json(['success' => true, 'message' => 'Edit draft created.', 'data' => $draft], 201);
    }

    public function updateDraft(Request $request, $id): JsonResponse
    {
        $draft = Itinerary::whereHas('meta', fn ($q) => $q->where('creator_id', Auth::id()))
            ->draft()
            ->find($id);

        if (! $draft) {
            return response()->json(['success' => false, 'message' => 'Draft not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:5000',
            'locations' => 'nullable|array',
            'locations.*' => 'integer|exists:cities,id',
            'schedules' => 'nullable|array|required_with:activities,transfers',
            'schedules.*.day' => 'required|integer',
            'schedules.*.title' => 'nullable|string|max:255',
            'activities' => 'nullable|array',
            'activities.*.day' => 'required|integer',
            'activities.*.activity_id' => 'required|exists:activities,id',
            'activities.*.start_time' => 'nullable|string|max:8',
            'activities.*.end_time' => 'nullable|string|max:8',
            'activities.*.price' => 'nullable|numeric',
            'activities.*.included' => 'boolean',
            'activities.*.notes' => 'nullable|string|max:5000',
            'transfers' => 'nullable|array',
            'transfers.*.day' => 'required|integer',
            'transfers.*.transfer_id' => 'required|exists:transfers,id',
            'transfers.*.start_time' => 'nullable|string|max:8',
            'transfers.*.end_time' => 'nullable|string|max:8',
            'transfers.*.pickup_location' => 'nullable|string|max:255',
            'transfers.*.dropoff_location' => 'nullable|string|max:255',
            'transfers.*.pax' => 'nullable|integer|min:1',
            'transfers.*.bag_count' => 'nullable|integer|min:0',
            'transfers.*.waiting_minutes' => 'nullable|integer|min:0',
            'transfers.*.price' => 'nullable|numeric',
            'transfers.*.included' => 'boolean',
            'transfers.*.notes' => 'nullable|string|max:5000',
        ]);

        return DB::transaction(function () use ($validated, $draft) {
            if (array_key_exists('name', $validated)) {
                $name = $validated['name'];
                $baseSlug = $validated['slug'] ?? Str::slug($name);
                $slug = $baseSlug;

                while (Itinerary::where('slug', $slug)->where('id', '!=', $draft->id)->exists()) {
                    $slug = $baseSlug.'-'.Str::random(4);
                }

                $draft->update([
                    'name' => $name,
                    'slug' => $slug,
                ]);
            }

            if (array_key_exists('description', $validated)) {
                $draft->update(['description' => $validated['description']]);
            }

            if (array_key_exists('locations', $validated)) {
                \App\Models\ItineraryLocation::where('itinerary_id', $draft->id)->delete();
                foreach ($validated['locations'] as $cityId) {
                    \App\Models\ItineraryLocation::create([
                        'itinerary_id' => $draft->id,
                        'city_id' => $cityId,
                    ]);
                }
            }

            if (array_key_exists('schedules', $validated)) {
                $draft->schedules()->each(function ($schedule) {
                    $schedule->activities()->delete();
                    $schedule->transfers()->delete();
                    $schedule->delete();
                });

                $scheduleMap = [];
                foreach ($validated['schedules'] as $scheduleData) {
                    $schedule = \App\Models\ItinerarySchedule::create([
                        'itinerary_id' => $draft->id,
                        'day' => $scheduleData['day'],
                        'title' => $scheduleData['title'] ?? null,
                    ]);
                    $scheduleMap[$scheduleData['day']] = $schedule->id;
                }

                if (! empty($validated['activities'])) {
                    foreach ($validated['activities'] as $activityData) {
                        if (! isset($scheduleMap[$activityData['day']])) {
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

                if (! empty($validated['transfers'])) {
                    foreach ($validated['transfers'] as $transferData) {
                        if (! isset($scheduleMap[$transferData['day']])) {
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
                            'bag_count' => $transferData['bag_count'] ?? 0,
                            'waiting_minutes' => $transferData['waiting_minutes'] ?? 0,
                            'price' => $transferData['price'] ?? null,
                            'included' => $transferData['included'] ?? true,
                            'notes' => $transferData['notes'] ?? null,
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Draft updated.',
                'data' => $draft->fresh(['locations.city', 'schedules.activities', 'schedules.transfers']),
            ]);
        });
    }

    public function submitDraft($id): JsonResponse
    {
        $draft = Itinerary::whereHas('meta', fn ($q) => $q->where('creator_id', Auth::id()))
            ->draft()
            ->find($id);

        if (! $draft) {
            return response()->json(['success' => false, 'message' => 'Draft not found.'], 404);
        }

        $draft->update(['status' => 'edit_pending']);

        $approved = Itinerary::whereHas('meta', fn ($q) => $q->where('draft_itinerary_id', $draft->id))->first();

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
        $itinerary = Itinerary::whereHas('meta', fn ($q) => $q->where('creator_id', Auth::id()))
            ->approved()
            ->find($id);

        if (! $itinerary) {
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
     */
    public function createDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:itineraries,slug',
            'description' => 'required|string|max:5000',
            'locations' => 'required|array|min:1',
            'locations.*' => 'exists:cities,id',
            'featured_itinerary' => 'nullable|boolean',
            'private_itinerary' => 'nullable|boolean',
            'schedules' => 'required|array|min:1',
            'schedules.*.day' => 'required|integer',
            'schedules.*.title' => 'nullable|string|max:255',
            'activities' => 'array',
            'activities.*.day' => 'required|integer',
            'activities.*.activity_id' => 'required|exists:activities,id',
            'activities.*.start_time' => 'nullable|string|max:8',
            'activities.*.end_time' => 'nullable|string|max:8',
            'activities.*.price' => 'nullable|numeric',
            'activities.*.included' => 'boolean',
            'activities.*.notes' => 'nullable|string|max:5000',
            'transfers' => 'array',
            'transfers.*.day' => 'required|integer',
            'transfers.*.transfer_id' => 'required|exists:transfers,id',
            'transfers.*.start_time' => 'nullable|string|max:8',
            'transfers.*.end_time' => 'nullable|string|max:8',
            'transfers.*.pickup_location' => 'nullable|string|max:255',
            'transfers.*.dropoff_location' => 'nullable|string|max:255',
            'transfers.*.pax' => 'nullable|integer|min:1',
            'transfers.*.bag_count' => 'nullable|integer|min:0',
            'transfers.*.waiting_minutes' => 'nullable|integer|min:0',
            'transfers.*.price' => 'nullable|numeric',
            'transfers.*.included' => 'boolean',
            'transfers.*.notes' => 'nullable|string|max:5000',
        ]);

        $creator = Auth::user();

        return DB::transaction(function () use ($validated, $creator) {
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
            if (! empty($validated['activities'])) {
                foreach ($validated['activities'] as $activityData) {
                    if (! isset($scheduleMap[$activityData['day']])) {
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
            if (! empty($validated['transfers'])) {
                foreach ($validated['transfers'] as $transferData) {
                    if (! isset($scheduleMap[$transferData['day']])) {
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
                        'bag_count' => $transferData['bag_count'] ?? 0,
                        'waiting_minutes' => $transferData['waiting_minutes'] ?? 0,
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
        });
    }

    // ==================== EXPLORE METHODS (merged from ExploreCreatorItineraryController) ====================

    public function exploreIndex(Request $request): JsonResponse
    {
        $query = \App\Models\Itinerary::creatorCopies()->approved()
            ->with([
                'creator:users.id,users.name',
                'creator.profile:id,user_id,avatar',
                'locations:id,itinerary_id,city_id',
                'locations.city:id,name',
                'mediaGallery.media',
                'basePricing.variations' => fn ($q) => $q->limit(1),
                'schedules' => fn ($q) => $q->orderBy('day'),
                'schedules.activities',
                'schedules.transfers.transfer.route',
                'schedules.transfers.transfer.pricingAvailability',
            ]);

        if ($request->query('source') === 'mine') {
            $user = Auth::guard('api')->user();
            if ($user) {
                $query->whereHas('meta', fn ($q) => $q->where('creator_id', $user->id));
            } else {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                ]);
            }
        }

        switch ($request->query('sort', 'latest')) {
            case 'oldest':
                $query->orderBy('itineraries.created_at', 'asc');
                break;
            case 'top_rated':
                $query->join('itinerary_meta', 'itineraries.id', '=', 'itinerary_meta.itinerary_id')
                    ->orderBy('itinerary_meta.likes_count', 'desc')
                    ->orderBy('itineraries.created_at', 'desc')
                    ->select('itineraries.*');
                break;
            case 'most_viewed':
                $query->join('itinerary_meta', 'itineraries.id', '=', 'itinerary_meta.itinerary_id')
                    ->orderBy('itinerary_meta.views_count', 'desc')
                    ->orderBy('itineraries.created_at', 'desc')
                    ->select('itineraries.*');
                break;
            default:
                $query->orderBy('itineraries.created_at', 'desc');
                break;
        }

        $paginated = $query->paginate(15);
        $userId = Auth::guard('api')->id();

        $likedIds = $userId
            ? \App\Models\ItineraryLike::where('user_id', $userId)
                ->whereIn('itinerary_id', $paginated->pluck('id'))
                ->pluck('itinerary_id')
                ->toArray()
            : [];

        $collection = $paginated->getCollection()->map(function (\App\Models\Itinerary $itinerary) use ($likedIds) {
            return [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'description' => $itinerary->description,
                'creator' => $itinerary->creator,
                'locations' => $itinerary->locations,
                'is_liked' => in_array($itinerary->id, $likedIds),
                'day_count' => $itinerary->schedules->count(),
                'featured_image' => $itinerary->featured_image,
                'display_price' => $itinerary->schedule_total_price,
                'display_currency' => $itinerary->schedule_total_currency,
                'currency' => $itinerary->basePricing?->currency,
                'likes_count' => $itinerary->likes_count,
                'views_count' => $itinerary->views_count,
                'created_at' => $itinerary->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $collection,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function exploreShow(int $id): JsonResponse
    {
        $itinerary = \App\Models\Itinerary::creatorCopies()->approved()
            ->with([
                'creator:users.id,users.name',
                'creator.profile:id,user_id,avatar',
                'locations.city',
                'schedules.activities.activity',
                'schedules.transfers.transfer',
                'mediaGallery.media',
                'basePricing.variations',
                'inclusionsExclusions',
                'categories.category',
                'tags.tag',
            ])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $itinerary]);
    }

    public function toggleLike(int $id): JsonResponse
    {
        $user = Auth::guard('api')->user();
        $itinerary = \App\Models\Itinerary::creatorCopies()->approved()->findOrFail($id);

        $existing = \App\Models\ItineraryLike::where('itinerary_id', $itinerary->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $itinerary->meta?->decrement('likes_count');

            return response()->json([
                'success' => true,
                'liked' => false,
                'likes_count' => $itinerary->fresh()->likes_count,
            ]);
        }

        \App\Models\ItineraryLike::create([
            'itinerary_id' => $itinerary->id,
            'user_id' => $user->id,
        ]);
        $itinerary->meta?->increment('likes_count');

        return response()->json([
            'success' => true,
            'liked' => true,
            'likes_count' => $itinerary->fresh()->likes_count,
        ]);
    }

    public function recordView(int $id): JsonResponse
    {
        $itinerary = \App\Models\Itinerary::creatorCopies()->approved()->findOrFail($id);
        $itinerary->meta?->increment('views_count');

        return response()->json([
            'success' => true,
            'views_count' => $itinerary->fresh()->views_count,
        ]);
    }

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
        // Accept either city_id (single) or city_ids (comma-separated list)
        $cityIds = $request->get('city_ids');
        $cityId = $request->get('city_id');

        $cityIdArray = [];
        if ($cityIds) {
            $cityIdArray = array_filter(array_map('intval', explode(',', $cityIds)));
        } elseif ($cityId) {
            $cityIdArray = [(int) $cityId];
        }

        $query = \App\Models\Activity::query()
            ->with(['tags.tag', 'mediaGallery.media', 'locations.city', 'locations.place'])
            ->select('id', 'name', 'slug', 'description', 'item_type', 'featured_activity');

        if (! empty($cityIdArray)) {
            $query->whereHas('locations', function ($q) use ($cityIdArray) {
                $q->whereIn('city_id', $cityIdArray);
            });
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $activities = $query->get()->map(function ($activity) {
            $primaryLocation = $activity->locations->where('location_type', 'primary')->first()
                ?? $activity->locations->first();

            $mediaGallery = collect($activity->mediaGallery)->map(function ($m) {
                return [
                    'id' => $m->id,
                    'media_id' => $m->media_id,
                    'name' => $m->media->name ?? null,
                    'alt_text' => $m->media->alt_text ?? null,
                    'url' => $m->media->url ?? null,
                    'is_featured' => $m->is_featured ?? false,
                ];
            });

            return [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'city_name' => $primaryLocation?->city?->name,
                'place_name' => $primaryLocation?->place?->name,
                'duration_minutes' => $primaryLocation?->duration,
                'type' => $activity->item_type,
                'featured_image' => $mediaGallery->firstWhere('is_featured', true)['url'] ?? $mediaGallery->first()['url'] ?? null,
                'media_gallery' => $mediaGallery->values(),
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
        // Accept optional city_id (single) or city_ids (comma-separated list)
        $cityIds = $request->get('city_ids');
        $cityId = $request->get('city_id');

        $cityIdArray = [];
        if ($cityIds) {
            $cityIdArray = array_filter(array_map('intval', explode(',', $cityIds)));
        } elseif ($cityId) {
            $cityIdArray = [(int) $cityId];
        }

        $query = \App\Models\Transfer::query()
            ->select('id', 'name', 'slug', 'transfer_type', 'description')
            ->with([
                'vendorRoutes.pickupPlace.city',
                'vendorRoutes.dropoffPlace.city',
                'mediaGallery.media',
            ]);

        if (! empty($cityIdArray)) {
            $placeIds = \App\Models\Place::whereIn('city_id', $cityIdArray)->pluck('id');
            $query->whereHas('vendorRoutes', function ($q) use ($placeIds) {
                $q->whereIn('pickup_place_id', $placeIds);
            });
        }

        $transfers = $query->get()->map(function ($transfer) {
            $route = $transfer->vendorRoutes;

            $mediaGallery = collect($transfer->mediaGallery)->map(function ($m) {
                return [
                    'id' => $m->id,
                    'media_id' => $m->media_id,
                    'name' => $m->media->name ?? null,
                    'alt_text' => $m->media->alt_text ?? null,
                    'url' => $m->media->url ?? null,
                    'is_featured' => $m->is_featured ?? false,
                ];
            });

            return [
                'id' => $transfer->id,
                'name' => $transfer->name,
                'slug' => $transfer->slug,
                'vehicle_type' => $route?->vehicle_type,
                'featured_image' => $mediaGallery->firstWhere('is_featured', true)['url'] ?? $mediaGallery->first()['url'] ?? null,
                'media_gallery' => $mediaGallery->values(),
                'pickup_city_name' => $route?->pickupPlace?->city?->name,
                'dropoff_city_name' => $route?->dropoffPlace?->city?->name,
                'vendor_routes' => [
                    'pickup_place_id' => $route?->pickup_place_id,
                    'dropoff_place_id' => $route?->dropoff_place_id,
                    'pickup_city_id' => $route?->pickupPlace?->city_id,
                    'dropoff_city_id' => $route?->dropoffPlace?->city_id,
                ],
            ];
        });

        return response()->json(['success' => true, 'data' => $transfers]);
    }

    public function getEditData(string $slug): JsonResponse
    {
        $itinerary = \App\Models\Itinerary::with([
            'locations',
            'schedules.activities',
            'schedules.transfers',
            'basePricing',
            'inclusionsExclusions',
            'mediaGallery',
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
}
