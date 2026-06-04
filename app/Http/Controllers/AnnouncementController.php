<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    public function index(): JsonResponse
    {
        $announcements = Announcement::visible()
            ->latest()
            ->limit(20)
            ->get(['id', 'type', 'title', 'message', 'link', 'display_style', 'image_url', 'coupon_code', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $announcements,
        ]);
    }

    public function popup(): JsonResponse
    {
        $announcements = Announcement::visible()
            ->where('display_style', 'popup')
            ->latest()
            ->limit(5)
            ->get(['id', 'type', 'title', 'message', 'link', 'image_url', 'coupon_code', 'display_style', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $announcements,
        ]);
    }
}
