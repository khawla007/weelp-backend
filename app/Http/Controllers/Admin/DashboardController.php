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
     * Returns total revenue, total bookings, active users, and growth percentage
     *
     * @return JsonResponse
     */
    public function getMetrics(): JsonResponse
    {
        try {
            // Get current month and last month for comparison
            $currentMonth = now()->month;
            $lastMonth = now()->subMonth()->month;
            $currentYear = now()->year;

            // Total Revenue (current month)
            $totalRevenue = DB::table('orders')
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->where('status', 'completed')
                ->sum('total_amount');

            // Total Revenue (last month) for growth calculation
            $lastMonthRevenue = DB::table('orders')
                ->whereMonth('created_at', $lastMonth)
                ->whereYear('created_at', $lastMonth)
                ->where('status', 'completed')
                ->sum('total_amount');

            // Calculate revenue growth percentage
            $revenueGrowth = $lastMonthRevenue > 0 
                ? round((($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
                : 0;

            // Total Bookings (current month)
            $totalBookings = DB::table('orders')
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->count();

            // Total Bookings (last month) for growth calculation
            $lastMonthBookings = DB::table('orders')
                ->whereMonth('created_at', $lastMonth)
                ->whereYear('created_at', $lastMonth)
                ->count();

            // Calculate bookings growth percentage
            $bookingsGrowth = $lastMonthBookings > 0 
                ? round((($totalBookings - $lastMonthBookings) / $lastMonthBookings) * 100, 1)
                : 0;

            // Active Users (users with orders in current month)
            $activeUsers = DB::table('orders')
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->distinct('user_id')
                ->count('user_id');

            // Active Users (last month) for growth calculation
            $lastMonthActiveUsers = DB::table('orders')
                ->whereMonth('created_at', $lastMonth)
                ->whereYear('created_at', $lastMonth)
                ->distinct('user_id')
                ->count('user_id');

            // Calculate users growth percentage
            $usersGrowth = $lastMonthActiveUsers > 0 
                ? round((($activeUsers - $lastMonthActiveUsers) / $lastMonthActiveUsers) * 100, 1)
                : 0;

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
                            'total' => $totalRevenue ?? 0,
                            'change' => $revenueGrowth,
                        ],
                        [
                            'title' => 'Bookings',
                            'total' => $totalBookings ?? 0,
                            'change' => $bookingsGrowth,
                        ],
                        [
                            'title' => 'Active Users',
                            'total' => $activeUsers ?? 0,
                            'change' => $usersGrowth,
                        ],
                        [
                            'title' => 'Total Activities',
                            'total' => $totalActivities ?? 0,
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
     * Returns monthly revenue data for the current year
     *
     * @return JsonResponse
     */
    public function getOverviewChart(): JsonResponse
    {
        try {
            $currentYear = now()->year;

            // Get monthly revenue for current year
            $monthlyRevenue = DB::table('orders')
                ->select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(total_amount) as total')
                )
                ->whereYear('created_at', $currentYear)
                ->where('status', 'completed')
                ->groupBy(DB::raw('MONTH(created_at)'))
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
     * Returns recent completed orders with user details
     *
     * @return JsonResponse
     */
    public function getRecentSales(): JsonResponse
    {
        try {
            $recentSales = DB::table('orders')
                ->select(
                    'orders.id',
                    'orders.total_amount as amount',
                    'orders.created_at',
                    'users.name as username',
                    'users.email',
                    'users.avatar_url as avatar'
                )
                ->leftJoin('users', 'orders.user_id', '=', 'users.id')
                ->where('orders.status', 'completed')
                ->orderBy('orders.created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $recentSales,
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
