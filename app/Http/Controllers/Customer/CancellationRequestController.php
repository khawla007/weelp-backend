<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCancellationRequest;
use App\Models\User;
use App\Services\CancellationRequestService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CancellationRequestController extends Controller
{
    public function __construct(private readonly CancellationRequestService $cancellations) {}

    public function quote(Request $request, int $order): JsonResponse
    {
        /** @var User $customer */
        $customer = $request->user();

        try {
            return response()->json([
                'quote' => $this->cancellations->quoteForCustomer($order, $customer->id),
            ]);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }
    }

    public function store(StoreCancellationRequest $request, int $order): JsonResponse
    {
        /** @var User $customer */
        $customer = $request->user();

        try {
            $cancellation = $this->cancellations->create(
                $order,
                $customer->id,
                $request->validated('reason'),
            );

            return response()->json([
                'cancellation' => $this->cancellations->transform($cancellation),
            ], 201);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }
    }
}
