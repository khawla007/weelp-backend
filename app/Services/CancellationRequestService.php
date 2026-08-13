<?php

namespace App\Services;

use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\OrderPayment;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CancellationRequestService
{
    private const UNRESOLVED_STATUSES = [
        CancellationRequest::STATUS_PENDING,
        CancellationRequest::STATUS_REFUND_PROCESSING,
        CancellationRequest::STATUS_REFUND_FAILED,
    ];

    public function __construct(
        private readonly CancellationPolicyService $policy,
        private readonly CancellationNotificationService $notifications,
    ) {}

    public function quoteForCustomer(int $orderId, int $customerId): array
    {
        $order = Order::query()
            ->whereKey($orderId)
            ->where('user_id', $customerId)
            ->with(['payment', 'latestCancellationRequest'])
            ->firstOrFail();

        $this->assertNoUnresolvedRequest($order);

        return $this->publicQuote($this->policy->quote($order));
    }

    public function create(int $orderId, int $customerId, string $reason): CancellationRequest
    {
        try {
            $cancellation = DB::transaction(function () use ($orderId, $customerId, $reason): CancellationRequest {
                $requestedAt = CarbonImmutable::now((string) config('app.timezone'));
                $this->beforeOrderLock($orderId, $customerId);
                $order = Order::query()
                    ->whereKey($orderId)
                    ->where('user_id', $customerId)
                    ->lockForUpdate()
                    ->firstOrFail();
                $this->afterOrderLocked($order);

                $payment = OrderPayment::query()
                    ->where('order_id', $order->id)
                    ->lockForUpdate()
                    ->first();
                $order->setRelation('payment', $payment);
                $order->setRelation(
                    'latestCancellationRequest',
                    CancellationRequest::query()
                        ->where('order_id', $order->id)
                        ->latest('requested_at')
                        ->latest('id')
                        ->lockForUpdate()
                        ->first(),
                );
                $this->assertNoUnresolvedRequest($order);
                $quote = $this->policy->quote($order, $requestedAt);

                $cancellation = $order->cancellationRequests()->create([
                    'customer_id' => $customerId,
                    'status' => CancellationRequest::STATUS_PENDING,
                    'reason' => $reason,
                    'requested_at' => $requestedAt,
                    'policy_version' => $quote['policy_version'],
                    'policy_snapshot' => $quote['policy_snapshot'],
                    'travel_starts_at' => $quote['travel_starts_at'],
                    'seconds_remaining' => $quote['seconds_remaining'],
                    'paid_amount' => $quote['paid_amount'],
                    'currency' => $quote['currency'],
                    'suggested_deduction_percentage' => $quote['deduction_percentage'],
                    'suggested_deduction_amount' => $quote['suggested_deduction'],
                    'suggested_refund_amount' => $quote['suggested_refund'],
                ]);

                $this->notifications->recordRequested($cancellation);

                return $cancellation;
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateConstraint($exception)) {
                throw new DomainException('A cancellation request is already being reviewed.');
            }

            throw $exception;
        }

        return $cancellation;
    }

    public function eligibility(Order $order): array
    {
        try {
            $this->assertNoUnresolvedRequest($order);
            $this->policy->quote($order);

            return ['eligible' => true, 'reason' => null];
        } catch (DomainException $exception) {
            return ['eligible' => false, 'reason' => $exception->getMessage()];
        }
    }

    public function transform(?CancellationRequest $request, bool $admin = false): ?array
    {
        if (! $request) {
            return null;
        }

        $data = [
            'id' => $request->id,
            'status' => $request->status,
            'reason' => $request->reason,
            'requested_at' => $request->requested_at?->toISOString(),
            'policy_version' => $request->policy_version,
            'travel_starts_at' => $request->travel_starts_at?->toISOString(),
            'seconds_remaining' => $request->seconds_remaining,
            'currency' => $request->currency,
            'deduction_percentage' => $request->deduction_percentage,
            'paid_amount' => $request->paid_amount,
            'suggested_deduction' => $request->suggested_deduction,
            'suggested_refund' => $request->suggested_refund,
            'final_refund' => $request->final_refund,
            'final_deduction' => $request->final_deduction,
            'decision_explanation' => $request->decision_explanation,
            'decided_at' => $request->decided_at?->toISOString(),
            'refund_completed_at' => $request->refund_completed_at?->toISOString(),
            'refund_outcome' => $this->safeRefundOutcome($request),
            'can_retry' => $request->canRetry(),
            'can_reject' => $request->canReject(),
        ];

        if ($admin) {
            $data['failure_code'] = $request->failure_code;
            $data['failure_summary'] = $request->failure_summary;
        }

        return $data;
    }

    private function assertNoUnresolvedRequest(Order $order): void
    {
        $latest = $order->latestCancellationRequest;
        if ($latest && in_array($latest->status, self::UNRESOLVED_STATUSES, true)) {
            throw new DomainException('A cancellation request is already being reviewed.');
        }

        if ($latest) {
            throw new DomainException('This booking already has a cancellation decision. Contact support for help.');
        }
    }

    private function publicQuote(array $quote): array
    {
        unset($quote['policy_snapshot']);

        return $quote;
    }

    private function safeRefundOutcome(CancellationRequest $request): ?string
    {
        $outcome = is_string($request->refund_outcome)
            ? mb_strtolower(trim($request->refund_outcome))
            : null;

        if (in_array($outcome, ['no_refund', 'partial', 'full'], true)) {
            return $outcome;
        }

        return $request->status === CancellationRequest::STATUS_REFUND_FAILED
            ? 'failed'
            : null;
    }

    private function isDuplicateConstraint(QueryException $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'cancellation_requests')
            && (in_array((int) ($exception->errorInfo[1] ?? 0), [19, 1062], true)
                || str_contains($message, 'unique'));
    }

    protected function beforeOrderLock(int $orderId, int $customerId): void
    {
        // Test seam for proving real database-lock contention.
    }

    protected function afterOrderLocked(Order $order): void
    {
        // Test seam for proving real database-lock contention.
    }
}
