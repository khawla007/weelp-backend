<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CancellationRequest;
use App\Models\Order;
use App\Services\CancellationRequestService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation; // ✅ Ye zaruri hai
use Illuminate\Http\Request;
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
        $validated = $request->validate([
            'page' => ['sometimes', 'integer'],
            'view' => ['sometimes', Rule::in(['active', 'trash'])],
            'status' => ['sometimes', 'nullable', Rule::in(['pending', 'processing', 'completed', 'cancelled', 'refunded'])],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $perPage = 5;
        $page = max(1, (int) ($validated['page'] ?? 1));
        $view = $validated['view'] ?? 'active';
        $status = $validated['status'] ?? null;
        $search = trim((string) ($validated['search'] ?? ''));
        $orderableTypes = array_values(Relation::morphMap());

        // Base query for pagination (filtered)
        $query = $view === 'trash'
            ? Order::onlyTrashed()->with(['user', 'orderable', 'payment', 'emergencyContact', 'latestCancellationRequest'])
            : Order::with(['user', 'orderable', 'payment', 'emergencyContact', 'latestCancellationRequest']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $escapedSearch = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search);
            $searchPattern = "%{$escapedSearch}%";

            $query->where(function (Builder $searchQuery) use ($orderableTypes, $search, $searchPattern): void {
                $searchQuery
                    ->whereHas('user', function (Builder $userQuery) use ($searchPattern): void {
                        $userQuery->whereRaw("LOWER(name) LIKE LOWER(?) ESCAPE '!'", [$searchPattern]);
                    });

                foreach ($orderableTypes as $orderableType) {
                    $orderable = new $orderableType;
                    $storedMorphTypes = [$orderable->getMorphClass(), $orderableType];
                    $matchingOrderableIds = $orderable->newQuery()
                        ->select($orderable->qualifyColumn($orderable->getKeyName()))
                        ->whereRaw("LOWER(name) LIKE LOWER(?) ESCAPE '!'", [$searchPattern]);

                    $searchQuery->orWhere(function (Builder $orderableQuery) use ($matchingOrderableIds, $storedMorphTypes): void {
                        $orderableQuery
                            ->whereIn($orderableQuery->getModel()->qualifyColumn('orderable_type'), $storedMorphTypes)
                            ->whereIn($orderableQuery->getModel()->qualifyColumn('orderable_id'), $matchingOrderableIds);
                    });
                }

                if (ctype_digit($search)) {
                    $searchQuery->orWhere(
                        $searchQuery->getModel()->qualifyColumn($searchQuery->getModel()->getKeyName()),
                        (int) $search
                    );
                }
            });
        }

        $orders = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $formatted = $orders->getCollection()->map(function ($order) {
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
                'cancellation_needs_attention' => ! $order->trashed()
                    && $order->latestCancellationRequest?->needsAdminAttention() === true,
                'created_at' => $order->created_at?->toISOString(),
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
            'current_page' => $orders->currentPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
            'last_page' => $orders->lastPage(),
            'trash_count' => Order::onlyTrashed()->count(),
        ];

        if ($formatted->isEmpty()) {
            $response['message'] = $status || $search !== ''
                ? 'No orders match the selected filters.'
                : 'No more orders available.';
        }

        return response()->json($response);
    }

    public function show($id)
    {
        $order = Order::withTrashed()
            ->with(['user.profile', 'orderable', 'payment', 'emergencyContact', 'latestCancellationRequest'])
            ->findOrFail($id);

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
            'created_at' => $order->created_at?->toISOString(),
            'is_trashed' => $order->trashed(),
            'cancellation' => app(CancellationRequestService::class)
                ->transform($order->latestCancellationRequest, admin: true),
        ];

        return response()->json([
            'success' => true,
            'data' => $formatted,
        ]);
    }

    public function updateOrder(Request $request, $id)
    {
        $status = $request->status;

        $result = DB::transaction(function () use ($id, $status): array {
            [$order, $payment, $cancellations] = $this->lockOrderWorkflow((int) $id);
            $latestCancellation = $cancellations->first();

            if ($latestCancellation?->needsAdminAttention()) {
                return ['response' => response()->json([
                    'success' => false,
                    'message' => 'Resolve the customer cancellation request before changing this order.',
                ], 409)];
            }

            if ($status === 'refunded') {
                return ['response' => response()->json([
                    'success' => false,
                    'message' => 'Use the cancellation request workflow to issue refunds.',
                ], 400)];
            }

            $allowedStatuses = ['completed', 'cancelled'];
            if (! in_array($status, $allowedStatuses)) {
                return ['response' => response()->json([
                    'success' => false,
                    'message' => 'You can only update status to: '.implode(', ', $allowedStatuses),
                ], 400)];
            }

            if ($status === 'completed' && (! $payment || $payment->payment_status !== 'paid')) {
                return ['response' => response()->json([
                    'success' => false,
                    'message' => 'Cannot mark order as completed. Payment not paid yet.',
                ], 400)];
            }

            if ($status === 'cancelled' && (! $payment || $payment->payment_status !== 'pending')) {
                return ['response' => response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel order. Payment is not pending.',
                ], 400)];
            }

            $order->update(['status' => $status]);

            return ['order' => $order];
        });

        if (isset($result['response'])) {
            return $result['response'];
        }

        /** @var Order $order */
        $order = $result['order'];
        $order->load(['payment', 'user']);

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

    public function destroy(int $id)
    {
        $blocked = DB::transaction(function () use ($id): bool {
            [$order, , $cancellations] = $this->lockOrderWorkflow($id);
            if ($cancellations->first()?->needsAdminAttention()) {
                return true;
            }

            $order->delete();

            return false;
        });

        if ($blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Resolve the customer cancellation request before moving this order to Trash.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order moved to Trash.',
        ]);
    }

    public function restore(int $id)
    {
        DB::transaction(function () use ($id): void {
            [$order] = $this->lockOrderWorkflow($id, trashedOnly: true);
            $order->restore();
        });

        return response()->json([
            'success' => true,
            'message' => 'Order restored successfully.',
        ]);
    }

    public function forceDestroy(int $id)
    {
        $blocked = DB::transaction(function () use ($id): bool {
            [$order, , $cancellations] = $this->lockOrderWorkflow($id, trashedOnly: true);
            if ($cancellations->isNotEmpty()) {
                return true;
            }

            $order->forceDelete();

            return false;
        });

        if ($blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Orders with cancellation history cannot be permanently deleted.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order permanently deleted.',
        ]);
    }

    /**
     * Lock an order and its workflow records in the canonical order used by refunds.
     *
     * @return array{Order, \App\Models\OrderPayment|null, \Illuminate\Database\Eloquent\Collection<int, CancellationRequest>}
     */
    private function lockOrderWorkflow(int $id, bool $trashedOnly = false): array
    {
        $query = $trashedOnly ? Order::onlyTrashed() : Order::query();
        $order = $query->whereKey($id)->lockForUpdate()->firstOrFail();
        $payment = $order->payment()->lockForUpdate()->first();
        $cancellations = $order->cancellationRequests()
            ->latest('requested_at')
            ->latest('id')
            ->lockForUpdate()
            ->get();
        $this->afterOrderWorkflowLocked($order);

        return [$order, $payment, $cancellations];
    }

    protected function afterOrderWorkflowLocked(Order $order): void
    {
        // Test seam for proving real database-lock contention.
    }
}
