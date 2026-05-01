<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request; // ✅ Ye zaruri hai
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $modelMap = [
            'activity' => \App\Models\Activity::class,
            'itinerary' => \App\Models\Itinerary::class,
            'package' => \App\Models\Package::class,
        ];
        $rules = [
            'user_id' => 'required|exists:users,id',
            'orderable_type' => ['required', Rule::in(array_keys($modelMap))],
            'orderable_id' => 'required|integer',
            'travel_date' => 'required|date',
            'preferred_time' => 'nullable|date_format:H:i:s',
            'number_of_adults' => 'required|integer|min:1',
            'number_of_children' => 'nullable|integer|min:0',
            'status' => 'nullable|string|max:50|in:pending,confirmed,cancelled',
            'special_requirements' => 'nullable|string|max:5000',

            'payment' => 'required|array',
            'emergency_contact' => 'required|array',
        ];

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            // Step 1: Create main order
            $order = Order::create([
                'user_id' => $validated['user_id'],
                'orderable_type' => $modelMap[$validated['orderable_type']],
                'orderable_id' => $validated['orderable_id'],
                'travel_date' => $validated['travel_date'],
                'preferred_time' => $validated['preferred_time'] ?? null,
                'number_of_adults' => $validated['number_of_adults'],
                'number_of_children' => $validated['number_of_children'] ?? 0,
                'status' => $validated['status'] ?? 'pending',
                'special_requirements' => $validated['special_requirements'] ?? null,
            ]);

            // Step 2: Create payment
            if (isset($validated['payment'])) {
                $order->payment()->create([
                    'payment_status' => $validated['payment']['payment_status'] ?? 'pending',
                    'payment_method' => $validated['payment']['payment_method'] ?? null,
                    'total_amount' => $validated['payment']['total_amount'] ?? 0,
                    'is_custom_amount' => $validated['payment']['is_custom_amount'] ?? false,
                    'custom_amount' => $validated['payment']['custom_amount'] ?? 0,
                ]);
            }

            // Step 3: Create emergency contact
            if (isset($validated['emergency_contact'])) {
                $order->emergencyContact()->create([
                    'contact_name' => $validated['emergency_contact']['contact_name'] ?? null,
                    'contact_phone' => $validated['emergency_contact']['contact_phone'] ?? null,
                    'relationship' => $validated['emergency_contact']['relationship'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully.',
                'data' => $order->load(['payment', 'emergencyContact']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $perPage = 3;
        $page = $request->get('page', 1);
        $status = $request->get('status');

        // Base query for pagination (filtered)
        $query = Order::with(['user', 'orderable', 'payment', 'emergencyContact']);

        if ($status && in_array($status, ['pending', 'confirmed', 'cancelled'])) {
            $query->where('status', $status);
        }

        $filteredCount = $query->count();

        // Use pagination only if filtered results > 5
        if ($filteredCount <= $perPage) {
            $orders = $query->get();
            $isPaginated = false;
        } else {
            $orders = $query->paginate($perPage, ['*'], 'page', $page);
            $isPaginated = true;
        }

        $orderCollection = $isPaginated ? $orders->getCollection() : $orders;

        $formatted = $orderCollection->map(function ($order) {
            return [
                'id' => $order->id,
                'order_type' => strtolower(class_basename($order->orderable_type)),
                'travel_date' => $order->travel_date,
                'preferred_time' => $order->preferred_time,
                'number_of_adults' => $order->number_of_adults,
                'number_of_children' => $order->number_of_children,
                'status' => $order->status,
                'special_requirements' => $order->special_requirements,
                'user' => $order->user,
                'orderable' => $order->orderable,
                'payment' => $order->payment,
                'emergency_contact' => $order->emergencyContact,
            ];
        });

        // Summary based on **all orders**, NOT filtered
        $allOrders = Order::with('payment')->get();

        // Get current month and last month for growth calculation
        $now = now();
        $currentMonth = $now->month;
        $currentYear = $now->year;
        $lastMonth = $now->copy()->subMonthNoOverflow();
        $lastMonthNum = $lastMonth->month;
        $lastMonthYear = $lastMonth->year;

        // Total Orders - current month
        $totalOrdersCurrent = Order::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        // Total Orders - last month
        $totalOrdersLast = Order::whereMonth('created_at', $lastMonthNum)
            ->whereYear('created_at', $lastMonthYear)
            ->count();
        // Calculate growth
        if ($totalOrdersLast > 0) {
            $totalOrdersGrowth = round((($totalOrdersCurrent - $totalOrdersLast) / $totalOrdersLast) * 100, 1);
        } elseif ($totalOrdersCurrent > 0) {
            $totalOrdersGrowth = 100;
        } else {
            $totalOrdersGrowth = 0;
        }

        // Pending Orders - current month
        $pendingOrdersCurrent = Order::where('status', 'pending')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        // Pending Orders - last month
        $pendingOrdersLast = Order::where('status', 'pending')
            ->whereMonth('created_at', $lastMonthNum)
            ->whereYear('created_at', $lastMonthYear)
            ->count();
        // Calculate growth
        if ($pendingOrdersLast > 0) {
            $pendingOrdersGrowth = round((($pendingOrdersCurrent - $pendingOrdersLast) / $pendingOrdersLast) * 100, 1);
        } elseif ($pendingOrdersCurrent > 0) {
            $pendingOrdersGrowth = 100;
        } else {
            $pendingOrdersGrowth = 0;
        }

        // Completed Orders - current month
        $completedOrdersCurrent = Order::where('status', 'completed')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        // Completed Orders - last month
        $completedOrdersLast = Order::where('status', 'completed')
            ->whereMonth('created_at', $lastMonthNum)
            ->whereYear('created_at', $lastMonthYear)
            ->count();
        // Calculate growth
        if ($completedOrdersLast > 0) {
            $completedOrdersGrowth = round((($completedOrdersCurrent - $completedOrdersLast) / $completedOrdersLast) * 100, 1);
        } elseif ($completedOrdersCurrent > 0) {
            $completedOrdersGrowth = 100;
        } else {
            $completedOrdersGrowth = 0;
        }

        // Total Revenue - current month (only completed orders)
        $totalRevenueCurrent = Order::where('status', 'completed')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->with('payment')
            ->get()
            ->pluck('payment')
            ->filter()
            ->sum(function ($payment) {
                return ($payment->total_amount ?? 0) + ($payment->custom_amount ?? 0);
            });
        // Total Revenue - last month (only completed orders)
        $totalRevenueLast = Order::where('status', 'completed')
            ->whereMonth('created_at', $lastMonthNum)
            ->whereYear('created_at', $lastMonthYear)
            ->with('payment')
            ->get()
            ->pluck('payment')
            ->filter()
            ->sum(function ($payment) {
                return ($payment->total_amount ?? 0) + ($payment->custom_amount ?? 0);
            });
        // Calculate growth
        if ($totalRevenueLast > 0) {
            $totalRevenueGrowth = round((($totalRevenueCurrent - $totalRevenueLast) / $totalRevenueLast) * 100, 1);
        } elseif ($totalRevenueCurrent > 0) {
            $totalRevenueGrowth = 100;
        } else {
            $totalRevenueGrowth = 0;
        }

        $summary = [
            'total_orders' => $allOrders->count(),
            'total_orders_growth' => $totalOrdersGrowth,
            'pending_orders' => $allOrders->where('status', 'pending')->count(),
            'pending_orders_growth' => $pendingOrdersGrowth,
            'confirmed_orders' => $allOrders->where('status', 'confirmed')->count(),
            'completed_orders' => $allOrders->where('status', 'completed')->count(),
            'completed_orders_growth' => $completedOrdersGrowth,
            'cancelled_orders' => $allOrders->where('status', 'cancelled')->count(),
            'total_revenue' => $allOrders->pluck('payment')->filter()->sum(function ($payment) {
                return ($payment->total_amount ?? 0) + ($payment->custom_amount ?? 0);
            }),
            'total_revenue_growth' => $totalRevenueGrowth,
        ];

        // Final Response
        $response = [
            'success' => true,
            'data' => $formatted,
            'summary' => $summary,
        ];

        if ($isPaginated) {
            $response['current_page'] = $orders->currentPage();
            $response['per_page'] = $orders->perPage();
            $response['total'] = $orders->total();

            if ($formatted->isEmpty()) {
                $response['message'] = $status
                    ? "No more {$status} orders available."
                    : 'No more orders available.';
            }
        }

        return response()->json($response);
    }

    public function show($id)
    {
        $order = Order::with(['user', 'orderable', 'payment', 'emergencyContact'])->findOrFail($id);

        $formatted = [
            'id' => $order->id,
            'type' => strtolower(class_basename($order->orderable_type)), // e.g. activity, package
            'travel_date' => $order->travel_date,
            'preferred_time' => $order->preferred_time,
            'number_of_adults' => $order->number_of_adults,
            'number_of_children' => $order->number_of_children,
            'status' => $order->status,
            'special_requirements' => $order->special_requirements,
            'user' => $order->user,
            'orderable' => $order->orderable,
            'payment' => $order->payment,
            'emergency_contact' => $order->emergencyContact,
            // 'created_at'           => $order->created_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $formatted,
        ]);
    }

    public function updateOrder(Request $request, $id)
    {
        // ---------------- Order Fetch ----------------
        $order = Order::with(['payment', 'user'])->findOrFail($id);
        $status = $request->status;

        // ---------------- Refund Logic ----------------
        if ($status === 'refunded') {
            if (! $order->payment || $order->payment->payment_status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund not possible. Payment not found or not paid.',
                ], 400);
            }

            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

                $refund = \Stripe\Refund::create([
                    'payment_intent' => $order->payment->payment_intent_id,
                ]);

                if (isset($refund->status) && $refund->status === 'succeeded') {
                    $order->payment->update(['payment_status' => 'refunded']);
                    $order->update(['status' => 'refunded']);
                }

                Mail::to($order->user->email)->send(new \App\Mail\CustomerRefundedOrderMail($order));

                return response()->json([
                    'success' => true,
                    'message' => 'Refund initiated successfully. Status updated in table.',
                    'refund' => $refund,
                    'email' => $order->user->email,
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund failed.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        // ---------------- Manual Status Update ----------------
        $allowedStatuses = ['completed', 'cancelled'];
        if (! in_array($status, $allowedStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update status to: '.implode(', ', $allowedStatuses),
            ], 400);
        }

        // Completed only if payment_status is paid
        if ($status === 'completed') {
            if (! $order->payment || $order->payment->payment_status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot mark order as completed. Payment not paid yet.',
                ], 400);
            }
        }

        // Cancelled only if payment_status is pending
        if ($status === 'cancelled') {
            if (! $order->payment || $order->payment->payment_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel order. Payment is not pending.',
                ], 400);
            }
        }

        // Update order status
        $order->update(['status' => $status]);

        // Email sending
        if ($status === 'completed') {
            Mail::to($order->user->email)->send(new \App\Mail\CustomerCompletedOrderMail($order));
        } elseif ($status === 'cancelled') {
            Mail::to($order->user->email)->send(new \App\Mail\CustomerCancelledOrderMail($order));
        }

        return response()->json([
            'success' => true,
            'message' => "Order status updated to {$status}.",
            'data' => $order,
        ]);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
