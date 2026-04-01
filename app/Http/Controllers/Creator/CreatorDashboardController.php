<?php
namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;

class CreatorDashboardController extends Controller
{
    public function stats()
    {
        $creatorId = Auth::id();

        $totalSales = Commission::where('creator_id', $creatorId)->count();
        $totalEarnings = Commission::where('creator_id', $creatorId)->sum('commission_amount');
        $totalClicks = Post::where('creator_id', $creatorId)->sum('shares_count');

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => $totalSales,
                'total_earnings' => (float) $totalEarnings,
                'total_clicks' => (int) $totalClicks,
            ],
        ]);
    }
}
