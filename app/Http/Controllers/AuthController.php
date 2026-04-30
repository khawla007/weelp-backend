<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Handle the user register request Old.
     */
    // public function register(Request $request)
    // {

    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|email|unique:users,email',
    //         'password' => 'required|string|min:8',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation Error',
    //             'errors' => $validator->errors(),
    //         ], 422);
    //     }

    //     $user = User::create([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password),
    //         'role' => User::ROLE_CUSTOMER,
    //     ]);

    //     $token = auth()->login($user);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'User registered successfully',
    //         'user' => $user,
    //         'token' => $token,
    //     ], 201);
    // }

    /**
     * Check if username is available
     */
    public function checkUsername(Request $request)
    {
        $username = $request->input('username');

        if (! $username || strlen($username) < 3) {
            return response()->json([
                'available' => false,
                'message' => 'Username must be at least 3 characters',
            ]);
        }

        // Check in user_meta table (has direct username column)
        $exists = DB::table('user_meta')
            ->where('username', $username)
            ->exists();

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? 'Username already taken. Please choose another.' : 'Username available',
        ]);
    }

    /**
     * Handle the user register request New.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed|regex:/[A-Za-z]/|regex:/\d/|regex:/[@#$%^&+=]/',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->forceFill(['role' => User::ROLE_CUSTOMER])->save();

        // Generate JWT token valid for 24 hours
        $payload = [
            'email' => $user->email,
            'exp' => now()->addDay()->timestamp,
        ];
        $token = JWTAuth::customClaims($payload)->fromUser($user);

        // Store hashed token
        DB::table('email_verifications')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        // Send verification email
        Mail::to($user->email)->send(new VerifyEmailMail($user, $token));

        return response()->json([
            'success' => true,
            'message' => 'Registration successful! Please verify your email.',
        ], 201);
    }

    /**
     * Handle the user email verfication while regestring account.
     */
    public function verifyEmail(Request $request)
    {
        $token = $request->token;

        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            $email = $payload['email'];

            $user = User::where('email', $email)->firstOrFail();

            // Update email_verified_at
            $user->email_verified_at = now();
            $user->save();

            // Delete verification token record
            DB::table('email_verifications')->where('email', $email)->delete();

            return response()->json([
                'success' => true,
                'email' => $email,
                'message' => 'Email verified successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'email' => null,
                'message' => 'Invalid or expired token.',
            ], 400);
        }
    }

    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $genericResponse = response()->json([
            'success' => true,
            'message' => 'If an unverified account with that email exists, a verification link has been sent.',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && ! $user->email_verified_at) {
            try {
                $payload = [
                    'email' => $user->email,
                    'exp' => now()->addDay()->timestamp,
                ];
                $token = JWTAuth::customClaims($payload)->fromUser($user);

                DB::table('email_verifications')->updateOrInsert(
                    ['email' => $user->email],
                    ['token' => Hash::make($token), 'created_at' => now()]
                );

                Mail::to($user->email)->send(new VerifyEmailMail($user, $token));
            } catch (\Throwable $e) {
                Log::warning('resendVerification delivery failed: '.$e->getMessage());
            }
        }

        return $genericResponse;
    }

    /**
     * Handle the user login request.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $user = User::where('email', $email)->first();

        if ($user && $user->locked_until && now()->lt($user->locked_until)) {
            return response()->json([
                'success' => false,
                'error' => 'Account temporarily locked. Try again later.',
            ], 423);
        }

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            if ($user) {
                DB::table('users')->where('id', $user->id)->increment('failed_login_attempts');
                $user->refresh();
                if ($user->failed_login_attempts >= 10) {
                    DB::table('users')->where('id', $user->id)->update([
                        'locked_until' => now()->addMinutes(30),
                        'failed_login_attempts' => 0,
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'error' => 'email or password incorrect',
            ], 401);
        }

        if ($user->failed_login_attempts > 0 || $user->locked_until) {
            $user->failed_login_attempts = 0;
            $user->locked_until = null;
            $user->save();
        }

        $user->load('profile');

        $accessToken = JWTAuth::customClaims([
            'type' => 'access',
            'tv' => (int) $user->token_version,
            'exp' => now()->addMinutes((int) config('jwt.ttl'))->timestamp,
        ])->fromUser($user);

        $refreshToken = JWTAuth::customClaims([
            'type' => 'refresh',
            'tv' => (int) $user->token_version,
            'exp' => now()->addMinutes((int) config('jwt.refresh_ttl'))->timestamp,
        ])->fromUser($user);

        return response()->json([
            'success' => true,
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'is_creator' => $user->is_creator,
            'avatar' => $user->profile?->avatar,
        ]);
    }

    /**
     * Handle the user logout request.
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            // Blacklist the current access token.
            try {
                JWTAuth::invalidate(JWTAuth::getToken());
            } catch (\Throwable $e) {
                Log::warning('logout: access token invalidate failed: '.$e->getMessage());
            }

            // Blacklist the refresh token if the client sent it.
            if ($refresh = $request->input('refreshToken')) {
                try {
                    JWTAuth::setToken($refresh)->invalidate();
                } catch (\Throwable $e) {
                    Log::warning('logout: refresh token invalidate failed: '.$e->getMessage());
                }
            }

            // Bump token_version → revoke every other outstanding token for this user.
            $user?->increment('token_version');

            return response()->json(['message' => 'Successfully logged out']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to logout'], 500);
        }
    }

    /**
     * Upgrade current customer to creator.
     */
    public function upgradeToCreator(Request $request)
    {
        $user = auth()->user();

        if ($user->is_creator) {
            return response()->json([
                'success' => false,
                'message' => 'You are already a creator.',
            ], 422);
        }

        $user->is_creator = true;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'You are now a creator!',
            'is_creator' => true,
        ]);
    }

    /**
     * Handle the user forgot password request.
     */

    // public function forgotPassword(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email',
    //     ]);

    //     $user = User::where('email', $request->email)->first();

    //     if (!$user) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Email address not found.',
    //         ]);
    //     }

    //     $payload = [
    //         'email' => $request->email,
    //         'exp' => now()->addMinutes(10)->timestamp,
    //     ];
    //     $token = JWTAuth::customClaims($payload)->fromUser($user);

    //     // Store only the hash of the token
    //     $hashedToken = Hash::make($token);

    //     DB::table('password_resets')->updateOrInsert(
    //         ['email' => $request->email],
    //         ['token' => $hashedToken, 'created_at' => now()]
    //     );

    //     // Send the original token in the email
    //     Mail::send('emails.reset-password', ['token' => $token], function ($message) use ($request) {
    //         $message->to($request->email);
    //         $message->subject('Reset Password Notification');
    //     });

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Password reset link sent to your email.',
    //     ]);
    // }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $genericResponse = response()->json([
            'success' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            try {
                $payload = [
                    'email' => $request->email,
                    'exp' => now()->addMinutes(10)->timestamp,
                ];
                $token = JWTAuth::customClaims($payload)->fromUser($user);

                DB::table('password_resets')->updateOrInsert(
                    ['email' => $request->email],
                    ['token' => Hash::make($token), 'created_at' => now()]
                );

                Mail::to($request->email)->send(new ResetPasswordMail($token));
            } catch (\Throwable $e) {
                Log::warning('forgotPassword delivery failed: '.$e->getMessage());
            }
        }

        return $genericResponse;
    }

    /**
     * Handle the user reset password request.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:8|confirmed|regex:/[A-Za-z]/|regex:/\d/|regex:/[@#$%^&+=]/',
        ]);

        try {
            $decodedToken = JWTAuth::setToken($request->token)->getPayload();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or link expired.',
            ], 400);
        }

        $email = $decodedToken->get('email');

        // Retrieve the stored hashed token
        $storedToken = DB::table('password_resets')->where('email', $email)->first();

        if (! $storedToken || ! Hash::check($request->token, $storedToken->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or already used token.',
            ], 400);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $user->update(['password' => bcrypt($request->password)]);

        // Delete the token after use
        DB::table('password_resets')->where('email', $email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password has been reset successfully.',
        ]);
    }

    /**
     * Handle the get user detail request.
     */

    // public function getUserDetails(Request $request)
    // {
    //     try {
    //         // $user = JWTAuth::parseToken()->authenticate();
    //         $user = auth('api')->user();

    //         if (!$user) {
    //             return response()->json(['error' => 'User not found'], 404);
    //         }

    //         return response()->json([
    //             'id' => $user->id,
    //             'email' => $user->email,
    //             'name' => $user->name,
    //             'role' => $user->role,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Token is invalid or expired'], 401);
    //     }
    // }

    /**
     * Handle the refresh token request.
     */
    public function refreshToken(Request $request)
    {
        $oldRefresh = $request->input('refreshToken') ?? $request->bearerToken();

        if (! $oldRefresh) {
            return response()->json(['error' => 'refresh_token_missing'], 401);
        }

        try {
            JWTAuth::setToken($oldRefresh);
            $payload = JWTAuth::getPayload();
        } catch (TokenBlacklistedException $e) {
            // Reuse of an already-rotated refresh token → assume theft, revoke everything for the user.
            try {
                $unverifiedPayload = JWTAuth::manager()->getJWTProvider()->decode($oldRefresh);
                if ($userId = $unverifiedPayload['sub'] ?? null) {
                    User::whereKey($userId)->increment('token_version');
                    Log::warning('Refresh token reuse detected for user '.$userId.'; token_version bumped.');
                }
            } catch (\Throwable $inner) {
                Log::warning('Refresh reuse cleanup failed: '.$inner->getMessage());
            }

            return response()->json(['error' => 'refresh_token_reused'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'refresh_token_expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'invalid_token'], 401);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'could_not_refresh'], 500);
        }

        if ($payload->get('type') !== 'refresh') {
            return response()->json(['error' => 'invalid_token_type'], 401);
        }

        try {
            $user = JWTAuth::authenticate();
        } catch (\Throwable $e) {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        if (! $user) {
            return response()->json(['error' => 'user_not_found'], 401);
        }

        if ((int) $payload->get('tv') !== (int) $user->token_version) {
            // Stale tv on a signature-valid refresh = the user already rotated/logged out
            // elsewhere. Treat as theft signal: blacklist this token and bump again so the
            // attacker's parallel access tokens (if any) are invalidated immediately.
            try {
                JWTAuth::setToken($oldRefresh)->invalidate();
            } catch (\Throwable $e) {
                Log::warning('refreshToken: failed to blacklist tv-mismatch refresh: '.$e->getMessage());
            }
            $user->increment('token_version');
            Log::warning('refreshToken: tv mismatch treated as theft', ['user_id' => $user->id]);

            return response()->json(['error' => 'token_revoked'], 401);
        }

        // Single-use: blacklist the refresh token before issuing the new pair.
        try {
            JWTAuth::setToken($oldRefresh)->invalidate();
        } catch (\Throwable $e) {
            Log::warning('refreshToken: failed to blacklist old refresh: '.$e->getMessage());
        }

        $newAccess = JWTAuth::customClaims([
            'type' => 'access',
            'tv' => (int) $user->token_version,
            'exp' => now()->addMinutes((int) config('jwt.ttl'))->timestamp,
        ])->fromUser($user);

        $newRefresh = JWTAuth::customClaims([
            'type' => 'refresh',
            'tv' => (int) $user->token_version,
            'exp' => now()->addMinutes((int) config('jwt.refresh_ttl'))->timestamp,
        ])->fromUser($user);

        return response()->json([
            'success' => true,
            'accessToken' => $newAccess,
            'refreshToken' => $newRefresh,
        ]);
    }
}
