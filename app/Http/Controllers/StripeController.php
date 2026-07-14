<?php

namespace App\Http\Controllers;

use App\Contracts\StripePaymentIntentGateway;
use App\Mail\AdminNewOrderMail;
use App\Mail\CustomerCancelledOrderMail;
use App\Mail\CustomerFailedOrderMail;
use App\Mail\CustomerProcessingOrderMail;
use App\Mail\CustomerRefundedOrderMail;
use App\Models\Activity;
use App\Models\Commission;
use App\Models\Itinerary;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Package;
use App\Models\Transfer;
use App\Models\User;
use App\Services\ActivityDiscountService;
use App\Services\CheckoutQuoteService;
use App\Services\PackagePricingService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class StripeController extends Controller
{
    public function initializePayment(
        Request $request,
        CheckoutQuoteService $quotes,
        StripePaymentIntentGateway $stripe,
    ) {
        $selection = $this->validateSelection($request);

        try {
            $quote = $quotes->quote($selection);
            $selectionHash = $this->selectionHash($selection);
            $intent = $stripe->create(
                $this->toSmallestUnit($quote['amount'], $quote['currency']),
                strtolower($quote['currency']),
                [
                    'user_id' => (string) $request->user()->id,
                    'selection_hash' => $selectionHash,
                ],
            );
        } catch (DomainException $e) {
            return $this->quoteError($e);
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Payment could not be initialized.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'clientSecret' => $intent->client_secret,
            'paymentIntent' => $intent->id,
            'quote' => $this->publicQuote($quote),
        ]);
    }

    public function createOrder(
        Request $request,
        CheckoutQuoteService $quotes,
        StripePaymentIntentGateway $stripe,
    ) {
        $selection = $this->validateSelection($request);
        $data = $request->validate([
            'payment_intent_id' => ['required', 'string', 'max:255'],
            'special_requirements' => ['nullable', 'string', 'max:5000'],
            'creator_id' => ['nullable', 'integer'],
            'emergency_contact.name' => ['required', 'string', 'max:200'],
            'emergency_contact.phone' => ['required', 'string', 'max:32'],
            'emergency_contact.relationship' => ['required', 'string', 'max:100'],
        ]);
        $user = $request->user();
        $selectionHash = $this->selectionHash($selection);
        $paymentIntentId = $data['payment_intent_id'];

        $existing = OrderPayment::with('order')->where('payment_intent_id', $paymentIntentId)->first();
        if ($existing) {
            return $this->idempotentOrderResponse($existing, $user->id, $selectionHash);
        }

        try {
            $quote = $quotes->quote($selection);
            $intent = $stripe->retrieve($paymentIntentId);
        } catch (DomainException $e) {
            return $this->quoteError($e);
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'error' => 'payment_intent_unavailable',
                'message' => 'Payment details could not be verified.',
            ], 422);
        }

        if (! $this->intentMatches($intent, $quote, $user->id, $selectionHash)) {
            return response()->json([
                'success' => false,
                'error' => 'payment_intent_mismatch',
                'message' => 'Payment details do not match this booking.',
            ], 422);
        }

        $class = $this->orderableClass($selection['order_type']);
        $creatorId = User::query()
            ->whereKey($data['creator_id'] ?? null)
            ->where('is_creator', true)
            ->value('id');

        try {
            $order = DB::transaction(function () use ($selection, $selectionHash, $quote, $data, $user, $class, $creatorId, $paymentIntentId): Order {
                $orderable = $class::findOrFail($selection['orderable_id']);
                $snapshot = $this->orderSnapshot($orderable, $selection, $quote, $selectionHash);
                $order = Order::create([
                    'user_id' => $user->id,
                    'creator_id' => $creatorId,
                    'orderable_type' => $class,
                    'orderable_id' => $selection['orderable_id'],
                    'variation_id' => $quote['variation_id'],
                    'travel_date' => $selection['travel_date'],
                    'preferred_time' => $selection['preferred_time'],
                    'number_of_adults' => $selection['number_of_adults'],
                    'number_of_children' => $selection['number_of_children'],
                    'special_requirements' => $data['special_requirements'] ?? null,
                    'item_snapshot_json' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                ]);
                $order->emergencyContact()->create([
                    'contact_name' => $data['emergency_contact']['name'],
                    'contact_phone' => $data['emergency_contact']['phone'],
                    'relationship' => $data['emergency_contact']['relationship'],
                ]);
                $order->payment()->create([
                    'payment_status' => 'pending',
                    'payment_method' => 'credit_card',
                    'amount' => $quote['amount'],
                    'is_custom_amount' => false,
                    'custom_amount' => null,
                    'total_amount' => $quote['amount'],
                    'currency' => $quote['currency'],
                    'payment_intent_id' => $paymentIntentId,
                ]);

                if ($creatorId) {
                    $rate = (float) config('services.creator.commission_rate', 10.00);
                    Commission::create([
                        'creator_id' => $creatorId,
                        'order_id' => $order->id,
                        'commission_rate' => $rate,
                        'commission_amount' => round($quote['amount'] * ($rate / 100), 2),
                        'status' => 'pending',
                    ]);
                    Notification::create([
                        'user_id' => $creatorId,
                        'type' => 'new_booking',
                        'title' => 'New Booking',
                        'message' => 'A new booking was placed.',
                        'data' => ['order_id' => $order->id],
                    ]);
                }

                return $order;
            }, 3);
        } catch (QueryException) {
            $existing = OrderPayment::with('order')->where('payment_intent_id', $paymentIntentId)->first();
            if ($existing) {
                return $this->idempotentOrderResponse($existing, $user->id, $selectionHash);
            }

            return response()->json([
                'success' => false,
                'error' => 'order_creation_failed',
                'message' => 'The order could not be created.',
            ], 500);
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'error' => 'order_creation_failed',
                'message' => 'The order could not be created.',
            ], 500);
        }

        return response()->json(['success' => true, 'order_id' => $order->id]);
    }

    private function validateSelection(Request $request): array
    {
        return $request->validate([
            'order_type' => ['required', 'in:activity,package,itinerary,transfer'],
            'orderable_id' => ['required', 'integer'],
            'travel_date' => ['required', 'date', 'after_or_equal:today'],
            'preferred_time' => ['required', 'string', 'max:100'],
            'number_of_adults' => ['required', 'integer', 'min:0'],
            'number_of_children' => ['required', 'integer', 'min:0'],
            'addon_ids' => ['sometimes', 'array'],
            'addon_ids.*' => ['integer'],
            'variation_id' => ['nullable', 'integer'],
            'bag_count' => ['nullable', 'integer', 'min:0'],
            'waiting_minutes' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function publicQuote(array $quote): array
    {
        return [
            'amount' => $quote['amount'],
            'currency' => $quote['currency'],
            'base_amount' => $quote['base_amount'],
            'addons' => $quote['addons'],
            'addons_amount' => $quote['addons_amount'],
        ];
    }

    private function selectionHash(array $selection): string
    {
        if (isset($selection['addon_ids'])) {
            $selection['addon_ids'] = array_values(array_unique(array_map('intval', $selection['addon_ids'])));
            sort($selection['addon_ids']);
        }

        return hash('sha256', json_encode($selection, JSON_THROW_ON_ERROR));
    }

    private function orderableClass(string $orderType): string
    {
        return [
            'activity' => \App\Models\Activity::class,
            'package' => \App\Models\Package::class,
            'itinerary' => \App\Models\Itinerary::class,
            'transfer' => \App\Models\Transfer::class,
        ][$orderType];
    }

    private function orderSnapshot(object $orderable, array $selection, array $quote, string $selectionHash): array
    {
        $snapshot = [
            'name' => $orderable->name,
            'slug' => $orderable->slug,
            'item_type' => $selection['order_type'],
            'addons' => $quote['addons'],
            'base_amount' => $quote['base_amount'],
            'addons_amount' => $quote['addons_amount'],
            'checkout_selection_hash' => $selectionHash,
        ];

        if ($orderable instanceof Activity || $orderable instanceof Package || $orderable instanceof Itinerary) {
            $orderable->loadMissing(['locations.city.state.country', 'mediaGallery.media']);
            $snapshot['location'] = $orderable->locations->map(fn ($location) => [
                'location_type' => $location->location_type ?? null,
                'city' => $location->city?->name,
                'state' => $location->city?->state?->name,
                'country' => $location->city?->state?->country?->name,
            ])->values()->all();
            $snapshot['media'] = $orderable->mediaGallery->map(fn ($gallery) => [
                'id' => $gallery->media?->id,
                'name' => $gallery->media?->name,
                'url' => $gallery->media?->url,
                'alt' => $gallery->media?->alt_text,
            ])->values()->all();
        }

        if ($orderable instanceof Transfer) {
            $orderable->loadMissing(['route.origin', 'route.destination', 'vendorRoutes', 'mediaGallery.media']);
            $snapshot += [
                'transfer_type' => $orderable->transfer_type,
                'vehicle_type' => $orderable->vendorRoutes?->vehicle_type,
                'inclusion' => $orderable->vendorRoutes?->inclusion,
                'route_name' => $orderable->route?->name,
                'origin_name' => $orderable->route?->origin?->name,
                'destination_name' => $orderable->route?->destination?->name,
                'transfer_quantities' => [
                    'bag_count' => (int) ($selection['bag_count'] ?? 0),
                    'waiting_minutes' => (int) ($selection['waiting_minutes'] ?? 0),
                ],
                'media' => $orderable->mediaGallery->map(fn ($gallery) => [
                    'id' => $gallery->media?->id,
                    'name' => $gallery->media?->name,
                    'url' => $gallery->media?->url,
                    'alt' => $gallery->media?->alt_text,
                ])->values()->all(),
            ];
        }

        return $snapshot;
    }

    private function intentMatches(object $intent, array $quote, int $userId, string $selectionHash): bool
    {
        $metadata = $intent->metadata ?? null;
        $metadataUser = is_array($metadata) ? ($metadata['user_id'] ?? null) : ($metadata->user_id ?? null);
        $metadataHash = is_array($metadata) ? ($metadata['selection_hash'] ?? null) : ($metadata->selection_hash ?? null);
        $allowedStatuses = ['requires_payment_method', 'requires_confirmation', 'requires_action', 'processing', 'succeeded'];

        return (int) ($intent->amount ?? -1) === $this->toSmallestUnit($quote['amount'], $quote['currency'])
            && strtolower((string) ($intent->currency ?? '')) === strtolower($quote['currency'])
            && (string) $metadataUser === (string) $userId
            && hash_equals($selectionHash, (string) $metadataHash)
            && in_array((string) ($intent->status ?? ''), $allowedStatuses, true);
    }

    private function toSmallestUnit(float $amount, string $currency): int
    {
        $zeroDecimalCurrencies = [
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA',
            'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
        ];
        $multiplier = in_array(strtoupper($currency), $zeroDecimalCurrencies, true) ? 1 : 100;

        return (int) round($amount * $multiplier);
    }

    private function stripeTestSecret(): string
    {
        $secret = (string) config('services.stripe.secret');
        if (! str_starts_with($secret, 'sk_test_')) {
            throw new \RuntimeException('Stripe test mode is required for checkout operations.');
        }

        return $secret;
    }

    private function idempotentOrderResponse(OrderPayment $payment, int $userId, string $selectionHash)
    {
        $snapshot = json_decode((string) $payment->order?->item_snapshot_json, true);
        $storedHash = is_array($snapshot) ? ($snapshot['checkout_selection_hash'] ?? null) : null;
        if (! $payment->order || $payment->order->user_id !== $userId || ! is_string($storedHash) || ! hash_equals($storedHash, $selectionHash)) {
            return response()->json([
                'success' => false,
                'error' => 'payment_intent_mismatch',
                'message' => 'Payment details do not match this booking.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'order_id' => $payment->order->id,
            'idempotent' => true,
        ]);
    }

    private function quoteError(DomainException $exception)
    {
        $code = $exception->getMessage();
        $status = $code === 'item_unavailable' ? 404 : 422;

        return response()->json([
            'success' => false,
            'error' => $code,
            'message' => $status === 404
                ? 'The selected item is no longer available.'
                : 'The booking selection could not be quoted.',
        ], $status);
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (! $webhookSecret || ! $sigHeader) {
            Log::error('Stripe webhook rejected: missing secret or Stripe-Signature header');

            return response('Webhook secret not configured', 500);
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed: '.$e->getMessage());

            return response('Invalid signature', 400);
        }

        $eventId = $event->id ?? null;

        if ($eventId && DB::table('stripe_webhook_events')->where('id', $eventId)->exists()) {
            return response()->json(['already_processed' => true], 200);
        }

        try {
            DB::transaction(function () use ($event, $eventId): void {
                if ($eventId) {
                    DB::table('stripe_webhook_events')->insert([
                        'id' => $eventId,
                        'type' => $event->type,
                        'processed_at' => now(),
                    ]);
                }

                $this->applyStripeEvent($event);
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' && $eventId && DB::table('stripe_webhook_events')->where('id', $eventId)->exists()) {
                return response()->json(['already_processed' => true], 200);
            }

            throw $e;
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'payment_record_not_found') {
                return response()->json(['error' => 'Payment record not found'], 404);
            }

            throw $e;
        }

        return response('Webhook Handled', 200);
    }

    private function applyStripeEvent(object $event): void
    {
        if (! in_array($event->type, ['payment_intent.succeeded', 'payment_intent.payment_failed', 'charge.refunded', 'payment_intent.canceled'], true)) {
            return;
        }

        $intentId = $event->type === 'charge.refunded'
            ? $event->data->object->payment_intent
            : $event->data->object->id;
        $payment = OrderPayment::where('payment_intent_id', $intentId)->first();
        if (! $payment) {
            throw new \RuntimeException('payment_record_not_found');
        }

        $order = Order::with(['user', 'emergencyContact', 'payment'])->find($payment->order_id);
        if (! $order) {
            throw new \RuntimeException('payment_record_not_found');
        }

        if ($event->type === 'payment_intent.succeeded') {
            $payment->update(['payment_status' => 'paid']);
            $order->update(['status' => 'processing']);
            Mail::to($order->user->email)->send(new CustomerProcessingOrderMail($order));
            Mail::to(config('mail.admin_address', 'khawla@fanaticcoders.com'))->send(new AdminNewOrderMail($order));

            return;
        }

        if ($event->type === 'payment_intent.payment_failed') {
            $payment->update(['payment_status' => 'failed']);
            $order->update(['status' => 'failed']);
            Mail::to($order->user->email)->send(new CustomerFailedOrderMail($order));

            return;
        }

        if ($event->type === 'charge.refunded') {
            $payment->update(['payment_status' => 'refunded']);
            $order->update(['status' => 'refunded']);
            Mail::to($order->user->email)->send(new CustomerRefundedOrderMail($order));

            return;
        }

        $payment->update(['payment_status' => 'cancelled']);
        $order->update(['status' => 'cancelled']);
        Mail::to($order->user->email)->send(new CustomerCancelledOrderMail($order));
    }

    // thanku page get order details api
    public function getOrderByPaymentIntent(Request $request)
    {
        $paymentIntentId = $request->query('payment_intent');

        if (! $paymentIntentId) {
            return response()->json(['error' => 'Payment Intent ID is required'], 400);
        }

        $payment = OrderPayment::where('payment_intent_id', $paymentIntentId)->first();

        if (! $payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        $order = Order::with(['payment', 'emergencyContact'])->find($payment->order_id);

        if (! $order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($order->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $user = $order->user ?? null;
        $userProfile = $user?->profile;

        // ✅ Load from snapshot if orderable is missing
        $snapshot = is_array($order->item_snapshot_json)
            ? $order->item_snapshot_json
            : json_decode($order->item_snapshot_json, true);

        $media = collect($snapshot['media'] ?? [])->map(fn ($mediaLink) => [
            'name' => null,
            'alt_text' => $mediaLink['alt'] ?? null,
            'url' => $mediaLink['url'] ?? null,
        ]);

        $locations = $snapshot['location'] ?? [];
        $cityName = $locations[0]['city'] ?? null;
        $countryId = null;

        if (! empty($locations[0]['country'])) {
            $countryId = \App\Models\Country::where('name', $locations[0]['country'])->value('id');
        }

        $region = $countryId
            ? \App\Models\Region::whereHas('countries', fn ($q) => $q->where('countries.id', $countryId))->first()
            : null;

        $orderType = $order->orderable_type ? strtolower(class_basename($order->orderable_type)) : null;

        $response = [
            'id' => $order->id,
            'item_id' => $order->orderable_id,
            'order_type' => $orderType,
            'status' => $order->status,
            'travel_date' => $order->travel_date,
            'preferred_time' => $order->preferred_time,
            'number_of_adults' => $order->number_of_adults,
            'number_of_children' => $order->number_of_children,
            'special_requirements' => $order->special_requirements,
            'payment' => $order->payment,
            'emergency_contact' => $order->emergencyContact,
            'item' => [
                'name' => $snapshot['name'] ?? null,
                'slug' => $snapshot['slug'] ?? null,
                'item_type' => $snapshot['item_type'] ?? null,
                'city' => $cityName,
                'region' => $region?->name,
                'locations' => $snapshot['location'] ?? null,
                'media' => $media,
                // Transfer-specific fields (null for other types)
                'transfer_type' => $snapshot['transfer_type'] ?? null,
                'vehicle_type' => $snapshot['vehicle_type'] ?? null,
                'inclusion' => $snapshot['inclusion'] ?? null,
                'route_name' => $snapshot['route_name'] ?? null,
                'origin_name' => $snapshot['origin_name'] ?? null,
                'destination_name' => $snapshot['destination_name'] ?? null,
            ],
            'addons' => $snapshot['addons'] ?? [],
            'base_amount' => $snapshot['base_amount'] ?? null,
            'addons_amount' => $snapshot['addons_amount'] ?? 0,
            'user' => [
                'name' => $user?->name,
                'email' => $user?->email,
                'phone' => $userProfile?->phone,
            ],
        ];

        return response()->json([
            'success' => true,
            'order' => $response,
        ]);
    }

    // old stripe code

    public function createCheckoutSession(Request $request)
    {
        // ✅ Validate input
        $data = $request->validate([
            'order_type' => 'required|in:activity,package,itinerary',
            'orderable_id' => 'required|integer',
            'travel_date' => 'required|date',
            'preferred_time' => 'required',
            'number_of_adults' => 'required|integer',
            'number_of_children' => 'required|integer',
            'special_requirements' => 'nullable|string|max:5000',
            'customer_email' => 'required|email',
            'currency' => 'required|string|max:8',
            'emergency_contact.name' => 'required|string|max:200',
            'emergency_contact.phone' => 'required|string|max:32',
            'emergency_contact.relationship' => 'required|string|max:100',
            'variation_id' => 'nullable|integer',
        ]);

        $userId = $request->user()->id;

        // ✅ Determine the orderable model via allow-list
        $orderableMap = [
            'activity' => \App\Models\Activity::class,
            'package' => \App\Models\Package::class,
            'itinerary' => \App\Models\Itinerary::class,
        ];
        $orderableClass = $orderableMap[$data['order_type']];
        $orderable = $orderableClass::findOrFail($data['orderable_id']);

        // ✅ Server-side total: never trust client.
        // TODO: replace with dedicated price services per type (currently
        // mirrors confirmPayment's read paths).
        $totalAmount = 0.0;
        $packageVariationId = null;
        if ($orderable instanceof \App\Models\Activity) {
            $orderable->loadMissing(['pricing', 'earlyBirdDiscount', 'lastMinuteDiscount']);
            $headcount = max(1, (int) $data['number_of_adults'] + (int) $data['number_of_children']);
            try {
                $service = app(ActivityDiscountService::class);
                $travelDate = ! empty($data['travel_date'])
                    ? \Carbon\CarbonImmutable::parse($data['travel_date'])
                    : null;
                $totalAmount = (float) $service->quote($orderable, $headcount, $travelDate)['final_amount'];
            } catch (\RuntimeException $e) {
                return response()->json(['error' => 'activity_pricing_missing'], 422);
            }
        } elseif ($orderable instanceof \App\Models\Itinerary) {
            $totalAmount = (float) $orderable->priceForGuests(
                (int) $data['number_of_adults'],
                (int) $data['number_of_children']
            );
        } elseif ($orderable instanceof \App\Models\Package) {
            try {
                $pricingService = app(PackagePricingService::class);
                $variationModel = $pricingService->resolveVariationFor(
                    $orderable,
                    isset($data['variation_id']) ? (int) $data['variation_id'] : null,
                );
                $packageVariationId = $variationModel->id;

                $totalAmount = $pricingService->priceFor(
                    $orderable,
                    $packageVariationId,
                    \Carbon\CarbonImmutable::parse($data['travel_date']),
                    (int) $data['number_of_adults'],
                    (int) $data['number_of_children'],
                    0,
                );
            } catch (\DomainException $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }

        if ($totalAmount <= 0) {
            return response()->json(['error' => 'amount_unresolved'], 422);
        }
        $totalAmount = round($totalAmount, 2);

        // ✅ Check if existing pending order with same data already exists
        $existingOrder = Order::where('user_id', $userId)
            ->where('orderable_type', $orderableClass)
            ->where('orderable_id', $data['orderable_id'])
            ->where('travel_date', $data['travel_date'])
            ->where('preferred_time', $data['preferred_time'])
            ->whereHas('payment', function ($q) use ($totalAmount) {
                $q->where('payment_status', 'pending')
                    ->where('total_amount', $totalAmount); // ensure same amount too
            })
            ->latest()
            ->first();

        if ($existingOrder) {
            return response()->json([
                'id' => $existingOrder->payment->stripe_session_id,
                'url' => 'https://checkout.stripe.com/pay/'.$existingOrder->payment->stripe_session_id,
            ]);
        }

        // ✅ Create Order
        $order = Order::create([
            'user_id' => $userId,
            'orderable_type' => $orderableClass,
            'orderable_id' => $data['orderable_id'],
            'variation_id' => $packageVariationId,
            'travel_date' => $data['travel_date'],
            'preferred_time' => $data['preferred_time'],
            'number_of_adults' => $data['number_of_adults'],
            'number_of_children' => $data['number_of_children'],
            'special_requirements' => $data['special_requirements'],
        ]);

        // ✅ Save emergency contact
        $order->emergencyContact()->create([
            'contact_name' => $data['emergency_contact']['name'],
            'contact_phone' => $data['emergency_contact']['phone'],
            'relationship' => $data['emergency_contact']['relationship'],
        ]);

        // ✅ Prepare and save snapshot
        if ($orderable instanceof \App\Models\Activity) {
            $snapshot = [
                'name' => $orderable->name,
                'slug' => $orderable->slug,
                'item_type' => $orderable->item_type,
                'location' => $orderable->locations->map(function ($loc) {
                    return [
                        'location_type' => $loc->location_type,
                        'city' => $loc->city?->name,
                        'state' => $loc->city?->state?->name,
                        'country' => $loc->city?->state?->country?->name,
                    ];
                }),
                'pricing' => $orderable->pricings,
                'coupons_applied' => $order->applied_coupons ?? [],
                'media' => $orderable->mediaGallery->map(function ($mg) {
                    return [
                        'url' => $mg->media?->url,
                        'alt' => $mg->media?->alt_text,
                    ];
                }),
            ];
        } elseif ($orderable instanceof \App\Models\Package || $orderable instanceof \App\Models\Itinerary) {
            $snapshot = [
                'name' => $orderable->name,
                'slug' => $orderable->slug,
                'locations' => $orderable->locations->map(function ($loc) {
                    return [
                        'city' => $loc->city?->name,
                        'state' => $loc->city?->state?->name,
                        'country' => $loc->city?->state?->country?->name,
                    ];
                }),
                'schedules' => $orderable->schedules,
                'pricing' => $orderable->basePricing->variations ?? [],
                'coupons_applied' => $order->applied_coupons ?? [],
                'media' => $orderable->mediaGallery->map(function ($mg) {
                    return [
                        'url' => $mg->media?->url,
                        'alt' => $mg->media?->alt_text,
                    ];
                }),
            ];
        }

        if (isset($snapshot)) {
            $order->item_snapshot_json = json_encode(collect($snapshot)->toArray());
            $order->save();
        }

        // ✅ Setup Stripe
        Stripe::setApiKey($this->stripeTestSecret());

        $checkoutSession = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $data['currency'],
                    'product_data' => [
                        'name' => 'Trip Booking for '.$data['travel_date'],
                    ],
                    'unit_amount' => $totalAmount * 100, // in cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'customer_email' => $data['customer_email'],
            'success_url' => env('FRONTEND_URL').'/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => env('FRONTEND_URL').'/checkout',
        ]);

        // ✅ Save payment record
        $order->payment()->create([
            'payment_status' => 'pending',
            'payment_method' => 'credit_card',
            'amount' => $totalAmount,
            'is_custom_amount' => false,
            'custom_amount' => null,
            'total_amount' => $totalAmount,
            'currency' => $data['currency'],
            'stripe_session_id' => $checkoutSession->id,
        ]);

        return response()->json([
            'id' => $checkoutSession->id,
            'url' => $checkoutSession->url,
        ]);
    }

    public function confirmPayment(Request $request)
    {
        $data = $request->validate([
            'session_id' => ['required', 'string', 'max:255'],
        ]);
        $sessionId = $data['session_id'];
        $payment = OrderPayment::with(['order.emergencyContact', 'order.payment'])
            ->where('stripe_session_id', $sessionId)
            ->whereHas('order', fn ($query) => $query->where('user_id', $request->user()->id))
            ->first();

        if (! $payment || ! $payment->order) {
            return response()->json(['error' => 'Payment confirmation not found'], 404);
        }

        try {
            Stripe::setApiKey($this->stripeTestSecret());
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return response()->json(['error' => 'Payment not completed'], 400);
            }

            // Update payment status
            $payment->update([
                'payment_status' => 'paid',
            ]);

            // Fetch associated order
            // $order = Order::find($payment->order_id);
            $order = $payment->order;

            if ($payment->fresh()->payment_status === 'paid' && $order) {
                $order->update([
                    'status' => 'processing',
                ]);
            }

            $user = User::find($order->user_id);

            // Get item name and price based on orderable_type
            $orderableType = class_basename($order->orderable_type);
            $orderableId = $order->orderable_id;

            $itemName = null;
            $itemPrice = null;

            switch ($orderableType) {
                case 'Activity':
                    $activity = \App\Models\Activity::with('pricing')->where('id', $orderableId)->first();
                    $itemName = $activity?->name;
                    $itemPrice = $activity?->pricing?->regular_price;
                    break;

                case 'Package':
                    $package = \App\Models\Package::with('basePricing.variations')->where('id', $orderableId)->first();
                    $itemName = $package?->name;
                    $itemPrice = $package?->basePricing?->variations?->first()?->regular_price;
                    break;

                case 'Itinerary':
                    $itinerary = \App\Models\Itinerary::with('schedules.activities', 'schedules.transfers')
                        ->where('id', $orderableId)->first();
                    $itemName = $itinerary?->name;
                    $itemPrice = $itinerary?->schedule_total_price;
                    break;
            }

            $itemDetail = [
                'item_name' => $itemName,
                'item_price' => $itemPrice,
                'total_paid' => $payment->total_amount,
            ];

            $userDetail = [
                'name' => $user?->name,
                'email' => $user?->email,
            ];

            $orderDetail = [
                'user_detail' => $userDetail,
                'item_detail' => $itemDetail,
                'order' => $order->fresh(['payment', 'emergencyContact']),
            ];

            return response()->json([
                'success' => true,
                'data' => $orderDetail,
            ]);

        } catch (\Throwable) {
            return response()->json(['error' => 'Payment confirmation failed'], 500);
        }
    }
}
