<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Commission;
use App\Models\Itinerary;
use App\Models\Order;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreatorDashboardController extends Controller
{
    public function stats()
    {
        $creatorId = Auth::id();

        $completedCommissions = Commission::where('creator_id', $creatorId)
            ->whereHas('order', fn($q) => $q->where('status', 'completed'));
        $totalSales = (clone $completedCommissions)->count();
        $totalEarnings = (clone $completedCommissions)->sum('commission_amount');
        $totalViews = Itinerary::whereHas('meta', fn($q) => $q->where('creator_id', $creatorId))
            ->approved()
            ->join('itinerary_meta', 'itineraries.id', '=', 'itinerary_meta.itinerary_id')
            ->sum('itinerary_meta.views_count');

        return response()->json([
            'success' => true,
            'data' => [
                'total_views' => (int) $totalViews,
                'total_sales' => $totalSales,
                'total_earnings' => (float) $totalEarnings,
            ],
        ]);
    }

    public function completedBookings()
    {
        $orders = Order::where('creator_id', Auth::id())
            ->where('status', 'completed')
            ->with(['orderable' => function ($morphTo) {
                $morphTo->morphWith([
                    \App\Models\Activity::class => ['mediaGallery.media'],
                    \App\Models\Package::class => ['mediaGallery.media'],
                    \App\Models\Itinerary::class => ['mediaGallery.media'],
                ]);
            }])
            ->latest()
            ->get();

        $bookings = $orders
            ->filter(fn ($order) => $order->orderable !== null)
            ->map(function ($order) {
                $item = $order->orderable;
                $image = $item->mediaGallery->first()?->media?->url;

                $typeMap = [
                    'App\Models\Activity' => 'Activity',
                    'App\Models\Package' => 'Package',
                    'App\Models\Itinerary' => 'Itinerary',
                ];

                return [
                    'order_id' => $order->id,
                    'item_id' => $order->orderable_id,
                    'item_type' => $order->orderable_type,
                    'item_name' => $item->name,
                    'item_slug' => $item->slug,
                    'item_image' => $image,
                    'type_label' => $typeMap[$order->orderable_type] ?? 'Item',
                    'travel_date' => $order->travel_date,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    public function resolveLink(Request $request)
    {
        $request->validate([
            'url' => 'required|string|url',
        ]);

        $path = parse_url($request->url, PHP_URL_PATH);
        if (! $path) {
            return response()->json(['success' => false, 'message' => 'Invalid URL.'], 422);
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        if (count($segments) < 2) {
            return response()->json(['success' => false, 'message' => 'Invalid URL format.'], 422);
        }

        $typeSegment = $segments[0];
        $slug = $segments[1];

        $modelMap = [
            'activities' => Activity::class,
            'packages' => Package::class,
            'itineraries' => Itinerary::class,
        ];

        if (! isset($modelMap[$typeSegment])) {
            return response()->json(['success' => false, 'message' => 'Unsupported item type. Only activities, packages, and itineraries links are accepted.'], 422);
        }

        $modelClass = $modelMap[$typeSegment];
        $item = $modelClass::where('slug', $slug)->with('mediaGallery.media')->first();

        if (! $item) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        $typeLabels = [
            Activity::class => 'Activity',
            Package::class => 'Package',
            Itinerary::class => 'Itinerary',
        ];

        $image = $item->mediaGallery->first()?->media?->url;

        return response()->json([
            'success' => true,
            'data' => [
                'item_id' => $item->id,
                'item_type' => $modelClass,
                'item_name' => $item->name,
                'item_slug' => $item->slug,
                'item_image' => $image,
                'type_label' => $typeLabels[$modelClass] ?? 'Item',
            ],
        ]);
    }
}
