<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // public function getUser(Request $request)
    // {
    //     return response()->json(['user' => auth()->user()]);
    // }

    public function getAllUsers(Request $request)
    {
        $query = User::with(['meta', 'profile']);

        // Search by name or email
        if ($request->has('search') && ! empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('email', 'like', '%'.$searchTerm.'%');
            });
        }

        // Filter by status
        if ($request->has('status') && ! empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Get stats before pagination
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $inactiveUsers = User::where('status', 'inactive')->count();

        // Pagination
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => [
                'users' => $users->items(),
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $inactiveUsers,
        ], 200);
    }

    public function createUser(Request $request)
    {
        $allowedRoles = auth()->user()?->role === User::ROLE_SUPER_ADMIN
            ? [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_CUSTOMER]
            : [User::ROLE_ADMIN, User::ROLE_CUSTOMER];

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
            'role' => ['required', Rule::in($allowedRoles)],
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => $request->status,
        ]);
        $user->role = $request->role;
        $user->save();

        // Insert username into `user_meta` table
        $userMeta = UserMeta::create([
            'user_id' => $user->id,
            'username' => $request->username,
        ]);

        // Fetch user with meta data
        $userWithMeta = User::with(['meta', 'profile'])->find($user->id);

        return response()->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $userWithMeta->id,
                'name' => $userWithMeta->name,
                'email' => $userWithMeta->email,
                'role' => $userWithMeta->role,
                'status' => $userWithMeta->status,
                'avatar' => $userWithMeta->profile?->avatar,
                'meta' => [
                    'username' => $userWithMeta->meta->username ?? null,
                ],
                'created_at' => $userWithMeta->created_at,
                'updated_at' => $userWithMeta->updated_at,
            ],
        ], 201);
    }

    public function show($id)
    {
        try {
            // user + meta fetch
            $user = User::with(['meta', 'profile'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'avatar' => $user->profile?->avatar,
                    'meta' => [
                        'username' => $user->meta->username ?? null,
                    ],
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        // Find user
        $user = User::with('meta')->find($id);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $allowedRoles = auth()->user()?->role === User::ROLE_SUPER_ADMIN
            ? [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_CUSTOMER]
            : [User::ROLE_ADMIN, User::ROLE_CUSTOMER];

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$id,
            'password' => 'sometimes|min:8',
            'confirm_password' => 'sometimes|same:password',
            'role' => ['sometimes', Rule::in($allowedRoles)],
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update fields only if present
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($request->has('role')) {
            $user->role = $request->role;
        }
        if ($request->has('status')) {
            $user->status = $request->status;
        }

        $user->save();

        // Update user_meta (if username present)
        // if ($request->has('username')) {
        //     if ($user->meta) {
        //         $user->meta->update(['username' => $request->username]);
        //     } else {
        //         UserMeta::create([
        //             'user_id' => $user->id,
        //             'username' => $request->username,
        //         ]);
        //     }
        // }

        // Refresh user with meta
        $userWithMeta = User::with(['meta', 'profile'])->find($user->id);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => [
                'id' => $userWithMeta->id,
                'name' => $userWithMeta->name,
                'email' => $userWithMeta->email,
                'role' => $userWithMeta->role,
                'status' => $userWithMeta->status,
                'avatar' => $userWithMeta->profile?->avatar,
                'meta' => [
                    'username' => $userWithMeta->meta->username ?? null,
                ],
                'created_at' => $userWithMeta->created_at,
                'updated_at' => $userWithMeta->updated_at,
            ],
        ], 200);
    }

    public function uploadUserAvatar(Request $request, $id)
    {
        $request->validate([
            'file' => array_merge(['required'], \App\Support\UploadRules::image(2048)),
        ]);

        $user = User::findOrFail($id);

        try {
            $avatarService = new \App\Services\AvatarService;
            $url = $avatarService->upload($user, $request->file('file'));

            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $userIds = $request->user_ids;

        $deletedCount = User::whereIn('id', $userIds)->delete();

        return response()->json([
            'success' => true,
            'message' => "$deletedCount user(s) deleted successfully",
        ]);
    }
}
