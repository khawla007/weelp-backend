<?php

namespace App\Http\Controllers;

use App\Mail\AdminNewOrderMail;
use App\Mail\CustomerCancelledOrderMail;
use App\Mail\CustomerFailedOrderMail;
use App\Mail\CustomerProcessingOrderMail;
use App\Mail\CustomerRefundedOrderMail;
use App\Models\Commission;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use App\Services\ActivityDiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class StripeController extends Controller
{
    public function createOrder(Request $request)
    {
        $data = $request->validate([
            'order_type' => 'required|string',
            'orderable_id' => 'required|integer',
            'travel_date' => 'required|date',
            'preferred_time' => 'required',
            'number_of_adults' => 'required|integer',
            'number_of_children' => 'required|integer',
            'special_requirements' => 'nullable|string',
            'user_id' => 'required|integer',
            'customer_email' => 'required|email',
            'amount' => 'required|numeric|min:0',
            'is_custom_amount' => 'required|boolean',
            'custom_amount' => 'nullable|numeric|min:0|required_if:is_custom_amount,true',
            'currency' => 'required|string',
            'payment_intent_id' => 'required|string',
            'emergency_contact.name' => 'required|string',
            'emergency_contact.phone' => 'required|string',
            'emergency_contact.relationship' => 'required|string',
            'addons' => 'nullable|array',
            'addons.*.addon_id' => 'required_with:addons|integer',
            'addons.*.addon_name' => 'required_with:addons|string',
            'addons.*.price' => 'required_with:addons|numeric',
            'base_amount' => 'nullable|numeric',
            'creator_id' => 'nullable|integer',
            'addons_amount' => 'nullable|numeric',
            'bag_count' => 'nullable|integer|min:0',
            'waiting_minutes' => 'nullable|integer|min:0',
        ]);

        $orderableClass = 'App\\Models\\'.ucfirst($data['order_type']);
        if (! class_exists($orderableClass)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid order type.',
            ], 422);
        }
        $orderable = $orderableClass::find($data['orderable_id']);
        if (! $orderable) {
            return response()->json([
                'success' => false,
                'message' => 'The selected item is no longer available.',
            ], 404);
        }
        $totalAmount = $data['is_custom_amount'] ? $data['custom_amount'] : $data['amount'];

        if ($orderable instanceof \App\Models\Activity) {
            $adults = (int) $data['number_of_adults'];
            $children = (int) $data['number_of_children'];
            $headcount = $adults + $children;

            if ($headcount === 0) {
                return response()->json([
                    'error' => 'invalid_headcount',
                    'message' => 'At least one adult or child is required.',
                ], 422);
            }

            // Require explicit base_amount so addon-inclusive `amount` can't bypass the check.
            if (! array_key_exists('base_amount', $data) || $data['base_amount'] === null) {
                return response()->json([
                    'error' => 'base_amount_required',
                    'message' => 'base_amount is required for activity orders.',
                ], 422);
            }

            // Eager-load EB/LM relations to avoid N+1 queries in service.
            $orderable->loadMissing(['pricing', 'groupDiscounts', 'earlyBirdDiscount', 'lastMinuteDiscount']);

            $submittedBaseAmount = (float) $data['base_amount'];

            // Re-validate: fetch expected price from service, using travel_date for EB/LM recompute.
            try {
                $service = app(ActivityDiscountService::class);
                $travelDate = !empty($data['travel_date'])
                    ? \Carbon\CarbonImmutable::parse($data['travel_date'])
                    : null;
                $quote = $service->quote($orderable, $headcount, $travelDate);
                $expectedBaseAmount = (float) $quote['final_amount'];
            } catch (\RuntimeException $e) {
                return response()->json([
                    'error' => 'activity_pricing_missing',
                    'message' => 'Activity pricing is not available.',
                ], 422);
            }

            // Tolerance: 0.01 (one cent)
            $tolerance = 0.01;
            if (abs($submittedBaseAmount - $expectedBaseAmount) > $tolerance) {
                return response()->json([
                    'error' => 'activity_price_mismatch',
                    'expected' => $expectedBaseAmount,
                    'submitted' => $submittedBaseAmount,
                ], 422);
            }
        }

        // Server-side enforcement: itineraries charge the sum of their schedule items,
        // ignoring any client-supplied amount. Prevents tampering.
        if ($orderable instanceof \App\Models\Itinerary) {
            $orderable->loadMissing('schedules.activities', 'schedules.transfers');
            $totalAmount = (float) $orderable->schedule_total_price;
        }

        // Server-side enforcement: transfers recompute final amount from DB rates ×
        // posted bag/minute quantities. Client-supplied amount is not trusted.
        $transferQuantities = null;
        if ($orderable instanceof \App\Models\Transfer) {
            $orderable->loadMissing('pricingAvailability', 'route');

            $bagCount = (int) ($data['bag_count'] ?? 0);
            $waitingMinutes = (int) ($data['waiting_minutes'] ?? 0);
            $headcount = max(1, (int) $data['number_of_adults'] + (int) $data['number_of_children']);

            $routePrice = $orderable->computeRoutePrice($headcount);
            $luggageRate = $orderable->luggagePerBagRate();
            $waitingRate = $orderable->waitingPerMinuteRate();

            $luggageAmount = round($luggageRate * $bagCount, 2);
            $waitingAmount = round($waitingRate * $waitingMinutes, 2);
            $expected = round($routePrice + $luggageAmount + $waitingAmount, 2);

            $submitted = (float) $totalAmount;
            if (abs($submitted - $expected) > 0.01) {
                return response()->json([
                    'error' => 'transfer_price_mismatch',
                    'expected' => $expected,
                    'submitted' => $submitted,
                ], 422);
            }

            $totalAmount = $expected;
            // Keep `payment.amount` and `snapshot.base_amount` consistent with the
            // server-recomputed total for transfers. Otherwise the order record can
            // carry the original (potentially tampered) client value.
            $data['amount'] = $expected;
            $data['base_amount'] = $routePrice;
            $data['addons_amount'] = round($luggageAmount + $waitingAmount, 2);

            $transferQuantities = [
                'bag_count' => $bagCount,
                'waiting_minutes' => $waitingMinutes,
                'headcount' => $headcount,
                'price_type' => $orderable->pricingPriceType(),
                'luggage_per_bag_rate' => $luggageRate,
                'waiting_per_minute_rate' => $waitingRate,
                'luggage_amount' => $luggageAmount,
                'waiting_amount' => $waitingAmount,
                'base_amount' => $routePrice,
            ];
        }

        // Validate creator if provided
        $creatorId = null;
        if (! empty($data['creator_id'])) {
            $creator = User::where('id', $data['creator_id'])->where('is_creator', true)->first();
            if ($creator) {
                $creatorId = $creator->id;
            }
        }

        // ✅ Create order
        $order = Order::create([
            'user_id' => $data['user_id'],
            'creator_id' => $creatorId,
            'orderable_type' => $orderableClass,
            'orderable_id' => $data['orderable_id'],
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

        // ✅ Snapshot (optional but useful)
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
                        'id' => $mg->media?->id,
                        'name' => $mg->media?->name,
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
                'pricing' => $orderable->basePricing->priceVariations ?? [],
                'coupons_applied' => $order->applied_coupons ?? [],
                'media' => $orderable->mediaGallery->map(function ($mg) {
                    return [
                        'url' => $mg->media?->url,
                        'alt' => $mg->media?->alt_text,
                    ];
                }),
            ];
        } elseif ($orderable instanceof \App\Models\Transfer) {
            $orderable->loadMissing('vendorRoutes.route.origin', 'vendorRoutes.route.destination', 'pricingAvailability', 'mediaGallery.media');
            $route = $orderable->vendorRoutes?->route;
            $snapshot = [
                'name' => $orderable->name,
                'slug' => $orderable->slug,
                'item_type' => 'transfer',
                'transfer_type' => $orderable->transfer_type,
                'vehicle_type' => $orderable->vendorRoutes?->vehicle_type,
                'inclusion' => $orderable->vendorRoutes?->inclusion,
                'route_name' => $route?->name,
                'origin_name' => $route?->origin?->name,
                'destination_name' => $route?->destination?->name,
                'pricing' => $orderable->pricingAvailability,
                'coupons_applied' => $order->applied_coupons ?? [],
                'media' => $orderable->mediaGallery->map(function ($mg) {
                    return [
                        'url' => $mg->media?->url,
                        'alt' => $mg->media?->alt_text,
                    ];
                }),
                // Capture per-unit semantics so the snapshot remains forensically clear
                // even after column meanings drift in future schema work.
                'transfer_quantities' => $transferQuantities,
            ];
        }

        if (isset($snapshot)) {
            $snapshot['addons'] = $data['addons'] ?? [];
            $snapshot['base_amount'] = $data['base_amount'] ?? $data['amount'];
            $snapshot['addons_amount'] = $data['addons_amount'] ?? 0;
            $order->item_snapshot_json = json_encode(collect($snapshot)->toArray());
            $order->save();
        }

        // ✅ Save payment info (based on PaymentIntent, not session)
        $order->payment()->create([
            'payment_status' => 'pending',
            'payment_method' => 'credit_card',
            'amount' => $data['amount'],
            'is_custom_amount' => $data['is_custom_amount'],
            'custom_amount' => $data['custom_amount'],
            'total_amount' => $totalAmount,
            'currency' => $data['currency'],
            'payment_intent_id' => $data['payment_intent_id'],
        ]);

        // Create affiliate commission if order is from a creator referral
        if ($creatorId) {
            $commissionRate = config('services.creator.commission_rate', 10.00);
            Commission::create([
                'creator_id' => $creatorId,
                'order_id' => $order->id,
                'commission_rate' => $commissionRate,
                'commission_amount' => round($totalAmount * ($commissionRate / 100), 2),
                'status' => 'pending',
            ]);

            Notification::create([
                'user_id' => $creatorId,
                'type' => 'new_booking',
                'title' => 'New Booking',
                'message' => "Someone booked your itinerary! Order total: {$data['currency']} {$totalAmount}",
                'data' => ['order_id' => $order->id],
            ]);
        }

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
        ]);
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');

        if ($webhookSecret && $sigHeader) {
            try {
                $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                Log::error('Stripe webhook signature verification failed: '.$e->getMessage());

                return response('Invalid signature', 400);
            }
        } else {
            $event = json_decode($payload);
        }

        if ($event->type == 'payment_intent.succeeded') {
            $intent_id = $event->data->object->id;

            $payment = OrderPayment::where('payment_intent_id', $intent_id)->first();

            if (! $payment) {
                return response()->json(['error' => 'Payment record not found'], 404);
            }

            // Update payment status
            $payment->update([
                'payment_status' => 'paid',
            ]);

            $order = Order::with(['user', 'emergencyContact', 'payment'])->find($payment->order_id);

            if ($payment->fresh()->payment_status === 'paid' && $order) {

                $order->update([
                    'status' => 'processing',
                ]);

                // Send customer mail
                Mail::to($order->user->email)->send(new CustomerProcessingOrderMail($order));
                // Send admin mail
                Mail::to(config('mail.admin_address', 'khawla@fanaticcoders.com'))->send(new AdminNewOrderMail($order));

            }
        }

        // ❌ Payment failed
        elseif ($event->type == 'payment_intent.payment_failed') {
            $intent_id = $event->data->object->id;

            $payment = OrderPayment::where('payment_intent_id', $intent_id)->first();
            if ($payment) {
                $payment->update(['payment_status' => 'failed']);
                $order = Order::with(['user'])->find($payment->order_id);

                if ($order) {
                    $order->update(['status' => 'failed']);

                    // Customer mail
                    Mail::to($order->user->email)->send(new CustomerFailedOrderMail($order));
                }
            }
        }

        // 💸 Refunded
        elseif ($event->type == 'charge.refunded') {
            $intent_id = $event->data->object->payment_intent;

            $payment = OrderPayment::where('payment_intent_id', $intent_id)->first();
            if ($payment) {
                $payment->update(['payment_status' => 'refunded']);
                $order = Order::with(['user'])->find($payment->order_id);

                if ($order) {
                    $order->update(['status' => 'refunded']);

                    // Customer mail
                    Mail::to($order->user->email)->send(new CustomerRefundedOrderMail($order));
                }
            }
        }

        // ❌ Payment canceled
        elseif ($event->type == 'payment_intent.canceled') {
            $intent_id = $event->data->object->id;

            $payment = OrderPayment::where('payment_intent_id', $intent_id)->first();
            if ($payment) {
                $payment->update(['payment_status' => 'cancelled']);
                $order = Order::with(['user'])->find($payment->order_id);

                if ($order) {
                    $order->update(['status' => 'cancelled']);

                    // Customer mail
                    Mail::to($order->user->email)->send(new CustomerCancelledOrderMail($order));
                }
            }
        }

        return response('Webhook Handled', 200);
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
            'order_type' => 'required|string',
            'orderable_id' => 'required|integer',
            'travel_date' => 'required|date',
            'preferred_time' => 'required',
            'number_of_adults' => 'required|integer',
            'number_of_children' => 'required|integer',
            'special_requirements' => 'nullable|string',
            'user_id' => 'required|integer',
            'customer_email' => 'required|email',
            'amount' => 'required|numeric|min:0',
            'is_custom_amount' => 'required|boolean',
            'custom_amount' => 'nullable|numeric|min:0|required_if:is_custom_amount,true',
            'currency' => 'required|string',
            'emergency_contact.name' => 'required|string',
            'emergency_contact.phone' => 'required|string',
            'emergency_contact.relationship' => 'required|string',
        ]);

        // ✅ Determine the orderable model
        $orderableClass = 'App\\Models\\'.ucfirst($data['order_type']);
        $orderable = $orderableClass::findOrFail($data['orderable_id']);

        // ✅ Calculate total amount
        $totalAmount = $data['is_custom_amount'] ? $data['custom_amount'] : $data['amount'];

        // ✅ Check if existing pending order with same data already exists
        $existingOrder = Order::where('user_id', $data['user_id'])
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
            'user_id' => $data['user_id'],
            'orderable_type' => $orderableClass,
            'orderable_id' => $data['orderable_id'],
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
                'pricing' => $orderable->basePricing->priceVariations ?? [],
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
        Stripe::setApiKey(env('STRIPE_SECRET'));
        // \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

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
            'amount' => $data['amount'],
            'is_custom_amount' => $data['is_custom_amount'],
            'custom_amount' => $data['custom_amount'],
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
        Stripe::setApiKey(env('STRIPE_SECRET'));
        // \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $sessionId = $request->input('session_id');

        try {
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return response()->json(['error' => 'Payment not completed'], 400);
            }

            // Find the order_payment record using session_id
            $payment = OrderPayment::where('stripe_session_id', $sessionId)->first();

            if (! $payment) {
                return response()->json(['error' => 'Payment record not found'], 404);
            }

            // Update payment status
            $payment->update([
                'payment_status' => 'paid',
            ]);

            // Fetch associated order
            // $order = Order::find($payment->order_id);
            $order = Order::with(['emergencyContact', 'payment'])->find($payment->order_id);

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
                    $itemPrice = $package?->basePricing?->priceVariations?->first()?->regular_price;
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

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong: '.$e->getMessage()], 500);
        }
    }
}
