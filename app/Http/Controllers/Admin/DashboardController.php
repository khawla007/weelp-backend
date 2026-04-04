<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Controller
 * 
 * Provides dashboard metrics and statistics for the admin dashboard
 */
class DashboardController extends Controller
{
    /**
     * Get dashboard metrics
     * Returns monthly revenue, bookings, new users, pending orders with growth percentage
     *
     * @return JsonResponse
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
                ->whereMonth('orders.created_at', $currentMonth)
                ->whereYear('orders.created_at', $currentYear)
                ->where('orders.status', 'completed')
                ->sum('order_payments.total_amount');

            // Total Revenue (last month) for growth calculation
            $lastMonthRevenue = DB::table('orders')
                ->leftJoin('order_payments', 'orders.id', '=', 'order_payments.order_id')
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
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->where('status', '!=', 'cancelled')
                ->count();

            // Total Bookings (last month) for growth calculation - exclude cancelled orders
            $lastMonthBookings = DB::table('orders')
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

            // New Users (current month) - all users registered this month
            $newUsers = DB::table('users')
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->count();

            // New Users (last month) - for growth comparison
            $lastMonthNewUsers = DB::table('users')
                ->whereMonth('created_at', $lastMonthNum)
                ->whereYear('created_at', $lastMonthYear)
                ->count();

            // Calculate users growth percentage
            if ($lastMonthNewUsers > 0) {
                $usersGrowth = round((($newUsers - $lastMonthNewUsers) / $lastMonthNewUsers) * 100, 1);
            } elseif ($newUsers > 0) {
                $usersGrowth = 100;
            } else {
                $usersGrowth = 0;
            }

            // Pending Orders (current month) - orders needing attention
            $pendingOrders = DB::table('orders')
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->whereIn('status', ['pending', 'processing'])
                ->count();

            // Pending Orders (last month) - for growth comparison
            $lastMonthPendingOrders = DB::table('orders')
                ->whereMonth('created_at', $lastMonthNum)
                ->whereYear('created_at', $lastMonthYear)
                ->whereIn('status', ['pending', 'processing'])
                ->count();

            // Calculate pending orders growth percentage
            if ($lastMonthPendingOrders > 0) {
                $pendingGrowth = round((($pendingOrders - $lastMonthPendingOrders) / $lastMonthPendingOrders) * 100, 1);
            } elseif ($pendingOrders > 0) {
                $pendingGrowth = 100;
            } else {
                $pendingGrowth = 0;
            }

            $monthName = $now->format('F'); // e.g. "April", "May", etc.

            return response()->json([
                'success' => true,
                'data' => [
                    'metrics' => [
                        [
                            'title' => $monthName . ' Revenue',
                            'total' => $totalRevenue ?? 0,
                            'change' => $revenueGrowth,
                        ],
                        [
                            'title' => $monthName . ' Bookings',
                            'total' => $totalBookings ?? 0,
                            'change' => $bookingsGrowth,
                        ],
                        [
                            'title' => $monthName . ' New Users',
                            'total' => $newUsers ?? 0,
                            'change' => $usersGrowth,
                        ],
                        [
                            'title' => $monthName . ' Orders In Process',
                            'total' => $pendingOrders ?? 0,
                            'change' => $pendingGrowth,
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
     * Returns monthly revenue data for the current year
     *
     * @return JsonResponse
     */
    public function getOverviewChart(): JsonResponse
    {
        try {
            $currentYear = now()->year;

            // Get monthly revenue for current year - only completed orders
            $monthlyRevenue = DB::table('orders')
                ->leftJoin('order_payments', 'orders.id', '=', 'order_payments.order_id')
                ->select(
                    DB::raw('MONTH(orders.created_at) as month'),
                    DB::raw('SUM(order_payments.total_amount) as total')
                )
                ->whereYear('orders.created_at', $currentYear)
                ->where('orders.status', 'completed')
                ->groupBy(DB::raw('MONTH(orders.created_at)'))
                ->orderBy('month')
                ->get();

            // Format data for chart (all 12 months)
            $chartData = [];
            $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            foreach ($monthNames as $index => $name) {
                $month = $index + 1;
                $revenue = $monthlyRevenue->firstWhere('month', $month);
                $chartData[] = [
                    'name' => $name,
                    'total' => (int) ($revenue->total ?? 0),
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

    /**
     * Get recent sales
     * Returns recent pending/confirmed orders with user details and monthly total from completed orders
     *
     * @return JsonResponse
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
                if (!$payment) {
                    return 0;
                }
                return ($payment->total_amount ?? 0) + ($payment->custom_amount ?? 0);
            });

            // Format response data
            $formattedOrders = $recentOrders->map(function ($order) {
                $user = $order->user;
                $payment = $order->payment;
                $avatarMedia = $user?->avatarMedia;

                // Handle orders without users
                if (!$user) {
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
                if ($avatarMedia && !empty($avatarMedia->url)) {
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
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
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

            $searchTerm = '%' . $query . '%';
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
