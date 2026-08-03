<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SupportItemResolver
{
    /**
     * @var array<string, array{class-string<Model>, string}>
     */
    private const TYPES = [
        'activity' => [Activity::class, 'activities'],
        'package' => [Package::class, 'packages'],
        'itinerary' => [Itinerary::class, 'itineraries'],
    ];

    public function resolve(string $itemType, int $itemId, string $itemSlug, string $citySlug): Model
    {
        [$modelClass] = $this->typeDefinition($itemType, $itemId);

        $query = (new $modelClass)->newQuery();

        $query = match ($itemType) {
            'package' => $query->where('private_package', false),
            'itinerary' => $query
                ->where('private_itinerary', false)
                ->where(function (Builder $query): void {
                    $query->whereDoesntHave('meta')
                        ->orWhereHas(
                            'meta',
                            fn (Builder $metaQuery) => $metaQuery->where('status', 'approved'),
                        );
                }),
            default => $query,
        };

        return $query
            ->whereKey($itemId)
            ->where('slug', $itemSlug)
            ->whereHas(
                'locations.city',
                fn (Builder $query) => $query->where('slug', $citySlug),
            )
            ->firstOrFail();
    }

    public function publicPathSegment(string $itemType): string
    {
        [, $pathSegment] = $this->typeDefinition($itemType);

        return $pathSegment;
    }

    /**
     * @return array{class-string<Model>, string}
     */
    private function typeDefinition(string $itemType, ?int $itemId = null): array
    {
        if (! array_key_exists($itemType, self::TYPES)) {
            $exception = new ModelNotFoundException;

            throw $exception->setModel(Model::class, $itemId === null ? [] : [$itemId]);
        }

        return self::TYPES[$itemType];
    }
}
