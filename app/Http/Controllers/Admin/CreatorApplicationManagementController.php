<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreatorApplication;
use App\Models\Notification;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreatorApplicationManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CreatorApplication::with(['user', 'reviewer']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $applications,
        ]);
    }

    public function show($id): JsonResponse
    {
        $application = CreatorApplication::with(['user.profile', 'reviewer'])->find($id);

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $application,
        ]);
    }

    public function approve($id): JsonResponse
    {
        $application = CreatorApplication::with('user')->find($id);

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found',
            ], 404);
        }

        if ($application->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending applications can be approved.',
            ], 422);
        }

        $application->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $user = $application->user;
        $user->is_creator = true;
        $user->save();

        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'gender' => $application->gender,
                'instagram_handle' => $application->instagram,
                'phone' => $application->phone,
                'youtube_url' => $application->youtube,
                'facebook_url_profile' => $application->facebook,
            ]
        );

        Notification::create([
            'user_id' => $application->user_id,
            'type' => 'application_approved',
            'title' => 'Application Approved',
            'message' => 'Your creator application has been approved! You can now create itineraries.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application approved successfully.',
            'data' => $application->fresh(['user', 'reviewer']),
        ]);
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $application = CreatorApplication::find($id);

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found',
            ], 404);
        }

        if ($application->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending applications can be rejected.',
            ], 422);
        }

        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $application->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        Notification::create([
            'user_id' => $application->user_id,
            'type' => 'application_rejected',
            'title' => 'Application Rejected',
            'message' => $request->admin_notes
                ? "Your creator application was not approved. Reason: {$request->admin_notes}"
                : 'Your creator application was not approved. You may re-apply.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application rejected.',
            'data' => $application->fresh(['user', 'reviewer']),
        ]);
    }
}
