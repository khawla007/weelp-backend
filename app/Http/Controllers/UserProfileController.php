<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Package;
use App\Models\Review;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\UserProfile;
use App\Support\MediaStorage;
use App\Support\UploadRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;  // Import UserMeta model
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class UserProfileController extends Controller
{
    /**
     * Handle the get user profile request.
     */
    public function show(Request $request)
    {
        $user = User::with(['profile.urls', 'meta'])->find($request->user()->id);

        if (! $user) {
            return response()->json(['error' => 'Profile not found'], 404);
        }

        // return response()->json($profile);
        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Handle the insert/update user profile request.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'avatar' => 'nullable|url',
            'name' => 'nullable|string|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|string|max:50|in:male,female,other',
            'username' => 'nullable|string|max:255',
            'interest' => 'nullable|string|max:5000',
            'bio' => 'nullable|string|max:5000',

            // URLs validation
            'urls' => 'nullable|array',
            'urls.*.label' => 'nullable|string|max:255',
            'urls.*.url' => 'nullable|url',
        ]);

        $user = $request->user();
        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);

        // Update user name if provided
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
            $user->save();
        }

        $profile->fill($validated);
        $profile->save();

        $userMeta = UserMeta::firstOrNew(['user_id' => $user->id]);

        if (isset($validated['username'])) {
            $userMeta->username = $validated['username'];
        }
        if (isset($validated['interest'])) {
            $userMeta->interest = $validated['interest'];
        }
        if (isset($validated['bio'])) {
            $userMeta->bio = $validated['bio'];
        }

        $userMeta->save();

        if ($request->has('urls')) {
            $incomingUrls = $validated['urls'];
            $existingUrls = $profile->urls()->orderBy('id')->get();

            $existingCount = $existingUrls->count();
            $incomingCount = count($incomingUrls);

            foreach ($incomingUrls as $index => $urlData) {
                if ($index < $existingCount) {
                    // Update existing URL
                    $existingUrls[$index]->update([
                        'label' => $urlData['label'] ?? $existingUrls[$index]->label,
                        'url' => $urlData['url'] ?? $existingUrls[$index]->url,
                    ]);
                } else {
                    // Create new URL entry
                    $profile->urls()->create($urlData);
                }
            }

            // If there are extra existing URLs beyond the incoming data, delete them
            if ($existingCount > $incomingCount) {
                for ($i = $incomingCount; $i < $existingCount; $i++) {
                    $existingUrls[$i]->delete();
                }
            }
        }

        return response()->json([
            'success' => true,
            'profile' => $profile->load('urls'),
            'user_meta' => $userMeta,
        ]);
    }

    /**
     * Handle avatar upload with optimization.
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'file' => array_merge(['required'], UploadRules::image(2048)),
        ]);

        try {
            $avatarService = new \App\Services\AvatarService;
            $url = $avatarService->upload($request->user(), $request->file('file'));

            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteAvatar(Request $request)
    {
        $avatarService = new \App\Services\AvatarService;
        $avatarService->delete($request->user());

        return response()->json(['success' => true]);
    }

    /**
     * Handle password change request.
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string|max:128',
            'password' => 'required|string|max:128|min:8|confirmed',
        ]);

        $user = $request->user();

        // Verify current password
        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'error' => 'Current password is incorrect',
            ], 401);
        }

        // Update password
        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    public function getUserOrders(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('profile');

        $pagination = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $perPage = min((int) ($pagination['per_page'] ?? 6), 50);
        $page = (int) ($pagination['page'] ?? 1);

        $orders = $this->customerOrderQuery($user->id)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => true,
                'orders' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => 1,
                ],
            ]);
        }

        $reviews = $this->reviewsForOrders($orders->getCollection(), $user->id);
        $linkedReviews = $reviews->whereNotNull('order_id')->groupBy('order_id');
        $legacyReviews = $reviews->whereNull('order_id')->groupBy(
            fn (Review $review): string => $this->reviewItemKey($review->item_type, $review->item_id)
        );

        $transformed = $orders->map(function (Order $order) use ($user, $linkedReviews, $legacyReviews): array {
            $review = $linkedReviews->get($order->id)?->first()
                ?? $legacyReviews->get($this->reviewItemKey($order->orderable_type, $order->orderable_id))?->first();

            return $this->transformCustomerOrder($order, $user, $review);
        });

        return response()->json([
            'success' => true,
            'orders' => $transformed->values(),
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function getUserOrder(Request $request, int $order): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('profile');

        $customerOrder = $this->customerOrderQuery($user->id)->findOrFail($order);
        $review = Review::with('mediaGallery.media')
            ->where('user_id', $user->id)
            ->where('order_id', $customerOrder->id)
            ->latest()
            ->first();

        if (! $review) {
            $review = Review::with('mediaGallery.media')
                ->where('user_id', $user->id)
                ->whereNull('order_id')
                ->whereIn('item_type', $this->itemTypeDatabaseValues($customerOrder->orderable_type))
                ->where('item_id', $customerOrder->orderable_id)
                ->latest()
                ->first();
        }

        return response()->json([
            'success' => true,
            'order' => $this->transformCustomerOrder($customerOrder, $user, $review),
        ]);
    }

    private function customerOrderQuery(int $userId): Builder
    {
        return Order::with($this->customerOrderRelations())->where('user_id', $userId);
    }

    private function customerOrderRelations(): array
    {
        $orderableRelations = [
            'locations.city.state.country.regions',
            'mediaGallery.media',
        ];

        return [
            'payment',
            'emergencyContact',
            'orderable' => function (MorphTo $morphTo) use ($orderableRelations): void {
                $morphTo->morphWith([
                    Activity::class => $orderableRelations,
                    Package::class => $orderableRelations,
                    Itinerary::class => $orderableRelations,
                ]);
            },
        ];
    }

    private function reviewsForOrders(Collection $orders, int $userId): Collection
    {
        if ($orders->isEmpty()) {
            return collect();
        }

        return Review::with('mediaGallery.media')
            ->where('user_id', $userId)
            ->where(function (Builder $query) use ($orders): void {
                $query->whereIn('order_id', $orders->pluck('id'))
                    ->orWhere(function (Builder $legacyQuery) use ($orders): void {
                        $legacyQuery->whereNull('order_id')
                            ->whereIn('item_id', $orders->pluck('orderable_id'));
                    });
            })
            ->latest()
            ->get();
    }

    private function transformCustomerOrder(Order $order, User $user, ?Review $review): array
    {
        $snapshot = $this->decodeOrderSnapshot($order->item_snapshot_json);
        $orderable = $order->orderable;
        $snapshotLocations = $snapshot['location'] ?? $snapshot['locations'] ?? null;
        $locations = $this->transformSnapshotLocations($snapshotLocations);
        if ($locations->isEmpty()) {
            $locations = $this->liveOrderLocations($orderable);
        }

        $media = $this->transformSnapshotMedia($snapshot['media'] ?? null);
        if ($media->isEmpty()) {
            $media = $this->liveOrderMedia($orderable);
        }

        $liveCity = $this->preferredLiveLocation($orderable)?->city;

        return [
            'id' => $order->id,
            'item_id' => $order->orderable_id,
            'created_at' => $order->created_at,
            'status' => $order->status,
            'travel_date' => $order->travel_date,
            'preferred_time' => $order->preferred_time,
            'number_of_adults' => $order->number_of_adults,
            'number_of_children' => $order->number_of_children,
            'special_requirements' => $order->special_requirements,
            'payment' => $this->transformPayment($order->payment),
            'emergency_contact' => $order->emergencyContact,
            'item' => [
                'name' => $this->snapshotValue($snapshot, 'name') ?? $orderable?->name,
                'has_live_item' => $orderable !== null,
                'slug' => $this->snapshotValue($snapshot, 'slug') ?? $orderable?->slug,
                'item_type' => $this->snapshotValue($snapshot, 'item_type')
                    ?? $orderable?->item_type
                    ?? $this->normalizeItemType($order->orderable_type),
                'city' => $locations->first()['city'] ?? $liveCity?->name,
                'city_slug' => $locations->first()['city_slug'] ?? $liveCity?->slug,
                'region' => $snapshot['region']
                    ?? ($locations->first()['region'] ?? null)
                    ?? $liveCity?->state?->country?->regions?->first()?->name,
                'locations' => $locations,
                'media' => $media,
            ],
            'review' => $this->transformReview($review),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->profile?->phone,
            ],
        ];
    }

    private function decodeOrderSnapshot(mixed $snapshot): array
    {
        if (is_array($snapshot)) {
            return $snapshot;
        }

        $decoded = json_decode((string) $snapshot, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function snapshotValue(array $snapshot, string $key): mixed
    {
        $value = $snapshot[$key] ?? null;

        return $value === '' ? null : $value;
    }

    private function liveOrderLocations(?Model $orderable): Collection
    {
        return collect($orderable?->locations ?? [])
            ->sortBy(fn ($location): int => strtolower((string) $location->location_type) === 'primary' ? 0 : 1)
            ->map(fn ($location): array => [
                'location_type' => $location->location_type ?? null,
                'city' => $location->city?->name,
                'city_slug' => $location->city?->slug,
                'state' => $location->city?->state?->name,
                'country' => $location->city?->state?->country?->name,
                'region' => $location->city?->state?->country?->regions?->first()?->name,
            ])->values();
    }

    private function preferredLiveLocation(?Model $orderable): ?Model
    {
        $locations = collect($orderable?->locations ?? []);

        return $locations->first(
            fn ($location): bool => strtolower((string) $location->location_type) === 'primary'
        ) ?? $locations->first();
    }

    private function transformSnapshotLocations(mixed $locations): Collection
    {
        return collect(is_array($locations) ? $locations : [])
            ->filter(fn (mixed $location): bool => is_array($location))
            ->map(fn (array $location): array => [
                'location_type' => $location['location_type'] ?? null,
                'city' => $location['city'] ?? null,
                'city_slug' => $location['city_slug'] ?? null,
                'state' => $location['state'] ?? null,
                'country' => $location['country'] ?? null,
                'region' => $location['region'] ?? null,
            ])
            ->filter(fn (array $location): bool => $this->hasSnapshotValues($location))
            ->values();
    }

    private function transformSnapshotMedia(mixed $media): Collection
    {
        return collect(is_array($media) ? $media : [])
            ->filter(fn (mixed $mediaLink): bool => is_array($mediaLink))
            ->map(fn (array $mediaLink): array => [
                'id' => $mediaLink['id'] ?? null,
                'name' => $mediaLink['name'] ?? null,
                'alt_text' => $mediaLink['alt_text'] ?? $mediaLink['alt'] ?? null,
                'url' => $mediaLink['url'] ?? null,
            ])
            ->filter(fn (array $mediaLink): bool => $this->hasSnapshotValues($mediaLink))
            ->values();
    }

    private function hasSnapshotValues(array $entry): bool
    {
        return collect($entry)->contains(fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function liveOrderMedia(?Model $orderable): Collection
    {
        return collect($orderable?->mediaGallery ?? [])->map(fn ($gallery): array => [
            'id' => $gallery->media?->id,
            'name' => $gallery->media?->name,
            'alt_text' => $gallery->media?->alt_text,
            'url' => $gallery->media?->url,
        ])->values();
    }

    private function transformPayment(?OrderPayment $payment): ?array
    {
        if (! $payment) {
            return null;
        }

        $data = [
            'payment_status' => $payment->payment_status,
            'payment_method' => $payment->payment_method,
            'amount' => $payment->amount,
            'total_amount' => $payment->total_amount,
            'currency' => $payment->currency,
            'is_custom_amount' => $payment->is_custom_amount,
        ];

        if ($payment->is_custom_amount) {
            $data['custom_amount'] = $payment->custom_amount;
        }

        return $data;
    }

    private function transformReview(?Review $review): ?array
    {
        if (! $review) {
            return null;
        }

        return [
            'id' => $review->id,
            'user_id' => $review->user_id,
            'item_type' => $review->item_type,
            'item_id' => $review->item_id,
            'rating' => $review->rating,
            'review_text' => $review->review_text,
            'status' => $review->status,
            'media_gallery' => $review->mediaGallery->map(fn ($gallery): array => [
                'id' => $gallery->media?->id,
                'name' => $gallery->media?->name,
                'alt_text' => $gallery->media?->alt_text,
                'url' => $gallery->media?->url,
            ])->values(),
            'created_at' => $review->created_at,
            'updated_at' => $review->updated_at,
        ];
    }

    private function reviewItemKey(string $itemType, int $itemId): string
    {
        return $this->normalizeItemType($itemType).':'.$itemId;
    }

    private function normalizeItemType(string $itemType): string
    {
        $normalized = array_search($itemType, Relation::morphMap(), true);

        return is_string($normalized)
            ? $normalized
            : Str::lower(class_basename($itemType));
    }

    private function itemTypeDatabaseValues(string $itemType): array
    {
        $normalized = $this->normalizeItemType($itemType);

        return array_values(array_unique(array_filter([
            $normalized,
            Relation::morphMap()[$normalized] ?? null,
        ])));
    }

    private function reviewItem(string $itemType, int $itemId): ?Model
    {
        return match ($itemType) {
            'activity' => Activity::find($itemId),
            'package' => Package::find($itemId),
            'itinerary' => Itinerary::find($itemId),
            default => null,
        };
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((int) ($exception->errorInfo[1] ?? 0), [19, 1062], true)
            || str_contains($exception->getMessage(), 'reviews_order_id_unique');
    }

    private function deleteUploadedReviewMedia(array $mediaIds): void
    {
        \App\Models\Media::whereIn('id', $mediaIds)->get()->each(function ($media): void {
            $path = $media->getRawOriginal('url');
            if (is_string($path) && $path !== '') {
                Storage::disk('minio')->delete($path);
            }
            $media->delete();
        });
    }

    // ****************************Review api all for customers********************************

    public function reviewIndex(Request $request)
    {
        $user = auth()->user();

        // Get pagination parameters
        $perPage = min($request->get('per_page', 6), 50);
        $page = $request->get('page', 1);

        // Fetch reviews of the logged-in customer with media details
        $reviewsQuery = \App\Models\Review::with(['mediaGallery.media', 'item', 'order'])
            ->where('user_id', $user->id)
            ->latest();

        $paginatedReviews = $reviewsQuery->paginate($perPage, ['*'], 'page', $page);

        $reviews = $paginatedReviews->getCollection()->map(function ($review) {
            $media = $review->mediaGallery->map(fn ($rmg) => [
                'id' => $rmg->media->id,
                'name' => $rmg->media->name,
                'alt_text' => $rmg->media->alt_text,
                'url' => $rmg->media->url,
            ])->values();

            // Use helper methods for display data
            $displayName = $review->getDisplayName();
            $displaySlug = $review->getDisplaySlug();
            $hasLiveItem = $review->hasLiveItem();

            return [
                'id' => $review->id,
                'order_id' => $review->order_id,          // NEW
                'item_type' => $review->item_type,
                'item_id' => $review->item_id,
                'item_name' => $displayName,               // Changed: use helper
                'item_slug' => $displaySlug,               // NEW
                'has_live_item' => $hasLiveItem,               // NEW
                'rating' => $review->rating,
                'review_text' => $review->review_text,
                'status' => $review->status,
                'media_gallery' => $media,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'reviews' => $reviews,
            'pagination' => [
                'total' => $paginatedReviews->total(),
                'per_page' => $paginatedReviews->perPage(),
                'current_page' => $paginatedReviews->currentPage(),
                'last_page' => $paginatedReviews->lastPage(),
            ],
        ]);
    }

    public function reviewStore(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Validate review + optional file upload
        $validated = $request->validate([
            'item_type' => 'required|string|max:50|in:activity,package,itinerary',
            'item_id' => 'required|integer',
            'order_id' => 'required|integer|min:1',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string|max:5000',
            'file' => 'nullable|array',
            'file.*' => UploadRules::reviewAttachment(),
        ]);

        $validated['item_id'] = (int) $validated['item_id'];
        $validated['order_id'] = (int) $validated['order_id'];
        $order = Order::where('user_id', $user->id)->find($validated['order_id']);

        if (! $order
            || $this->normalizeItemType($order->orderable_type) !== $validated['item_type']
            || (int) $order->orderable_id !== $validated['item_id']) {
            throw ValidationException::withMessages([
                'order_id' => 'The selected order does not match this customer and item.',
            ]);
        }

        $item = $this->reviewItem($validated['item_type'], $validated['item_id']);
        if (! $item) {
            throw ValidationException::withMessages([
                'item_id' => 'The booked item is no longer available for review.',
            ]);
        }

        if (Review::where('order_id', $validated['order_id'])->exists()) {
            throw ValidationException::withMessages([
                'order_id' => 'A review already exists for this booking.',
            ]);
        }

        $uploadedMediaIds = [];

        // ✅ Handle file upload if present
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $file) {
                // UUID-based key — original filename never enters object path.
                $extension = strtolower($file->getClientOriginalExtension());
                $fileName = Str::uuid().'.'.$extension;
                try {
                    $filePath = MediaStorage::storeUploadedFile($file, 'media', $fileName);
                } catch (RuntimeException) {
                    return response()->json([
                        'message' => 'File upload failed.',
                    ], 500);
                }

                $originalName = UploadRules::sanitizeName(
                    pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                );

                $media = new \App\Models\Media;
                $media->name = $originalName;
                $media->alt_text = $originalName;
                $media->url = $filePath;
                $media->save();

                $uploadedMediaIds[] = $media->id;
            }
        }

        // Snapshots - item is guaranteed to exist at this point
        $itemName = $item->name;
        $itemSlug = $item->slug;

        // Create review with snapshots
        try {
            $review = Review::create([
                'user_id' => $user->id,
                'order_id' => $validated['order_id'],
                'item_type' => $validated['item_type'],
                'item_id' => $validated['item_id'],
                'item_name_snapshot' => $itemName,
                'item_slug_snapshot' => $itemSlug,
                'rating' => $validated['rating'],
                'review_text' => $validated['review_text'],
                'status' => 'pending',
            ]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $this->deleteUploadedReviewMedia($uploadedMediaIds);
            throw ValidationException::withMessages([
                'order_id' => 'A review already exists for this booking.',
            ]);
        }

        // Sync media to review_media_gallery table
        foreach ($uploadedMediaIds as $index => $mediaId) {
            $review->mediaGallery()->create([
                'media_id' => $mediaId,
                'sort_order' => $index,
            ]);
        }
        $review->load('mediaGallery.media');

        // Full media details
        $media = $review->mediaGallery->map(fn ($rmg) => [
            'id' => $rmg->media->id,
            'name' => $rmg->media->name,
            'alt_text' => $rmg->media->alt_text,
            'url' => $rmg->media->url,
        ])->values();

        $reviewData = [
            'id' => $review->id,
            'user_id' => $review->user_id,
            'item_type' => $review->item_type,
            'item_id' => $review->item_id,
            'rating' => $review->rating,
            'review_text' => $review->review_text,
            'status' => $review->status,
            'media_gallery' => $media,
            'created_at' => $review->created_at,
            'updated_at' => $review->updated_at,
        ];

        return response()->json([
            'success' => true,
            'review' => $reviewData,
        ]);
    }

    public function reviewShow($id)
    {
        $user = auth()->user();

        $review = \App\Models\Review::with(['mediaGallery.media', 'item', 'order'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found or access denied',
            ], 404);
        }

        // Load media details
        $media = $review->mediaGallery->map(fn ($rmg) => [
            'id' => $rmg->media->id,
            'name' => $rmg->media->name,
            'alt_text' => $rmg->media->alt_text,
            'url' => $rmg->media->url,
        ])->values();

        // Use helper methods
        $displayName = $review->getDisplayName();
        $displaySlug = $review->getDisplaySlug();
        $hasLiveItem = $review->hasLiveItem();

        $reviewData = [
            'id' => $review->id,
            'order_id' => $review->order_id,          // NEW
            'item_type' => $review->item_type,
            'item_id' => $review->item_id,
            'item_name' => $displayName,               // Changed
            'item_slug' => $displaySlug,               // NEW
            'has_live_item' => $hasLiveItem,               // NEW
            'rating' => $review->rating,
            'review_text' => $review->review_text,
            'status' => $review->status,
            'media_gallery' => $media,
            'created_at' => $review->created_at,
            'updated_at' => $review->updated_at,
        ];

        return response()->json([
            'success' => true,
            'review' => $reviewData,
        ]);
    }

    public function reviewUpdate(Request $request, $id): JsonResponse
    {
        $user = auth()->user();

        // Find the review
        $review = Review::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $review) {
            return response()->json(['error' => 'Review not found or access denied'], 404);
        }

        // Validate optional fields
        $validated = $request->validate([
            'rating' => 'nullable|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:5000',
            'order_id' => 'nullable|integer|min:1',
            'file' => 'nullable|array',
            'file.*' => UploadRules::reviewAttachment(),
            'existing_media_ids' => 'nullable|array',
            'existing_media_ids.*' => 'integer|exists:media,id',
        ]);

        // Update basic fields if provided
        $review->rating = $validated['rating'] ?? $review->rating;
        $review->review_text = $validated['review_text'] ?? $review->review_text;

        if ($request->has('order_id') && $validated['order_id'] !== null) {
            $orderId = (int) $validated['order_id'];
            $order = Order::where('user_id', $user->id)->find($orderId);
            $orderMatchesReview = $order
                && $this->normalizeItemType($order->orderable_type) === $this->normalizeItemType($review->item_type)
                && (int) $order->orderable_id === (int) $review->item_id;
            $changesExistingBooking = $review->order_id !== null && (int) $review->order_id !== $orderId;
            $bookingAlreadyReviewed = Review::where('order_id', $orderId)
                ->whereKeyNot($review->id)
                ->exists();

            if (! $orderMatchesReview || $changesExistingBooking || $bookingAlreadyReviewed) {
                throw ValidationException::withMessages([
                    'order_id' => 'The selected order does not match this review.',
                ]);
            }

            if ($review->order_id === null) {
                try {
                    Review::whereKey($review->id)->update(['order_id' => $orderId]);
                } catch (QueryException $exception) {
                    if (! $this->isUniqueConstraintViolation($exception)) {
                        throw $exception;
                    }

                    throw ValidationException::withMessages([
                        'order_id' => 'A review already exists for this booking.',
                    ]);
                }
            }

            $review->order_id = $orderId;
        }

        // Cast existing_media_ids to integer array
        $existingMediaIdsFromRequest = collect($request->existing_media_ids ?? [])->map(fn ($id) => (int) $id)->toArray();
        $currentMediaIds = $review->load('mediaGallery')->mediaGallery->pluck('media_id')->toArray();

        // Delete media not present in request
        foreach ($currentMediaIds as $oldMediaId) {
            if (! in_array($oldMediaId, $existingMediaIdsFromRequest)) {
                $oldMedia = \App\Models\Media::find($oldMediaId);
                if ($oldMedia && ! empty($oldMedia->getRawOriginal('url'))) {
                    $relativePath = $oldMedia->getRawOriginal('url');

                    // Legacy fallback: handle full URLs from before migration cleanup
                    if (str_starts_with($relativePath, 'http')) {
                        $parsed = parse_url($relativePath, PHP_URL_PATH);
                        $relativePath = ltrim($parsed, '/');
                        $bucket = config('filesystems.disks.minio.bucket');
                        $relativePath = preg_replace('#^'.preg_quote($bucket, '#').'/#', '', $relativePath);
                    }

                    if ($relativePath) {
                        Storage::disk('minio')->delete($relativePath);
                    }
                    $oldMedia->delete();
                }
            }
        }

        $updatedMediaIds = $existingMediaIdsFromRequest;

        // Handle new file uploads
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $fileName = Str::uuid().'.'.$extension;
                try {
                    $filePath = MediaStorage::storeUploadedFile($file, 'media', $fileName);
                } catch (RuntimeException) {
                    return response()->json(['message' => 'File upload failed.'], 500);
                }

                $originalName = UploadRules::sanitizeName(
                    pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                );

                $media = new \App\Models\Media;
                $media->name = $originalName;
                $media->alt_text = $originalName;
                $media->url = $filePath;
                $media->save();

                $updatedMediaIds[] = $media->id;
            }
        }

        $review->mediaGallery()->delete();
        foreach ($updatedMediaIds as $index => $mediaId) {
            $review->mediaGallery()->create([
                'media_id' => $mediaId,
                'sort_order' => $index,
            ]);
        }

        if ($review->item) {
            $review->item_name_snapshot = $review->item->name;
            $review->item_slug_snapshot = $review->item->slug;
        }
        $review->save();
        $review->load('mediaGallery.media');

        $media = $review->mediaGallery->map(fn ($rmg) => [
            'id' => $rmg->media->id,
            'name' => $rmg->media->name,
            'alt_text' => $rmg->media->alt_text,
            'url' => $rmg->media->url,
        ])->values();

        return response()->json([
            'success' => true,
            'review' => [
                'id' => $review->id,
                'user_id' => $review->user_id,
                'item_type' => $review->item_type,
                'item_id' => $review->item_id,
                'rating' => $review->rating,
                'review_text' => $review->review_text,
                'status' => $review->status,
                'media_gallery' => $media,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ],
        ]);
    }

    public function reviewDelete($id)
    {
        $user = auth()->user();

        // Find the review by id and user
        $review = \App\Models\Review::with('mediaGallery')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $review) {
            return response()->json(['error' => 'Review not found or access denied'], 404);
        }

        // Delete associated media (if any)
        $currentMediaIds = $review->mediaGallery->pluck('media_id')->toArray();

        foreach ($currentMediaIds as $mediaId) {
            $media = \App\Models\Media::find($mediaId);
            if ($media && ! empty($media->getRawOriginal('url'))) {
                $relativePath = $media->getRawOriginal('url');

                // Legacy fallback: handle full URLs from before migration cleanup
                if (str_starts_with($relativePath, 'http')) {
                    $parsed = parse_url($relativePath, PHP_URL_PATH);
                    $relativePath = ltrim($parsed, '/');
                    $bucket = config('filesystems.disks.minio.bucket');
                    $relativePath = preg_replace('#^'.preg_quote($bucket, '#').'/#', '', $relativePath);
                }

                if ($relativePath) {
                    Storage::disk('minio')->delete($relativePath);
                }
                // Delete DB record
                $media->delete();
            }
        }

        // Finally delete the review
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully',
        ]);
    }
}
