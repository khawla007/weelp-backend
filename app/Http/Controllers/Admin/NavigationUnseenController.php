<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NavigationUnseenController extends Controller
{
    private const RESOURCES = [
        'orders' => [
            'model' => Order::class,
            'seen_at' => 'admin_orders_last_seen_at',
        ],
        'reviews' => [
            'model' => Review::class,
            'seen_at' => 'admin_reviews_last_seen_at',
        ],
    ];

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['data' => $this->counts($user->fresh())]);
    }

    public function markSeen(Request $request, string $resource): JsonResponse
    {
        $validated = Validator::make(
            [...$request->all(), 'resource' => $resource],
            [
                'resource' => ['required', Rule::in(array_keys(self::RESOURCES))],
                'seen_through' => ['sometimes', 'required', 'date_format:Y-m-d\TH:i:s.v\Z'],
            ]
        )->validate();

        $now = Carbon::now('UTC')->utc();
        $candidate = array_key_exists('seen_through', $validated)
            ? Carbon::createFromFormat('Y-m-d\TH:i:s.v\Z', $validated['seen_through'], 'UTC')->utc()
            : $now;
        $candidate = $candidate->greaterThan($now) ? $now : $candidate;
        $candidateBoundary = $candidate->format('Y-m-d H:i:s.v');

        /** @var User $user */
        $user = $request->user();
        $seenAt = self::RESOURCES[$resource]['seen_at'];

        User::query()
            ->whereKey($user->getKey())
            ->where(function (Builder $query) use ($seenAt, $candidateBoundary): void {
                $query->whereNull($seenAt)
                    ->orWhere($seenAt, '<', $candidateBoundary);
            })
            ->update([$seenAt => $candidateBoundary]);

        return response()->json(['data' => $this->counts($user->fresh())]);
    }

    /**
     * @return array{orders: int, reviews: int, has_actionable_cancellations: bool}
     */
    private function counts(User $user): array
    {
        $counts = [];

        foreach (self::RESOURCES as $resource => $configuration) {
            $seenThrough = $user->{$configuration['seen_at']}
                ->utc()
                ->format('Y-m-d H:i:s.v');
            $counts[$resource] = $configuration['model']::query()
                ->where('created_at', '>', $seenThrough)
                ->count();
        }

        $counts['has_actionable_cancellations'] = CancellationRequest::query()
            ->whereIn('status', CancellationRequest::ADMIN_ATTENTION_STATUSES)
            ->whereHas('order', function (Builder $query): void {
                $query->whereNull('orders.deleted_at');
            })
            ->exists();

        return $counts;
    }
}
