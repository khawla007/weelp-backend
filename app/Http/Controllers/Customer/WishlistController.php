<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Package;
use App\Models\Transfer;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WishlistController extends Controller
{
    private const ITEM_MODELS = [
        WishlistItem::TYPE_ACTIVITY => Activity::class,
        WishlistItem::TYPE_ITINERARY => Itinerary::class,
        WishlistItem::TYPE_PACKAGE => Package::class,
        WishlistItem::TYPE_TRANSFER => Transfer::class,
    ];

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 50);
        $paginated = WishlistItem::query()
            ->forUser($request->user()->id)
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_type' => ['required', 'string', Rule::in(WishlistItem::SUPPORTED_TYPES)],
            'item_id' => ['required', 'integer', 'min:1'],
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'city_slug' => ['nullable', 'string', 'max:255'],
            'city_name' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'currency' => ['nullable', 'string', 'max:10'],
            'snapshot' => ['nullable', 'array'],
        ]);

        $item = $this->findSupportedItem($validated['item_type'], (int) $validated['item_id']);

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist item target was not found.',
            ], 422);
        }

        $attributes = $this->displayAttributes($validated, $item);
        $upsertAttributes = $attributes;

        if (is_array($upsertAttributes['snapshot'])) {
            $upsertAttributes['snapshot'] = json_encode($upsertAttributes['snapshot'], JSON_THROW_ON_ERROR);
        }

        $identity = [
            'user_id' => $request->user()->id,
            'item_type' => $validated['item_type'],
            'item_id' => (int) $validated['item_id'],
        ];

        WishlistItem::query()->upsert(
            [array_merge($identity, $upsertAttributes, [
                'created_at' => now(),
                'updated_at' => now(),
            ])],
            ['user_id', 'item_type', 'item_id'],
            array_keys(array_merge($upsertAttributes, ['updated_at' => now()])),
        );

        $wishlistItem = WishlistItem::query()
            ->where($identity)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Wishlist item saved.',
            'data' => $wishlistItem->fresh(),
        ]);
    }

    public function destroy(Request $request, WishlistItem $wishlistItem): JsonResponse
    {
        if ((int) $wishlistItem->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot remove this wishlist item.',
            ], 403);
        }

        $wishlistItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wishlist item removed.',
        ]);
    }

    public function destroyByItem(Request $request, string $itemType, int $itemId): JsonResponse
    {
        if (! in_array($itemType, WishlistItem::SUPPORTED_TYPES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported wishlist item type.',
            ], 422);
        }

        $wishlistItem = WishlistItem::query()
            ->forUser($request->user()->id)
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->first();

        if (! $wishlistItem) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist item was not found.',
            ], 404);
        }

        $wishlistItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wishlist item removed.',
        ]);
    }

    private function findSupportedItem(string $itemType, int $itemId): ?Model
    {
        $modelClass = self::ITEM_MODELS[$itemType] ?? null;

        if (! $modelClass) {
            return null;
        }

        return $this->wishlistableQuery($itemType, $modelClass)
            ->with($this->relationsFor($itemType))
            ->find($itemId);
    }

    private function wishlistableQuery(string $itemType, string $modelClass)
    {
        $query = $modelClass::query();

        return match ($itemType) {
            WishlistItem::TYPE_ITINERARY => $query
                ->where('private_itinerary', false)
                ->where(function ($query): void {
                    $query->whereDoesntHave('meta')
                        ->orWhereHas('meta', fn ($metaQuery) => $metaQuery->where('status', 'approved'));
                }),
            WishlistItem::TYPE_PACKAGE => $query->where('private_package', false),
            default => $query,
        };
    }

    /**
     * @return array<int, string>
     */
    private function relationsFor(string $itemType): array
    {
        return match ($itemType) {
            WishlistItem::TYPE_ACTIVITY => [
                'locations.city',
                'pricing',
                'mediaGallery.media',
            ],
            WishlistItem::TYPE_ITINERARY => [
                'locations.city',
                'basePricing.variations',
                'mediaGallery.media',
            ],
            WishlistItem::TYPE_PACKAGE => [
                'locations.city',
                'basePricing.variations',
                'mediaGallery.media',
            ],
            WishlistItem::TYPE_TRANSFER => [
                'vendorRoutes.pickupPlace.city',
                'vendorRoutes.dropoffPlace.city',
                'pricingAvailability',
                'mediaGallery.media',
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function displayAttributes(array $validated, Model $item): array
    {
        $city = $this->resolveCity($validated['item_type'], $item);
        $pricing = $this->resolvePricing($validated['item_type'], $item);

        return [
            'title' => $validated['title'] ?? $item->name,
            'slug' => $item->slug,
            'city_slug' => $validated['city_slug'] ?? $city['slug'],
            'city_name' => $validated['city_name'] ?? $city['name'],
            'image_url' => $validated['image_url'] ?? $this->resolveImageUrl($item),
            'price' => $validated['price'] ?? $pricing['price'],
            'currency' => $validated['currency'] ?? $pricing['currency'],
            'snapshot' => $validated['snapshot'] ?? null,
        ];
    }

    /**
     * @return array{slug: ?string, name: ?string}
     */
    private function resolveCity(string $itemType, Model $item): array
    {
        $city = match ($itemType) {
            WishlistItem::TYPE_ACTIVITY => $item->locations
                ->firstWhere('location_type', 'primary')?->city
                ?? $item->locations->first()?->city,
            WishlistItem::TYPE_ITINERARY, WishlistItem::TYPE_PACKAGE => $item->locations->first()?->city,
            WishlistItem::TYPE_TRANSFER => $item->vendorRoutes?->pickupPlace?->city
                ?? $item->vendorRoutes?->dropoffPlace?->city,
            default => null,
        };

        return [
            'slug' => $city?->slug,
            'name' => $city?->name,
        ];
    }

    private function resolveImageUrl(Model $item): ?string
    {
        $gallery = $item->mediaGallery ?? collect();

        return $gallery->firstWhere('is_featured', true)?->media?->url
            ?? $gallery->first()?->media?->url;
    }

    /**
     * @return array{price: mixed, currency: mixed}
     */
    private function resolvePricing(string $itemType, Model $item): array
    {
        return match ($itemType) {
            WishlistItem::TYPE_ACTIVITY => [
                'price' => $item->pricing?->regular_price,
                'currency' => $item->pricing?->currency,
            ],
            WishlistItem::TYPE_ITINERARY, WishlistItem::TYPE_PACKAGE => [
                'price' => $item->basePricing?->variations?->sortBy('regular_price')->first()?->regular_price,
                'currency' => $item->basePricing?->currency,
            ],
            WishlistItem::TYPE_TRANSFER => [
                'price' => $item->pricingAvailability?->transfer_price,
                'currency' => $item->pricingAvailability?->currency,
            ],
            default => [
                'price' => null,
                'currency' => null,
            ],
        };
    }
}
