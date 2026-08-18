<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dashboard Controller
 *
 * Provides dashboard metrics and statistics for the admin dashboard
 */
class DashboardController extends Controller
{
    /**
     * Get dashboard metrics
     * Returns total revenue, total bookings, active users, and growth percentage
     */
    public function getMetrics(): JsonResponse
    {
        try {
            // Get current month and last month for comparison
            $now = now();
            $currentMonth = $now->month;
            $currentYear = $now->year;

            $lastMonth = $now->copy()->subMonthNoOverflow();
            $lastMonthNum = $lastMonth->month;
            $lastMonthYear = $lastMonth->year;

            // Total Revenue (current month) - only completed orders
            $totalRevenue = DB::table('orders')
                ->leftJoin('order_payments', 'orders.id', '=', 'order_payments.order_id')
                ->whereNull('orders.deleted_at')
                ->whereMonth('orders.created_at', $currentMonth)
                ->whereYear('orders.created_at', $currentYear)
                ->where('orders.status', 'completed')
                ->sum('order_payments.total_amount');

            // Total Revenue (last month) for growth calculation
            $lastMonthRevenue = DB::table('orders')
                ->leftJoin('order_payments', 'orders.id', '=', 'order_payments.order_id')
                ->whereNull('orders.deleted_at')
                ->whereMonth('orders.created_at', $lastMonthNum)
                ->whereYear('orders.created_at', $lastMonthYear)
                ->where('orders.status', 'completed')
                ->sum('order_payments.total_amount');

            // Calculate revenue growth percentage
            if ($lastMonthRevenue > 0) {
                $revenueGrowth = round((($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1);
            } elseif ($totalRevenue > 0) {
                // Last month was 0, current month has revenue - show 100% growth
                $revenueGrowth = 100;
            } else {
                // Both months are 0
                $revenueGrowth = 0;
            }

            // Total Bookings (current month) - exclude cancelled orders
            $totalBookings = DB::table('orders')
                ->whereNull('deleted_at')
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->where('status', '!=', 'cancelled')
                ->count();

            // Total Bookings (last month) for growth calculation - exclude cancelled orders
            $lastMonthBookings = DB::table('orders')
                ->whereNull('deleted_at')
                ->whereMonth('created_at', $lastMonthNum)
                ->whereYear('created_at', $lastMonthYear)
                ->where('status', '!=', 'cancelled')
                ->count();

            // Calculate bookings growth percentage
            if ($lastMonthBookings > 0) {
                $bookingsGrowth = round((($totalBookings - $lastMonthBookings) / $lastMonthBookings) * 100, 1);
            } elseif ($totalBookings > 0) {
                // Last month was 0, current month has bookings - show 100% growth
                $bookingsGrowth = 100;
            } else {
                // Both months are 0
                $bookingsGrowth = 0;
            }

            // Active Users (current month) - users with status = 'active' who registered this month
            $activeUsers = DB::table('users')
                ->where('status', 'active')
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->count();

            // Active Users (last month) - for growth comparison
            $lastMonthActiveUsers = DB::table('users')
                ->where('status', 'active')
                ->whereMonth('created_at', $lastMonthNum)
                ->whereYear('created_at', $lastMonthYear)
                ->count();

            // Calculate users growth percentage
            if ($lastMonthActiveUsers > 0) {
                $usersGrowth = round((($activeUsers - $lastMonthActiveUsers) / $lastMonthActiveUsers) * 100, 1);
            } elseif ($activeUsers > 0) {
                // Last month was 0, current month has users - show 100% growth
                $usersGrowth = 100;
            } else {
                // Both months are 0
                $usersGrowth = 0;
            }

            // Total Activities count
            $totalActivities = DB::table('activities')->count();

            // Total Packages count
            $totalPackages = DB::table('packages')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'metrics' => [
                        [
                            'title' => 'Total Revenue',
                            'total' => $totalRevenue,
                            'change' => $revenueGrowth,
                        ],
                        [
                            'title' => 'Bookings',
                            'total' => $totalBookings,
                            'change' => $bookingsGrowth,
                        ],
                        [
                            'title' => 'Active Users',
                            'total' => $activeUsers,
                            'change' => $usersGrowth,
                        ],
                        [
                            'title' => 'Total Activities',
                            'total' => $totalActivities,
                            'change' => 0, // No growth calculation for activities count
                        ],
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get overview chart data
     * Returns monthly revenue and booking data for the current year
     */
    public function getOverviewChart(): JsonResponse
    {
        try {
            $currentYear = now()->year;
            $monthExpression = $this->monthExpression('orders.created_at');

            // Get monthly revenue for current year - only completed orders
            $monthlyRevenue = DB::table('orders')
                ->leftJoin('order_payments', 'orders.id', '=', 'order_payments.order_id')
                ->selectRaw("{$monthExpression} as month, SUM(order_payments.total_amount) as total")
                ->whereNull('orders.deleted_at')
                ->whereYear('orders.created_at', $currentYear)
                ->where('orders.status', 'completed')
                ->groupByRaw($monthExpression)
                ->orderBy('month')
                ->get();

            $monthlyBookings = DB::table('orders')
                ->selectRaw("{$monthExpression} as month, COUNT(*) as bookings")
                ->whereNull('orders.deleted_at')
                ->whereYear('orders.created_at', $currentYear)
                ->where('orders.status', '!=', 'cancelled')
                ->groupByRaw($monthExpression)
                ->orderBy('month')
                ->get();

            // Format data for chart (all 12 months)
            $chartData = [];
            $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            foreach ($monthNames as $index => $name) {
                $month = $index + 1;
                $revenue = $monthlyRevenue->firstWhere('month', $month);
                $bookings = $monthlyBookings->firstWhere('month', $month);
                $chartData[] = [
                    'name' => $name,
                    'total' => (int) ($revenue->total ?? 0),
                    'bookings' => (int) ($bookings->bookings ?? 0),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $chartData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch overview chart data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function monthExpression(string $column): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CAST(strftime('%m', {$column}) AS INTEGER)";
        }

        return "MONTH({$column})";
    }

    public function getBookingMix(): JsonResponse
    {
        try {
            $now = now();
            $currentStart = $now->copy()->startOfMonth();
            $currentEnd = $now->copy()->endOfMonth();
            $previousMonth = $now->copy()->subMonthNoOverflow();
            $previousStart = $previousMonth->copy()->startOfMonth();
            $previousEnd = $previousMonth->copy()->endOfMonth();

            $current = $this->aggregateSupportedBookings($currentStart, $currentEnd);
            $previous = $this->aggregateSupportedBookings($previousStart, $previousEnd);
            $categories = [
                ['key' => 'activities', 'label' => 'Activities', 'count' => 0],
                ['key' => 'packages', 'label' => 'Packages', 'count' => 0],
                ['key' => 'trips', 'label' => 'Trips', 'count' => 0],
            ];

            foreach ($current as $item) {
                $categoryIndex = match ($item['type']) {
                    'activity' => 0,
                    'package' => 1,
                    'trip' => 2,
                };
                $categories[$categoryIndex]['count'] += $item['bookings'];
            }

            $names = $this->bookingItemNames($current);
            $leaders = $current->map(function (array $item) use ($previous, $names): array {
                $key = $item['type'].':'.$item['id'];
                $previousBookings = $previous->get($key)['bookings'] ?? 0;

                return [
                    'type' => $item['type'],
                    'id' => $item['id'],
                    'name' => $names[$key] ?? 'Unavailable item',
                    'bookings' => $item['bookings'],
                    'change' => $this->bookingChange($item['bookings'], $previousBookings),
                ];
            })->sort(function (array $left, array $right): int {
                return ($right['bookings'] <=> $left['bookings'])
                    ?: strcmp($left['name'], $right['name'])
                    ?: ($left['id'] <=> $right['id']);
            })->take(2)->values()->all();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => array_sum(array_column($categories, 'count')),
                    'categories' => $categories,
                    'leaders' => $leaders,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch booking mix', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch booking mix',
            ], 500);
        }
    }

    private function aggregateSupportedBookings(\DateTimeInterface $start, \DateTimeInterface $end): Collection
    {
        return DB::table('orders')
            ->select('orderable_type', 'orderable_id', DB::raw('COUNT(*) as bookings'))
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->groupBy('orderable_type', 'orderable_id')
            ->get()
            ->reduce(function (Collection $supported, object $row): Collection {
                $type = $this->supportedBookingType($row->orderable_type);
                if ($type === null) {
                    return $supported;
                }

                $key = $type.':'.(int) $row->orderable_id;
                $existing = $supported->get($key, ['type' => $type, 'id' => (int) $row->orderable_id, 'bookings' => 0]);
                $existing['bookings'] += (int) $row->bookings;
                $supported->put($key, $existing);

                return $supported;
            }, collect());
    }

    private function supportedBookingType(string $type): ?string
    {
        return match (strtolower(class_basename($type))) {
            'activity' => 'activity',
            'package' => 'package',
            'itinerary' => 'trip',
            default => null,
        };
    }

    private function bookingItemNames(Collection $bookings): array
    {
        $modelByType = [
            'activity' => Activity::class,
            'package' => Package::class,
            'trip' => Itinerary::class,
        ];
        $names = [];

        foreach ($modelByType as $type => $model) {
            $ids = $bookings->where('type', $type)->pluck('id')->unique()->values();
            if ($ids->isEmpty()) {
                continue;
            }

            foreach ($model::query()->whereIn('id', $ids)->pluck('name', 'id') as $id => $name) {
                $names[$type.':'.$id] = $name ?: 'Unavailable item';
            }
        }

        return $names;
    }

    private function bookingChange(int $current, int $previous): float|int
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }

        return $current > 0 ? 100 : 0;
    }

    /**
     * Get recent sales
     * Returns recent pending/confirmed orders with user details and monthly total from completed orders
     */
    public function getRecentSales(): JsonResponse
    {
        try {
            // Fetch recent 5 orders regardless of status (pending, processing, completed)
            $recentOrders = \App\Models\Order::with(['user.avatarMedia', 'payment'])
                ->orderBy('orders.created_at', 'desc')
                ->limit(5)
                ->get();

            // Calculate monthly total from completed orders this month
            $currentMonth = now()->month;
            $currentYear = now()->year;

            $completedOrders = \App\Models\Order::with('payment')
                ->where('status', 'completed')
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->get();

            $monthlyTotal = $completedOrders->sum(function ($order) {
                $payment = $order->payment;
                if (! $payment) {
                    return 0;
                }

                return ($payment->total_amount ?? 0) + ($payment->custom_amount ?? 0);
            });

            // Format response data
            $formattedOrders = $recentOrders->map(function ($order) {
                $user = $order->user;
                $payment = $order->payment;
                $avatarMedia = $user->avatarMedia;

                // Handle orders without users
                if (! $user) {
                    $amount = 0;
                    if ($payment) {
                        $amount = ($payment->total_amount ?? 0) + ($payment->custom_amount ?? 0);
                    }

                    return [
                        'username' => 'Unknown',
                        'email' => '',
                        'amount' => (float) $amount,
                        'icon' => 'https://ui-avatars.com/api/?name=User&background=random',
                    ];
                }

                // Generate avatar URL
                $avatarUrl = null;
                if ($avatarMedia && ! empty($avatarMedia->url)) {
                    $avatarUrl = $avatarMedia->url;
                } else {
                    // Fallback to UI Avatars API
                    $name = urlencode($user->name ?? 'User');
                    $avatarUrl = "https://ui-avatars.com/api/?name={$name}&background=random";
                }

                // Calculate amount
                $amount = 0;
                if ($payment) {
                    $amount = ($payment->total_amount ?? 0) + ($payment->custom_amount ?? 0);
                }

                return [
                    'username' => $user->name ?? 'Unknown',
                    'email' => $user->email ?? '',
                    'amount' => (float) $amount,
                    'icon' => $avatarUrl,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedOrders,
                'monthly_total' => (float) $monthlyTotal,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent sales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search dashboard content
     * Searches across orders, users, activities, packages, and blogs
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q', '');
            $limit = min($request->input('limit', 10), 50);

            if (empty($query) || strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Query too short',
                ], 200);
            }

            $searchTerm = '%'.$query.'%';
            $results = [];

            // Search Orders
            $orders = DB::table('orders')
                ->select(
                    'id',
                    DB::raw('"order" as type'),
                    DB::raw('CONCAT("Order #", id) as title'),
                    'status',
                    'total_amount as subtitle',
                    DB::raw('"/dashboard/admin/orders" as url')
                )
                ->where('id', 'like', $searchTerm)
                ->orWhere('status', 'like', $searchTerm)
                ->limit(3)
                ->get();

            $results = array_merge($results, $orders->toArray());

            // Search Users
            $users = DB::table('users')
                ->select(
                    'id',
                    DB::raw('"user" as type'),
                    'name as title',
                    'email as subtitle',
                    DB::raw('"/dashboard/admin/users" as url')
                )
                ->where('name', 'like', $searchTerm)
                ->orWhere('email', 'like', $searchTerm)
                ->limit(3)
                ->get();

            $results = array_merge($results, $users->toArray());

            // Search Activities
            $activities = DB::table('activities')
                ->select(
                    'id',
                    DB::raw('"activity" as type'),
                    'title',
                    DB::raw('CONCAT("Activity - ", SUBSTRING(description, 1, 50), "...") as subtitle'),
                    DB::raw('CONCAT("/activity/", slug) as url')
                )
                ->where('title', 'like', $searchTerm)
                ->limit(3)
                ->get();

            $results = array_merge($results, $activities->toArray());

            // Search Packages
            $packages = DB::table('packages')
                ->select(
                    'id',
                    DB::raw('"package" as type'),
                    'title',
                    DB::raw('CONCAT("Package - ", SUBSTRING(description, 1, 50), "...") as subtitle'),
                    DB::raw('CONCAT("/package/", slug) as url')
                )
                ->where('title', 'like', $searchTerm)
                ->limit(3)
                ->get();

            $results = array_merge($results, $packages->toArray());

            // Search Blogs
            $blogs = DB::table('blogs')
                ->select(
                    'id',
                    DB::raw('"blog" as type'),
                    'title',
                    DB::raw('CONCAT("Blog - ", SUBSTRING(content, 1, 50), "...") as subtitle'),
                    DB::raw('CONCAT("/blog/", slug) as url')
                )
                ->where('title', 'like', $searchTerm)
                ->limit(3)
                ->get();

            $results = array_merge($results, $blogs->toArray());

            return response()->json([
                'success' => true,
                'data' => array_slice($results, 0, $limit),
                'query' => $query,
                'count' => count($results),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
