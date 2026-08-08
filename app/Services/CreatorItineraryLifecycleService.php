<?php

namespace App\Services;

use App\Models\Itinerary;
use App\Models\Review;
use App\Models\WishlistItem;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatorItineraryLifecycleService
{
    public function trash(
        int $id,
        ?Closure $withinTransaction = null,
        bool $requireRemovalRequest = false,
    ): Itinerary {
        return DB::transaction(function () use ($id, $withinTransaction, $requireRemovalRequest): Itinerary {
            $itinerary = Itinerary::standaloneCreator()
                ->lockForUpdate()
                ->findOrFail($id);

            if ($requireRemovalRequest && $itinerary->removal_status !== 'requested') {
                throw ValidationException::withMessages([
                    'itinerary' => 'No pending removal request was found.',
                ]);
            }

            if ($itinerary->draft_itinerary_id || in_array($itinerary->status, ['pending', 'edit_pending'], true)) {
                throw ValidationException::withMessages([
                    'itinerary' => 'Resolve the pending approval before moving this itinerary to Trash.',
                ]);
            }

            $itinerary->meta->update([
                'status' => 'deleted',
                'removal_status' => 'approved',
                'publication_requested_at' => null,
                'publication_rejection_reason' => null,
            ]);
            $itinerary->delete();
            $withinTransaction?->__invoke($itinerary);

            return $itinerary->load('meta');
        });
    }

    public function restoreToDraft(
        int $id,
        ?int $ownedByCreatorId = null,
        ?Closure $withinTransaction = null,
    ): Itinerary {
        return DB::transaction(function () use ($id, $ownedByCreatorId, $withinTransaction): Itinerary {
            $query = Itinerary::onlyTrashed()->standaloneCreator();

            if ($ownedByCreatorId !== null) {
                $query->whereHas('meta', fn (Builder $meta) => $meta->where('creator_id', $ownedByCreatorId));
            }

            $itinerary = $query->lockForUpdate()->findOrFail($id);
            $itinerary->restore();
            $itinerary->meta->update([
                'status' => 'draft',
                'removal_status' => null,
                'removal_reason' => null,
                'publication_requested_at' => null,
                'publication_rejection_reason' => null,
            ]);
            $withinTransaction?->__invoke($itinerary);

            return $itinerary->fresh(['meta']);
        });
    }

    public function requestRemoval(
        int $id,
        int $creatorId,
        ?string $reason,
        ?Closure $withinTransaction = null,
    ): Itinerary {
        return DB::transaction(function () use ($id, $creatorId, $reason, $withinTransaction): Itinerary {
            $itinerary = $this->ownedStandaloneQuery($creatorId)->lockForUpdate()->findOrFail($id);

            if (! in_array($itinerary->status, ['draft', 'rejected', 'approved'], true)) {
                throw ValidationException::withMessages(['itinerary' => 'This itinerary cannot be removed while approval is pending.']);
            }
            if ($itinerary->draft_itinerary_id) {
                throw ValidationException::withMessages(['itinerary' => 'Resolve the pending edit before requesting removal.']);
            }
            if ($itinerary->removal_status === 'requested') {
                throw ValidationException::withMessages(['itinerary' => 'A removal request is already pending.']);
            }

            $itinerary->meta->update([
                'removal_status' => 'requested',
                'removal_reason' => $reason,
            ]);
            $withinTransaction?->__invoke($itinerary);

            return $itinerary->fresh(['meta']);
        });
    }

    public function rejectRemoval(int $id, ?Closure $withinTransaction = null): Itinerary
    {
        return DB::transaction(function () use ($id, $withinTransaction): Itinerary {
            $itinerary = Itinerary::standaloneCreator()->lockForUpdate()->findOrFail($id);
            if ($itinerary->removal_status !== 'requested') {
                throw ValidationException::withMessages(['itinerary' => 'No pending removal request was found.']);
            }

            $itinerary->meta->update(['removal_status' => null, 'removal_reason' => null]);
            $withinTransaction?->__invoke($itinerary);

            return $itinerary->fresh(['meta']);
        });
    }

    public function requestPublication(
        int $id,
        int $creatorId,
        ?Closure $withinTransaction = null,
    ): Itinerary {
        return DB::transaction(function () use ($id, $creatorId, $withinTransaction): Itinerary {
            $itinerary = $this->ownedStandaloneQuery($creatorId)->lockForUpdate()->findOrFail($id);

            if ($itinerary->status !== 'draft') {
                throw ValidationException::withMessages(['itinerary' => 'Only a Draft can be requested for publication.']);
            }
            if ($itinerary->removal_status === 'requested') {
                throw ValidationException::withMessages(['itinerary' => 'Resolve the removal request before requesting publication.']);
            }

            $itinerary->meta->update([
                'status' => 'pending',
                'publication_requested_at' => now(),
                'publication_rejection_reason' => null,
            ]);
            $withinTransaction?->__invoke($itinerary);

            return $itinerary->fresh(['meta']);
        });
    }

    public function publish(int $id, ?Closure $withinTransaction = null): Itinerary
    {
        return DB::transaction(function () use ($id, $withinTransaction): Itinerary {
            $itinerary = Itinerary::standaloneCreator()->lockForUpdate()->findOrFail($id);
            if (! in_array($itinerary->status, ['draft', 'pending'], true)) {
                throw ValidationException::withMessages(['itinerary' => 'Only Draft or Pending itineraries can be published.']);
            }
            if ($itinerary->removal_status === 'requested') {
                throw ValidationException::withMessages(['itinerary' => 'Resolve the removal request before publishing this itinerary.']);
            }

            $itinerary->meta->update([
                'status' => 'approved',
                'publication_requested_at' => null,
                'publication_rejection_reason' => null,
                'removal_status' => null,
                'removal_reason' => null,
            ]);
            $withinTransaction?->__invoke($itinerary);

            return $itinerary->fresh(['meta']);
        });
    }

    public function rejectPublication(
        int $id,
        ?string $reason,
        ?Closure $withinTransaction = null,
    ): Itinerary {
        return DB::transaction(function () use ($id, $reason, $withinTransaction): Itinerary {
            $itinerary = Itinerary::standaloneCreator()->lockForUpdate()->findOrFail($id);
            if ($itinerary->status !== 'pending') {
                throw ValidationException::withMessages(['itinerary' => 'Only Pending itineraries can be rejected.']);
            }

            $wasPublicationRequest = $itinerary->publication_requested_at !== null;
            $itinerary->meta->update([
                'status' => $wasPublicationRequest ? 'draft' : 'rejected',
                'publication_requested_at' => null,
                'publication_rejection_reason' => $wasPublicationRequest ? $reason : null,
            ]);
            $withinTransaction?->__invoke($itinerary);

            return $itinerary->fresh(['meta']);
        });
    }

    public function forceDelete(int $id, ?CarbonInterface $deletedBefore = null): bool
    {
        return DB::transaction(function () use ($id, $deletedBefore): bool {
            $itinerary = Itinerary::onlyTrashed()
                ->standaloneCreator()
                ->lockForUpdate()
                ->find($id);

            if (! $itinerary || ($deletedBefore && $itinerary->deleted_at->isAfter($deletedBefore))) {
                return false;
            }

            WishlistItem::query()
                ->where('item_type', WishlistItem::TYPE_ITINERARY)
                ->where('item_id', $itinerary->id)
                ->delete();
            $itinerary->postTags()->delete();
            $itinerary->reviews()
                ->whereNull('order_id')
                ->each(function (Review $review): void {
                    $review->mediaGallery()->delete();
                    $review->delete();
                });
            $itinerary->forceDelete();

            return true;
        });
    }

    private function ownedStandaloneQuery(int $creatorId): Builder
    {
        return Itinerary::standaloneCreator()
            ->whereHas('meta', fn (Builder $meta) => $meta->where('creator_id', $creatorId));
    }
}
