<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    // public function getUser(Request $request)
    // {
    //     return response()->json(['user' => auth()->user()]);
    // }

    public function getAllUsers()
    {
        // $users = User::all();
        $users = User::with(['meta', 'profile'])->get();

        // Count Users Based on Status
        $totalUsers = $users->count();
        $activeUsers = $users->where('status', 'active')->count();
        $inactiveUsers = $users->where('status', 'inactive')->count();
        $pendingUsers = $users->where('status', 'pending')->count();

        return response()->json([
            'users'             => $users,
            'total_users'       => $totalUsers,
            'active_users'      => $activeUsers,
            'inactive_users'    => $inactiveUsers,
            'pending_users'     => $pendingUsers
        ], 200);
    }

    public function createUser(Request $request)
    {
        // Validate Input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
            'role' => 'required|in:admin,customer',
            'status' => 'required|in:active,inactive,pending',
            'avatar' => 'nullable|exists:media,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status,
            'avatar' => $request->avatar,
        ]);

        // Insert username into `user_meta` table
        $userMeta = UserMeta::create([
            'user_id' => $user->id,
            'username' => $request->username,
        ]);

        // Fetch user with meta data
        $userWithMeta = User::with('meta')->find($user->id);

        return response()->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $userWithMeta->id,
                'name' => $userWithMeta->name,
                'email' => $userWithMeta->email,
                'role' => $userWithMeta->role,
                'status' => $userWithMeta->status,
                'avatar' => [
                    'id' => $userWithMeta->avatarMedia->id ?? null,
                    'url' => $userWithMeta->avatarMedia->url ?? null,
                ],
                'meta' => [
                    'username' => $userWithMeta->meta->username ?? null
                ],
                'created_at' => $userWithMeta->created_at,
                'updated_at' => $userWithMeta->updated_at,
            ]
        ], 201);
    }


    public function show($id)
    {
        try {
            // user + meta fetch
            $user = User::with('meta')->findOrFail($id);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'avatar' => [
                        'id' => $user->avatarMedia->id ?? null,
                        'url' => $user->avatarMedia->url ?? null,
                    ],
                    'meta' => [
                        'username' => $user->meta->username ?? null
                    ],
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        // Find user
        $user = User::with('meta')->find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Validation (only for fields that can come in request)
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|min:8',
            'confirm_password' => 'sometimes|same:password',
            'role' => 'sometimes|in:admin,customer',
            'status' => 'sometimes|in:active,inactive,pending',
            'avatar' => 'nullable|exists:media,id',
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
        $userWithMeta = User::with('meta')->find($user->id);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => [
                'id' => $userWithMeta->id,
                'name' => $userWithMeta->name,
                'email' => $userWithMeta->email,
                'role' => $userWithMeta->role,
                'status' => $userWithMeta->status,
                'avatar' => [
                    'id' => $userWithMeta->avatarMedia->id ?? null,
                    'url' => $userWithMeta->avatarMedia->url ?? null,
                ],
                'meta' => [
                    'username' => $userWithMeta->meta->username ?? null
                ],
                'created_at' => $userWithMeta->created_at,
                'updated_at' => $userWithMeta->updated_at,
            ]
        ], 200);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.'
        ]);
    }

}
