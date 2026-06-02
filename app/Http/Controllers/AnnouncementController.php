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
            ->get(['id', 'type', 'title', 'message', 'link', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $announcements,
        ]);
    }
}
