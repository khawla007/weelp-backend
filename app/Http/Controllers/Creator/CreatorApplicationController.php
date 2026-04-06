<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\CreatorApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreatorApplicationController extends Controller
{
    public function apply(Request $request)
    {
        $user = auth()->user();

        if ($user->is_creator) {
            return response()->json([
                'success' => false,
                'message' => 'Already a creator',
            ], 422);
        }

        $pending = CreatorApplication::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            return response()->json([
                'success' => false,
                'message' => 'Already have pending application',
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'gender' => 'required|in:male,female,other',
            'instagram' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'youtube' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
        ]);

        $application = CreatorApplication::create([
            'user_id' => $user->id,
            ...$validated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Creator application submitted successfully.',
            'data' => $application,
        ], 201);
    }

    public function status()
    {
        $application = CreatorApplication::where('user_id', Auth::id())
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => $application,
        ]);
    }
}
