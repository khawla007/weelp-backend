<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\RefundGatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DecideCancellationRequest;
use App\Models\CancellationRequest;
use App\Models\User;
use App\Services\CancellationRefundService;
use App\Services\CancellationRequestService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CancellationRequestController extends Controller
{
    public function __construct(
        private readonly CancellationRefundService $refunds,
        private readonly CancellationRequestService $cancellations,
    ) {}

    public function reject(
        DecideCancellationRequest $request,
        CancellationRequest $cancellationRequest,
    ): JsonResponse {
        /** @var User $admin */
        $admin = $request->user();

        try {
            $cancellation = $this->refunds->reject(
                $cancellationRequest->id,
                $admin,
                (string) $request->validated('explanation'),
            );

            return $this->success($cancellation);
        } catch (DomainException $exception) {
            return $this->conflict($exception);
        }
    }

    public function approve(
        DecideCancellationRequest $request,
        CancellationRequest $cancellationRequest,
    ): JsonResponse {
        /** @var User $admin */
        $admin = $request->user();

        try {
            $cancellation = $this->refunds->approve(
                $cancellationRequest->id,
                $admin,
                (string) $request->validated('final_refund'),
                $request->validated('explanation'),
            );

            return $this->success($cancellation);
        } catch (DomainException $exception) {
            return $this->conflict($exception);
        } catch (RefundGatewayException $exception) {
            return response()->json(['message' => $exception->safeSummary], 502);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Cancellation refund local finalization failed.', [
                'cancellation_request_id' => $cancellationRequest->id,
                'exception_class' => $exception::class,
            ]);
            $this->refunds->reportLocalFailure($cancellationRequest->id);

            return response()->json([
                'message' => 'The provider refund may have succeeded, but local confirmation failed. Retry this request.',
            ], 500);
        }
    }

    public function retry(Request $request, CancellationRequest $cancellationRequest): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        try {
            return $this->success($this->refunds->retry($cancellationRequest->id, $admin));
        } catch (DomainException $exception) {
            return $this->conflict($exception);
        } catch (RefundGatewayException $exception) {
            return response()->json(['message' => $exception->safeSummary], 502);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Cancellation refund retry finalization failed.', [
                'cancellation_request_id' => $cancellationRequest->id,
                'exception_class' => $exception::class,
            ]);
            $this->refunds->reportLocalFailure($cancellationRequest->id);

            return response()->json([
                'message' => 'The refund could not be confirmed locally. Retry this request.',
            ], 500);
        }
    }

    private function success(CancellationRequest $request): JsonResponse
    {
        return response()->json([
            'cancellation' => $this->cancellations->transform($request, admin: true),
        ]);
    }

    private function conflict(DomainException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 409);
    }
}
