<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $lastSeen = Auth::user()->notifications_last_seen_at;

        $query = Notification::where('user_id', Auth::id())->unread();

        if ($lastSeen) {
            $query->where('created_at', '>', $lastSeen);
        }

        return response()->json([
            'success' => true,
            'count' => $query->count(),
        ]);
    }

    public function markSeen(): JsonResponse
    {
        Auth::user()->update(['notifications_last_seen_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as seen.',
        ]);
    }

    public function markAsRead($id): JsonResponse
    {
        $notification = Notification::where('user_id', Auth::id())->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => $notification,
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        Notification::where('user_id', Auth::id())
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }
}
