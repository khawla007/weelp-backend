<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportRequest;
use App\Jobs\SendSupportRequestAlert;
use App\Jobs\SendSupportRequestReceipt;
use App\Models\SupportRequest;
use App\Models\User;
use App\Support\SupportItemResolver;
use App\Support\SupportReferenceGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;

class SupportRequestController extends Controller
{
    public function __construct(
        private readonly SupportItemResolver $itemResolver,
        private readonly SupportReferenceGenerator $referenceGenerator,
    ) {}

    public function store(StoreSupportRequest $request): JsonResponse|Response
    {
        if ($request->filled('website')) {
            return response()->noContent();
        }

        $validated = $request->validated();
        $item = $this->itemResolver->resolve(
            $validated['item_type'],
            (int) $validated['item_id'],
            $validated['item_slug'],
            $validated['city_slug'],
        );
        $user = $this->optionalUser($request);

        $supportRequest = DB::transaction(
            fn (): SupportRequest => $this->createOrReload(
                $validated,
                $item,
                $user,
            ),
        );

        if (! $this->hasSameIdentity($supportRequest, $validated)) {
            return response()->json([
                'success' => false,
                'message' => 'This request identifier has already been used.',
            ], Response::HTTP_CONFLICT);
        }

        if ($supportRequest->wasRecentlyCreated) {
            SendSupportRequestReceipt::dispatch($supportRequest->id)->afterCommit();
            SendSupportRequestAlert::dispatch($supportRequest->id)->afterCommit();
        }

        return response()->json([
            'success' => true,
            'message' => 'Your support request has been received.',
            'data' => [
                'reference' => $supportRequest->reference,
                'status' => $supportRequest->status,
            ],
        ], $supportRequest->wasRecentlyCreated
            ? Response::HTTP_CREATED
            : Response::HTTP_OK);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function createOrReload(array $validated, Model $item, ?User $user): SupportRequest
    {
        while (true) {
            $reference = $this->referenceGenerator->generate();

            try {
                return SupportRequest::query()->createOrFirst(
                    ['client_request_id' => $validated['client_request_id']],
                    [
                        'reference' => $reference,
                        'user_id' => $user?->id,
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                        'topic' => $validated['topic'],
                        'message' => $validated['message'],
                        'item_type' => $validated['item_type'],
                        'item_id' => $item->getKey(),
                        'item_title' => (string) $item->getAttribute('name'),
                        'city_slug' => $validated['city_slug'],
                        'item_slug' => $validated['item_slug'],
                        'page_url' => $validated['page_url'],
                        'status' => 'open',
                    ],
                );
            } catch (UniqueConstraintViolationException $exception) {
                if (! SupportRequest::query()->where('reference', $reference)->exists()) {
                    throw $exception;
                }
            }
        }
    }

    private function optionalUser(StoreSupportRequest $request): ?User
    {
        if (! $request->bearerToken()) {
            return null;
        }

        try {
            $user = auth('api')->user();
        } catch (JWTException) {
            return null;
        }

        return $user instanceof User ? $user : null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function hasSameIdentity(SupportRequest $supportRequest, array $validated): bool
    {
        return $supportRequest->email === $validated['email']
            && $supportRequest->item_type === $validated['item_type']
            && (int) $supportRequest->item_id === (int) $validated['item_id'];
    }
}
