<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Review;
use App\Models\ReviewHelpfulVote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewHelpfulController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'review_ids' => ['required', 'array', 'max:50'],
            'review_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);

        $reviewIds = $this->publicReviewQuery()
            ->whereKey($validated['review_ids'])
            ->whereHas('helpfulVotes', fn (Builder $votes): Builder => $votes
                ->where('user_id', (int) $request->user()->getAuthIdentifier()))
            ->pluck('id')
            ->map(fn (int $reviewId): int => $reviewId)
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => ['review_ids' => $reviewIds],
        ]);
    }

    public function store(Request $request, int $review): JsonResponse
    {
        $userId = (int) $request->user()->getAuthIdentifier();

        return DB::transaction(function () use ($review, $userId): JsonResponse {
            $publicReview = $this->findPublicReviewOrFail($review, lockForUpdate: true);

            abort_if(
                $publicReview->user_id === $userId,
                422,
                'You cannot mark your own review as helpful.'
            );

            ReviewHelpfulVote::query()->firstOrCreate([
                'review_id' => $publicReview->id,
                'user_id' => $userId,
            ]);

            return $this->voteResponse($publicReview, $userId);
        });
    }

    public function destroy(Request $request, int $review): JsonResponse
    {
        $userId = (int) $request->user()->getAuthIdentifier();

        return DB::transaction(function () use ($review, $userId): JsonResponse {
            $publicReview = $this->findPublicReviewOrFail($review, lockForUpdate: true);

            $publicReview->helpfulVotes()
                ->where('user_id', $userId)
                ->delete();

            return $this->voteResponse($publicReview, $userId);
        });
    }

    private function publicReviewQuery(): Builder
    {
        return Review::query()
            ->where('status', 'approved')
            ->where(function (Builder $reviews): void {
                $reviews->where(function (Builder $activityReviews): void {
                    $activityReviews
                        ->where('item_type', 'activity')
                        ->whereIn('item_id', Activity::query()->select('id'));
                })->orWhere(function (Builder $itineraryReviews): void {
                    $itineraryReviews
                        ->where('item_type', 'itinerary')
                        ->whereIn('item_id', Itinerary::publiclyVisible()->select('id'));
                });
            });
    }

    private function findPublicReviewOrFail(int $reviewId, bool $lockForUpdate = false): Review
    {
        $query = $this->publicReviewQuery();

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $review = $query->find($reviewId);

        abort_if($review === null, 404);

        return $review;
    }

    private function voteResponse(Review $review, int $userId): JsonResponse
    {
        $helpfulVotes = $review->helpfulVotes();

        return response()->json([
            'success' => true,
            'data' => [
                'review_id' => $review->id,
                'helpful_count' => (clone $helpfulVotes)->count(),
                'viewer_has_marked_helpful' => $helpfulVotes
                    ->where('user_id', $userId)
                    ->exists(),
            ],
        ]);
    }
}
