<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ItineraryApprovedMail;
use App\Mail\ItineraryEditApprovedMail;
use App\Mail\ItineraryEditRejectedMail;
use App\Mail\ItineraryRejectedMail;
use App\Mail\ItineraryRemovalApprovedMail;
use App\Mail\ItineraryRemovalRejectedMail;
use App\Mail\ItineraryTrashedMail;
use App\Models\Itinerary;
use App\Models\Notification;
use App\Services\CreatorItineraryLifecycleService;
use App\Services\ItineraryDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CreatorItineraryManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'view' => ['sometimes', \Illuminate\Validation\Rule::in(['active', 'trash'])],
            'status' => ['sometimes', \Illuminate\Validation\Rule::in(['draft', 'pending', 'approved', 'rejected'])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $view = $validated['view'] ?? 'active';
        $query = ($view === 'trash' ? Itinerary::onlyTrashed() : Itinerary::query())
            ->standaloneCreator()
            ->with(['creator', 'parentItinerary.locations.city', 'locations', 'mediaGallery.media']);

        if ($view === 'active' && isset($validated['status'])) {
            $query->whereHas('meta', fn ($q) => $q->where('status', $validated['status']));
        }

        $itineraries = $query->latest()->paginate(15);
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
            'trash_count' => Itinerary::onlyTrashed()->standaloneCreator()->count(),
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
                'schedules.transfers.transfer.mediaGallery.media',
                'basePricing.variations',
                'basePricing.blackoutDates',
                'inclusionsExclusions',
                'mediaGallery.media',
                'seo',
            ])
            ->find($id);

        if (! $itinerary) {
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

        // Resolve gallery with the same fallback chain the public itinerary page uses:
        // itinerary media → activity media → transfer media. Creator itineraries carry no
        // own itinerary-level media, so without the fallback the review gallery is empty.
        $data['media_gallery'] = $this->resolveGalleryWithFallback($itinerary);

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
                        'include_in_package' => $activity->included,
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
                        'include_in_package' => $transfer->included,
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

        if (! $creatorCopy) {
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

        if (! $original) {
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
        $itinerary = app(CreatorItineraryLifecycleService::class)->publish(
            (int) $id,
            fn (Itinerary $locked) => $this->notifyCreator(
                $locked,
                'itinerary_approved',
                'Itinerary Approved',
                "Your itinerary \"{$locked->name}\" has been approved and is now visible on the Explore page.",
            ),
        );

        $itinerary->load('creator');
        if ($itinerary->creator) {
            try {
                Mail::to($itinerary->creator->email)->send(new ItineraryApprovedMail($itinerary, $itinerary->creator));
            } catch (\Throwable $exception) {
                Log::error('Failed to send itinerary approved email', [
                    'itinerary_id' => $itinerary->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        } else {
            Log::warning('Skipped itinerary approved email because creator is missing', ['itinerary_id' => $itinerary->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Itinerary approved.',
            'data' => $itinerary,
        ]);
    }

    public function updateAndApprove(Request $request, $id): JsonResponse
    {
        $result = DB::transaction(function () use ($request, $id): array {
            $itinerary = Itinerary::standaloneCreator()->lockForUpdate()->find($id);
            if (! $itinerary) {
                return ['response' => response()->json(['success' => false, 'message' => 'Creator itinerary not found'], 404)];
            }
            if ($itinerary->status !== 'pending') {
                return ['response' => response()->json(['success' => false, 'message' => 'Only pending itineraries can be approved.'], 422)];
            }

            $adminController = app(\App\Http\Controllers\Admin\ItineraryController::class);
            $updateResponse = $adminController->update($request, $id);

            if ($updateResponse->getStatusCode() !== 200) {
                return ['response' => $updateResponse];
            }

            $published = app(CreatorItineraryLifecycleService::class)->publish(
                (int) $id,
                fn (Itinerary $locked) => $this->notifyCreator(
                    $locked,
                    'itinerary_approved',
                    'Itinerary Approved',
                    "Your itinerary \"{$locked->name}\" has been approved.",
                ),
            )->load('creator');

            return ['itinerary' => $published];
        });

        if (isset($result['response'])) {
            return $result['response'];
        }

        $itinerary = $result['itinerary'];
        if ($itinerary->creator) {
            try {
                Mail::to($itinerary->creator->email)->send(new ItineraryApprovedMail($itinerary, $itinerary->creator));
            } catch (\Throwable $exception) {
                Log::error('Failed to send itinerary approved email', [
                    'itinerary_id' => $itinerary->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Itinerary updated and approved.',
            'data' => $itinerary,
        ]);
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $itinerary = app(CreatorItineraryLifecycleService::class)->rejectPublication(
            (int) $id,
            $validated['reason'] ?? null,
            fn (Itinerary $locked) => $this->notifyCreator(
                $locked,
                'itinerary_rejected',
                'Itinerary Rejected',
                "Your itinerary \"{$locked->name}\" was not approved.",
            ),
        );

        $itinerary->load('creator');
        if ($itinerary->creator) {
            try {
                Mail::to($itinerary->creator->email)->send(new ItineraryRejectedMail($itinerary, $itinerary->creator));
            } catch (\Throwable $exception) {
                Log::error('Failed to send itinerary rejected email', [
                    'itinerary_id' => $itinerary->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        } else {
            Log::warning('Skipped itinerary rejected email because creator is missing', ['itinerary_id' => $itinerary->id]);
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
        if (! $itinerary) {
            return response()->json(['success' => false, 'message' => 'Creator itinerary not found'], 404);
        }

        $adminController = app(\App\Http\Controllers\Admin\ItineraryController::class);

        return $adminController->update($request, $id);
    }

    public function destroy($id): JsonResponse
    {
        $itinerary = app(CreatorItineraryLifecycleService::class)->trash(
            (int) $id,
            function (Itinerary $locked): void {
                $purgeAt = $locked->deleted_at->copy()->addDays(30);
                $this->notifyCreator(
                    $locked,
                    'itinerary_trashed',
                    'Itinerary Moved to Trash',
                    "Your itinerary \"{$locked->name}\" was moved to Trash. It will be permanently removed on {$purgeAt->toFormattedDateString()} unless restored.",
                    [
                        'deleted_at' => $locked->deleted_at->toIso8601String(),
                        'purge_at' => $purgeAt->toIso8601String(),
                    ],
                );
            },
        );
        $itinerary->load('creator');

        if ($itinerary->creator) {
            try {
                Mail::to($itinerary->creator->email)->send(new ItineraryTrashedMail(
                    $itinerary,
                    $itinerary->creator,
                    $itinerary->deleted_at->copy()->addDays(30),
                ));
            } catch (\Throwable $exception) {
                Log::error('Failed to send itinerary trashed email', [
                    'itinerary_id' => $itinerary->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        } else {
            Log::warning('Skipped itinerary trashed email because creator is missing', ['itinerary_id' => $itinerary->id]);
        }

        return response()->json(['success' => true, 'message' => 'Itinerary moved to Trash.']);
    }

    public function restore($id): JsonResponse
    {
        $itinerary = app(CreatorItineraryLifecycleService::class)->restoreToDraft((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Itinerary restored to Draft.',
            'data' => $itinerary,
        ]);
    }

    public function publish($id): JsonResponse
    {
        $itinerary = app(CreatorItineraryLifecycleService::class)->publish(
            (int) $id,
            fn (Itinerary $locked) => $this->notifyCreator(
                $locked,
                'itinerary_approved',
                'Itinerary Approved',
                "Your itinerary \"{$locked->name}\" has been approved and is now public.",
            ),
        );
        $itinerary->load('creator');

        if ($itinerary->creator) {
            try {
                Mail::to($itinerary->creator->email)->send(new ItineraryApprovedMail($itinerary, $itinerary->creator));
            } catch (\Throwable $exception) {
                Log::error('Failed to send itinerary approved email', [
                    'itinerary_id' => $itinerary->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        } else {
            Log::warning('Skipped itinerary approved email because creator is missing', ['itinerary_id' => $itinerary->id]);
        }

        return response()->json(['success' => true, 'message' => 'Itinerary published.', 'data' => $itinerary]);
    }

    public function forceDestroy($id): JsonResponse
    {
        if (! app(CreatorItineraryLifecycleService::class)->forceDelete((int) $id)) {
            return response()->json(['success' => false, 'message' => 'Trashed creator itinerary not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Itinerary permanently deleted.']);
    }

    public function approveEdit($id): JsonResponse
    {
        $itinerary = Itinerary::creatorCopies()->find($id);
        if (! $itinerary || ! $itinerary->draft_itinerary_id) {
            return response()->json(['success' => false, 'message' => 'No pending edit found for this itinerary.'], 404);
        }

        $draft = Itinerary::find($itinerary->draft_itinerary_id);
        if (! $draft || $draft->status !== 'edit_pending') {
            return response()->json(['success' => false, 'message' => 'Draft is not pending approval.'], 422);
        }

        $service = new ItineraryDraftService;
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
        if (! $itinerary || ! $itinerary->draft_itinerary_id) {
            return response()->json(['success' => false, 'message' => 'No pending edit found for this itinerary.'], 404);
        }

        $draft = Itinerary::find($itinerary->draft_itinerary_id);
        if (! $draft || $draft->status !== 'edit_pending') {
            return response()->json(['success' => false, 'message' => 'Draft is not pending approval.'], 422);
        }

        $service = new ItineraryDraftService;
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
        $itinerary = app(CreatorItineraryLifecycleService::class)->trash(
            (int) $id,
            fn (Itinerary $locked) => $this->notifyCreator(
                $locked,
                'itinerary_removal_approved',
                'Itinerary Removed',
                "Your itinerary \"{$locked->name}\" has been removed.",
            ),
            requireRemovalRequest: true,
        );

        $itinerary->load('creator');
        if ($itinerary->creator) {
            try {
                Mail::to($itinerary->creator->email)->send(new ItineraryRemovalApprovedMail($itinerary, $itinerary->creator));
            } catch (\Throwable $exception) {
                Log::error('Failed to send removal approved email', [
                    'itinerary_id' => $itinerary->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Removal approved.']);
    }

    public function rejectRemoval($id): JsonResponse
    {
        $itinerary = app(CreatorItineraryLifecycleService::class)->rejectRemoval(
            (int) $id,
            fn (Itinerary $locked) => $this->notifyCreator(
                $locked,
                'itinerary_removal_rejected',
                'Removal Request Declined',
                "Your removal request for \"{$locked->name}\" was declined.",
            ),
        );

        $itinerary->load('creator');
        if ($itinerary->creator) {
            try {
                Mail::to($itinerary->creator->email)->send(new ItineraryRemovalRejectedMail($itinerary, $itinerary->creator));
            } catch (\Throwable $exception) {
                Log::error('Failed to send removal rejected email', [
                    'itinerary_id' => $itinerary->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Removal rejected.']);
    }

    private function notifyCreator(
        Itinerary $itinerary,
        string $type,
        string $title,
        string $message,
        array $data = [],
    ): void {
        if (! $itinerary->creator_id) {
            Log::warning('Skipped creator notification because creator is missing', [
                'itinerary_id' => $itinerary->id,
                'notification_type' => $type,
            ]);

            return;
        }

        Notification::create([
            'user_id' => $itinerary->creator_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => ['itinerary_id' => $itinerary->id, ...$data],
        ]);
    }

    /**
     * Resolve the media_gallery for the admin review/preview of a creator itinerary.
     * Mirrors PublicItineraryController::resolveGalleryWithFallback so the review page
     * shows the same images the public page will once approved.
     * Fallback chain: itinerary media → activity media → transfer media. Dedupes by URL.
     */
    private function resolveGalleryWithFallback(Itinerary $itinerary): array
    {
        $ownGallery = $itinerary->mediaGallery
            ->filter(fn ($mg) => $mg->media?->url)
            ->map(fn ($mg) => [
                'id' => $mg->media->id,
                'name' => $mg->media->name,
                'alt_text' => $mg->media->alt_text,
                'url' => $mg->media->url,
                'is_featured' => (bool) $mg->is_featured,
            ])
            ->values()
            ->toArray();

        if (! empty($ownGallery)) {
            return $ownGallery;
        }

        $collectFrom = function ($items) {
            $seen = [];
            $out = [];
            foreach ($items as $mg) {
                $media = $mg->media ?? null;
                if (! $media?->url || in_array($media->url, $seen, true)) {
                    continue;
                }
                $seen[] = $media->url;
                $out[] = [
                    'id' => $media->id,
                    'name' => $media->name,
                    'alt_text' => $media->alt_text,
                    'url' => $media->url,
                    'is_featured' => false,
                ];
            }

            return $out;
        };

        $activityMedia = $itinerary->schedules->flatMap(
            fn ($schedule) => $schedule->activities->flatMap(
                fn ($activity) => $activity->activity?->mediaGallery ?? collect()
            )
        );
        $activityGallery = $collectFrom($activityMedia);
        if (! empty($activityGallery)) {
            return $activityGallery;
        }

        $transferMedia = $itinerary->schedules->flatMap(
            fn ($schedule) => $schedule->transfers->flatMap(
                fn ($transfer) => $transfer->transfer?->mediaGallery ?? collect()
            )
        );

        return $collectFrom($transferMedia);
    }
}
