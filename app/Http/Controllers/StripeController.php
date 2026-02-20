<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Mail;
use App\Mail\Customer\CustomerProcessingOrderMail;
use App\Mail\Admin\AdminNewOrderMail;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderEmergencyContact;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;


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
        ]);

        $orderableClass = 'App\\Models\\' . ucfirst($data['order_type']);
        $orderable = $orderableClass::findOrFail($data['orderable_id']);
        $totalAmount = $data['is_custom_amount'] ? $data['custom_amount'] : $data['amount'];

        // ✅ Create order
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
                        'id'   => $mg->media?->id,
                        'name' => $mg->media?->name,
                        'url'  => $mg->media?->url,
                        'alt'  => $mg->media?->alt_text,
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

        // ✅ Save payment info (based on PaymentIntent, not session)
        $order->payment()->create([
            'payment_status'    => 'pending',
            'payment_method'    => 'credit_card',
            'amount'            => $data['amount'],
            'is_custom_amount'  => $data['is_custom_amount'],
            'custom_amount'     => $data['custom_amount'],
            'total_amount'      => $totalAmount,
            'currency'          => $data['currency'],
            'payment_intent_id' => $data['payment_intent_id'],
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
        ]);
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $event = json_decode($payload);

        if ($event->type == 'payment_intent.succeeded') {
            $intent_id = $event->data->object->id;


            $payment = OrderPayment::where('payment_intent_id', $intent_id)->first();

            if (!$payment) {
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
                Mail::to($order->user->email)->send(new \App\Mail\CustomerProcessingOrderMail($order));
                // Send admin mail
                Mail::to(config('mail.admin_address', 'khawla@fanaticcoders.com'))->send(new \App\Mail\AdminNewOrderMail($order));
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
            $charge_id = $event->data->object->id;

            $payment = OrderPayment::where('charge_id', $charge_id)->first();
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

        if (!$paymentIntentId) {
            return response()->json(['error' => 'Payment Intent ID is required'], 400);
        }
    
        $payment = OrderPayment::where('payment_intent_id', $paymentIntentId)->first();
    
        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }
    
        $order = Order::with(['payment', 'emergencyContact'])->find($payment->order_id);
    
        if (!$order) {
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
    
        if (!empty($locations[0]['country'])) {
            $countryId = \App\Models\Country::where('name', $locations[0]['country'])->value('id');
        }
    
        $region = $countryId
            ? \App\Models\Region::whereHas('countries', fn ($q) => $q->where('countries.id', $countryId))->first()
            : null;
    
        $response = [
            'id' => $order->id,
            'item_id' => $order->orderable_id,
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
            ],
            'user' => [
                'name' => $user?->name,
                'email' => $user?->email,
                'phone' => $userProfile?->phone,
            ],
        ];
    
        return response()->json([
            'success' => true,
            'order' => $response
        ]);
    }    


    //old stripe code

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
        $orderableClass = 'App\\Models\\' . ucfirst($data['order_type']);
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
                'url' => 'https://checkout.stripe.com/pay/' . $existingOrder->payment->stripe_session_id,
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
                        'name' => 'Trip Booking for ' . $data['travel_date'],
                    ],
                    'unit_amount' => $totalAmount * 100, // in cents
                ],
                'quantity' => 1,
            ]],
            'mode'           => 'payment',
            'customer_email' => $data['customer_email'],
            'success_url'    => env('FRONTEND_URL') . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'     => env('FRONTEND_URL') . '/checkout',
        ]);

        // ✅ Save payment record
        $order->payment()->create([
            'payment_status'    => 'pending',
            'payment_method'    => 'credit_card',
            'amount'            => $data['amount'],
            'is_custom_amount'  => $data['is_custom_amount'],
            'custom_amount'     => $data['custom_amount'],
            'total_amount'      => $totalAmount,
            'currency'          => $data['currency'],
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

            if (!$payment) {
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
                    $itinerary = \App\Models\Itinerary::with('basePricing.variations')->where('id', $orderableId)->first();
                    $itemName = $itinerary?->name;
                    $itemPrice = $itinerary?->basePricing?->priceVariations?->first()?->regular_price;
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
                'data' => $orderDetail
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }    

}
