<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\StripeRefundGateway;
use App\Exceptions\RefundGatewayException;
use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CancellationRefundService
{
    public function __construct(
        private readonly StripeRefundGateway $gateway,
        private readonly CancellationNotificationService $notificationService,
    ) {}

    public function reject(int $requestId, User $admin, string $explanation): CancellationRequest
    {
        if (trim($explanation) === '') {
            throw ValidationException::withMessages([
                'explanation' => 'An explanation is required when rejecting a cancellation request.',
            ]);
        }

        $request = DB::transaction(function () use ($requestId, $admin, $explanation): CancellationRequest {
            [$request] = $this->lockWorkflow($requestId);

            if ($request->status !== CancellationRequest::STATUS_PENDING
                && ! ($request->status === CancellationRequest::STATUS_REFUND_FAILED
                    && $request->failure_disposition === 'definitive')) {
                throw new DomainException('This cancellation request cannot be rejected in its current state.');
            }

            $request->update([
                'status' => CancellationRequest::STATUS_REJECTED,
                'decision_explanation' => trim($explanation),
                'decided_by' => $admin->id,
                'decided_at' => now(),
                'refund_outcome' => null,
                'refund_completed_at' => null,
            ]);

            $request = $request->refresh();
            $this->notifications()->recordRejected($request);

            return $request;
        });

        return $request;
    }

    public function approve(
        int $requestId,
        User $admin,
        string $finalRefund,
        ?string $explanation,
    ): CancellationRequest {
        $decision = DB::transaction(function () use ($requestId, $admin, $finalRefund, $explanation): array {
            [$request, $order, $payment] = $this->lockWorkflow($requestId);
            $this->assertPending($request);

            $amountMinor = $this->parseDecisionAmount($finalRefund, $request->currency);
            $paidMinor = $this->parseStoredAmount($request->paid_amount, $request->currency);
            $suggestedMinor = $this->parseStoredAmount($request->suggested_refund_amount, $request->currency);
            $this->assertExplanationForAdjustment($amountMinor, $suggestedMinor, $explanation);

            return compact('request', 'order', 'payment', 'amountMinor', 'paidMinor', 'suggestedMinor') + [
                'adminId' => $admin->id,
                'explanation' => $this->nullableTrim($explanation),
            ];
        });

        $paymentIntentId = $this->paymentIntentId($decision['payment']);

        try {
            $providerRefundedMinor = $this->gateway->refundedAmount($paymentIntentId);
        } catch (RefundGatewayException $exception) {
            $this->logProviderFailure(
                $requestId,
                $decision['order']->id,
                $paymentIntentId,
                $exception,
            );
            $this->recordProviderFailureBeforeProcessing($requestId, $admin, $decision, $exception);

            throw $exception;
        }

        $processing = DB::transaction(function () use ($requestId, $admin, $decision, $providerRefundedMinor): array {
            [$request, $order, $payment] = $this->lockWorkflow($requestId);
            $this->assertPending($request);

            $paidMinor = $this->parseStoredAmount($request->paid_amount, $request->currency);
            $localRefundedMinor = $this->parseStoredAmount(
                $payment->refunded_amount ?? '0.00',
                $request->currency,
            );
            $providerRefundedMinor = min(
                $paidMinor,
                max(0, $providerRefundedMinor, $localRefundedMinor),
            );
            $remainingMinor = max(0, $paidMinor - $providerRefundedMinor);
            if ($decision['amountMinor'] > $remainingMinor) {
                throw ValidationException::withMessages([
                    'final_refund' => 'The refund cannot exceed the provider-confirmed remaining balance.',
                ]);
            }

            $this->reconcilePayment($payment, $providerRefundedMinor, $paidMinor, $request->currency);
            $idempotencyKey = "cancel-request-{$request->id}";
            $request->update([
                'status' => CancellationRequest::STATUS_REFUND_PROCESSING,
                'final_refund_amount' => $this->formatMinor($decision['amountMinor'], $request->currency),
                'final_deduction_amount' => $this->formatMinor(
                    max(0, $paidMinor - $decision['amountMinor']),
                    $request->currency,
                ),
                'decision_explanation' => $decision['explanation'],
                'decided_by' => $admin->id,
                'decided_at' => now(),
                'idempotency_key' => $idempotencyKey,
                'failure_code' => null,
                'failure_summary' => null,
                'failure_disposition' => null,
            ]);

            return [
                'requestId' => $request->id,
                'orderId' => $order->id,
                'paymentIntentId' => $this->paymentIntentId($payment),
                'amountMinor' => $decision['amountMinor'],
                'providerRefundedMinor' => $providerRefundedMinor,
                'expectedCumulativeMinor' => $providerRefundedMinor + $decision['amountMinor'],
                'idempotencyKey' => $idempotencyKey,
                'metadata' => $this->metadata($request),
            ];
        });

        if ($processing['amountMinor'] === 0) {
            return $this->finalize(
                $processing['requestId'],
                null,
                $processing['providerRefundedMinor'],
            );
        }

        return $this->callProviderAndFinalize($processing);
    }

    public function retry(int $requestId, User $admin): CancellationRequest
    {
        $captured = DB::transaction(function () use ($requestId): array {
            [$request, $order, $payment] = $this->lockWorkflow($requestId);
            $this->assertRetryable($request);

            $amountMinor = $this->parseStoredAmount($request->final_refund_amount, $request->currency);
            $paidMinor = $this->parseStoredAmount($request->paid_amount, $request->currency);

            return [
                'requestId' => $request->id,
                'orderId' => $order->id,
                'paymentIntentId' => $this->paymentIntentId($payment),
                'amountMinor' => $amountMinor,
                'localRefundedMinor' => $this->parseStoredAmount($payment->refunded_amount ?? '0.00', $request->currency),
                'paidMinor' => $paidMinor,
                'idempotencyKey' => $request->idempotency_key,
                'metadata' => $this->metadata($request),
            ];
        });

        try {
            $providerRefundedMinor = $this->gateway->refundedAmount($captured['paymentIntentId']);
        } catch (RefundGatewayException $exception) {
            $this->logProviderFailure(
                $captured['requestId'],
                $captured['orderId'],
                $captured['paymentIntentId'],
                $exception,
            );
            $this->recordExistingFailureAlert($captured['requestId']);

            throw $exception;
        }

        $processing = DB::transaction(function () use ($requestId, $admin, $captured, $providerRefundedMinor): array {
            [$request, $order, $payment] = $this->lockWorkflow($requestId);
            $this->assertRetryable($request);

            $amountMinor = $this->parseStoredAmount($request->final_refund_amount, $request->currency);
            $paidMinor = $this->parseStoredAmount($request->paid_amount, $request->currency);
            $localRefundedMinor = $this->parseStoredAmount($payment->refunded_amount ?? '0.00', $request->currency);
            $providerRefundedMinor = min($paidMinor, max(0, $providerRefundedMinor, $localRefundedMinor));
            $remainingMinor = max(0, $paidMinor - $providerRefundedMinor);
            $capturedRefundCouldAlreadyExist = $providerRefundedMinor
                >= min($paidMinor, $captured['localRefundedMinor'] + $amountMinor);

            $this->reconcilePayment($payment, $providerRefundedMinor, $paidMinor, $request->currency);
            if ($amountMinor > $remainingMinor && ! $capturedRefundCouldAlreadyExist) {
                return [
                    'validationError' => true,
                ];
            }

            $request->update([
                'status' => CancellationRequest::STATUS_REFUND_PROCESSING,
                'decided_by' => $request->decided_by ?? $admin->id,
                'decided_at' => $request->decided_at ?? now(),
                'failure_code' => null,
                'failure_summary' => null,
                'failure_disposition' => null,
            ]);

            return [
                'requestId' => $request->id,
                'orderId' => $order->id,
                'paymentIntentId' => $this->paymentIntentId($payment),
                'amountMinor' => $amountMinor,
                'providerRefundedMinor' => $providerRefundedMinor,
                'expectedCumulativeMinor' => $capturedRefundCouldAlreadyExist
                    ? $providerRefundedMinor
                    : $providerRefundedMinor + $amountMinor,
                'paidMinor' => $paidMinor,
                'idempotencyKey' => $request->idempotency_key,
                'metadata' => $this->metadata($request),
            ];
        });

        if ($processing['validationError'] ?? false) {
            throw ValidationException::withMessages([
                'final_refund' => 'The refund cannot exceed the provider-confirmed remaining balance.',
            ]);
        }

        if ($processing['amountMinor'] === 0) {
            return $this->finalize($processing['requestId'], null, $processing['providerRefundedMinor']);
        }

        return $this->callProviderAndFinalize($processing);
    }

    /** @param array<string, mixed> $processing */
    private function callProviderAndFinalize(array $processing): CancellationRequest
    {
        try {
            $refund = $this->gateway->refund(
                $processing['paymentIntentId'],
                $processing['amountMinor'],
                $processing['idempotencyKey'],
                $processing['metadata'],
            );
            $this->assertProviderResult($refund, $processing['amountMinor']);
        } catch (RefundGatewayException $exception) {
            $this->logProviderFailure(
                $processing['requestId'],
                $processing['orderId'],
                $processing['paymentIntentId'],
                $exception,
            );
            $this->recordFailure($processing['requestId'], $exception);

            throw $exception;
        }

        try {
            $authoritativeRefundedMinor = $this->gateway->refundedAmount($processing['paymentIntentId']);
            $minimumExpectedMinor = $processing['expectedCumulativeMinor'];
            if ($authoritativeRefundedMinor < $minimumExpectedMinor) {
                throw RefundGatewayException::indeterminate(
                    'refund_balance_not_confirmed',
                    "Provider cumulative refund {$authoritativeRefundedMinor} is below expected {$minimumExpectedMinor}.",
                );
            }
        } catch (RefundGatewayException $exception) {
            $this->logProviderFailure(
                $processing['requestId'],
                $processing['orderId'],
                $processing['paymentIntentId'],
                $exception,
            );
            $this->recordRefundConfirmationFailure($processing['requestId']);

            throw $exception;
        }

        $request = CancellationRequest::query()->findOrFail($processing['requestId']);
        $this->beforeFinalization($request, $refund);

        return $this->finalize(
            $processing['requestId'],
            $refund,
            $authoritativeRefundedMinor,
        );
    }

    private function finalize(int $requestId, ?object $refund, int $providerRefundedMinor): CancellationRequest
    {
        $request = DB::transaction(function () use ($requestId, $refund, $providerRefundedMinor): CancellationRequest {
            [$request, $order, $payment] = $this->lockWorkflow($requestId);
            if ($request->status !== CancellationRequest::STATUS_REFUND_PROCESSING) {
                throw new DomainException('This cancellation request cannot be finalized in its current state.');
            }

            $paidMinor = $this->parseStoredAmount($request->paid_amount, $request->currency);
            $providerRefundedMinor = min($paidMinor, max(0, $providerRefundedMinor));
            $this->reconcilePayment($payment, $providerRefundedMinor, $paidMinor, $request->currency);
            $decisionMinor = $this->parseStoredAmount($request->final_refund_amount ?? '0.00', $request->currency);

            $request->update([
                'status' => CancellationRequest::STATUS_APPROVED,
                'stripe_refund_id' => $refund?->id,
                'failure_code' => null,
                'failure_summary' => null,
                'failure_disposition' => null,
                'refund_outcome' => $decisionMinor === 0
                    ? 'no_refund'
                    : ($decisionMinor >= $paidMinor ? 'full' : 'partial'),
                'refund_completed_at' => now(),
            ]);
            $order->update(['status' => 'cancelled']);

            $request = $request->refresh();
            $this->notifications()->recordApproved($request);

            return $request;
        });

        return $request;
    }

    private function recordFailure(int $requestId, RefundGatewayException $exception): void
    {
        DB::transaction(function () use ($requestId, $exception): void {
            [$request] = $this->lockWorkflow($requestId);
            if ($request->status !== CancellationRequest::STATUS_REFUND_PROCESSING) {
                return;
            }

            $request->update([
                'status' => CancellationRequest::STATUS_REFUND_FAILED,
                'failure_code' => $exception->failureCode,
                'failure_summary' => $exception->safeSummary,
                'failure_disposition' => $exception->failureDisposition,
            ]);

            $this->notifications()->recordRefundFailed($request->refresh());
        });
    }

    /** @param array<string, mixed> $decision */
    private function recordProviderFailureBeforeProcessing(
        int $requestId,
        User $admin,
        array $decision,
        RefundGatewayException $exception,
    ): void {
        DB::transaction(function () use ($requestId, $admin, $decision, $exception): void {
            [$request] = $this->lockWorkflow($requestId);
            if ($request->status !== CancellationRequest::STATUS_PENDING) {
                return;
            }

            $paidMinor = $this->parseStoredAmount($request->paid_amount, $request->currency);
            $request->update([
                'status' => CancellationRequest::STATUS_REFUND_FAILED,
                'final_refund_amount' => $this->formatMinor($decision['amountMinor'], $request->currency),
                'final_deduction_amount' => $this->formatMinor(
                    max(0, $paidMinor - $decision['amountMinor']),
                    $request->currency,
                ),
                'decision_explanation' => $decision['explanation'],
                'decided_by' => $admin->id,
                'decided_at' => now(),
                'idempotency_key' => "cancel-request-{$request->id}",
                'failure_code' => $exception->failureCode,
                'failure_summary' => $exception->safeSummary,
                'failure_disposition' => $exception->failureDisposition,
            ]);

            $this->notifications()->recordRefundFailed($request->refresh());
        });
    }

    /** @return array{CancellationRequest, Order, OrderPayment} */
    private function lockWorkflow(int $requestId): array
    {
        $locator = CancellationRequest::query()->select('order_id')->findOrFail($requestId);
        $order = Order::query()->whereKey($locator->order_id)->lockForUpdate()->firstOrFail();
        $payment = OrderPayment::query()->where('order_id', $order->id)->lockForUpdate()->first();
        if (! $payment instanceof OrderPayment) {
            throw new DomainException('A refundable payment is required.');
        }
        $request = CancellationRequest::query()->whereKey($requestId)->lockForUpdate()->firstOrFail();

        return [$request, $order, $payment];
    }

    private function assertPending(CancellationRequest $request): void
    {
        if ($request->status !== CancellationRequest::STATUS_PENDING) {
            throw new DomainException('This cancellation request cannot be approved in its current state.');
        }
    }

    private function assertRetryable(CancellationRequest $request): void
    {
        if ($request->status === CancellationRequest::STATUS_REFUND_PROCESSING) {
            $staleAfter = (int) config('cancellation.refund_processing_stale_after_seconds', 300);
            if ($request->updated_at->copy()->addSeconds($staleAfter)->isFuture()) {
                throw new DomainException('This refund is still processing. Try again later.');
            }
        } elseif ($request->status !== CancellationRequest::STATUS_REFUND_FAILED) {
            throw new DomainException('This cancellation request cannot be retried in its current state.');
        }

        if ($request->final_refund_amount === null || blank($request->idempotency_key)) {
            throw new DomainException('This refund does not have a retryable decision.');
        }
    }

    private function assertExplanationForAdjustment(
        int $amountMinor,
        int $suggestedMinor,
        ?string $explanation,
    ): void {
        if ($amountMinor !== $suggestedMinor && trim((string) $explanation) === '') {
            throw ValidationException::withMessages([
                'explanation' => 'An explanation is required when adjusting the suggested refund.',
            ]);
        }
    }

    private function paymentIntentId(OrderPayment $payment): string
    {
        $paymentIntentId = trim((string) $payment->payment_intent_id);
        if ($paymentIntentId === '') {
            throw new DomainException('A refundable payment reference is required.');
        }

        return $paymentIntentId;
    }

    private function parseDecisionAmount(string $amount, string $currency): int
    {
        $exponent = $this->currencyExponent($currency);
        if (preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', trim($amount), $matches) !== 1) {
            throw ValidationException::withMessages(['final_refund' => 'Enter a valid refund amount.']);
        }

        $fraction = $matches[2] ?? '';
        if ($exponent === 0 && trim($fraction, '0') !== '') {
            throw ValidationException::withMessages([
                'final_refund' => 'This currency does not support fractional refund amounts.',
            ]);
        }

        return ((int) $matches[1] * (10 ** $exponent))
            + (int) str_pad(substr($fraction, 0, $exponent), $exponent, '0');
    }

    private function parseStoredAmount(string $amount, string $currency): int
    {
        return $this->parseDecisionAmount($amount, $currency);
    }

    private function currencyExponent(string $currency): int
    {
        return in_array(strtoupper($currency), [
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX',
            'VND', 'VUV', 'XAF', 'XOF', 'XPF',
        ], true) ? 0 : 2;
    }

    private function formatMinor(int $minor, string $currency): string
    {
        $exponent = $this->currencyExponent($currency);
        if ($exponent === 0) {
            return $minor.'.00';
        }

        return intdiv($minor, 100).'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    private function reconcilePayment(
        OrderPayment $payment,
        int $refundedMinor,
        int $paidMinor,
        string $currency,
    ): void {
        $payment->update([
            'refunded_amount' => $this->formatMinor($refundedMinor, $currency),
            'payment_status' => $refundedMinor <= 0
                ? 'paid'
                : ($refundedMinor >= $paidMinor ? 'refunded' : 'partially_refunded'),
        ]);
    }

    private function assertProviderResult(object $refund, int $expectedAmountMinor): void
    {
        if (blank($refund->id ?? null)
            || ($refund->status ?? null) !== 'succeeded'
            || ! isset($refund->amount)
            || (int) $refund->amount !== $expectedAmountMinor) {
            throw RefundGatewayException::indeterminate(
                'malformed_provider_response',
                'The provider returned an incomplete refund result.',
            );
        }
    }

    private function logProviderFailure(
        int $requestId,
        int $orderId,
        string $paymentIntentId,
        RefundGatewayException $exception,
    ): void {
        Log::warning('Cancellation refund provider failure.', [
            'cancellation_request_id' => $requestId,
            'order_id' => $orderId,
            'payment_intent_id' => $paymentIntentId,
            'failure_code' => $exception->failureCode,
            'failure_disposition' => $exception->failureDisposition,
            'safe_summary' => $exception->safeSummary,
            'exception_class' => $exception::class,
        ]);
    }

    /** @return array<string, int|string> */
    private function metadata(CancellationRequest $request): array
    {
        return [
            'cancellation_request_id' => $request->id,
            'order_id' => $request->order_id,
            'policy_version' => $request->policy_version,
            'suggested_deduction_percentage' => (string) $request->suggested_deduction_percentage,
        ];
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function beforeFinalization(CancellationRequest $request, object $refund): void
    {
        // Test seam for proving recovery after Stripe succeeds but local finalization fails.
    }

    public function reportLocalFailure(int $requestId): void
    {
        $this->recordExistingFailureAlert($requestId);
    }

    private function recordExistingFailureAlert(int $requestId): void
    {
        DB::transaction(function () use ($requestId): void {
            [$request] = $this->lockWorkflow($requestId);

            if ($request->status === CancellationRequest::STATUS_REFUND_FAILED) {
                $this->notifications()->recordRefundFailed($request);

                return;
            }

            if ($request->status === CancellationRequest::STATUS_REFUND_PROCESSING) {
                $this->notifications()->recordRefundConfirmationFailed($request);
            }
        });
    }

    private function recordRefundConfirmationFailure(int $requestId): void
    {
        DB::transaction(function () use ($requestId): void {
            [$request] = $this->lockWorkflow($requestId);
            if ($request->status !== CancellationRequest::STATUS_REFUND_PROCESSING) {
                return;
            }

            $this->notifications()->recordRefundConfirmationFailed($request);
        });
    }

    private function notifications(): CancellationNotificationService
    {
        return $this->notificationService;
    }
}
