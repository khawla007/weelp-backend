<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use App\Mail\VerifyEmailMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
// use Illuminate\Support\Facades\Auth;
// use Tymon\JWTAuth\Exceptions\JWTException;

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
     * Handle the user register request New.
    */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

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
        ]);
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
                'email'   => $email,
                'message' => 'Email verified successfully!'
            ]);
        } catch (\Exception $e) {
            $payload = JWTAuth::setToken($token)->getPayload();
            $email = $payload['email'];

            return response()->json([
                'success' => false,
                'email'   => $email,
                'message' => 'Invalid or expired token.'
            ]);
        }
    }

    public function resendVerification(Request $request)
    {
        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified.'
            ]);
        }

        // Generate new JWT token
        $payload = [
            'email' => $user->email,
            'exp' => now()->addDay()->timestamp,
        ];
        $token = JWTAuth::customClaims($payload)->fromUser($user);

        // Update DB token
        DB::table('email_verifications')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        // Send email again
        Mail::to($user->email)->send(new VerifyEmailMail($user, $token));

        return response()->json([
            'success' => true,
            'message' => 'Verification link resent successfully.'
        ]);
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

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'email or password incorrect'
            ], 401);
        }

        $accessToken = JWTAuth::fromUser($user);

        $refreshToken = JWTAuth::customClaims(['type' => 'refresh'])->fromUser($user);

        return response()->json([
            'success' => true,
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,

        ]);
    }

    /**
     * Handle the user logout request.
    */

    public function logout(Request $request)
    {
        try {
            // Invalidate the current token
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json(['message' => 'Successfully logged out']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to logout'], 500);
        }
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

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email address not found.',
            ]);
        }

        // Generate JWT token with 10 minutes expiry
        $payload = [
            'email' => $request->email,
            'exp' => now()->addMinutes(10)->timestamp,
        ];
        $token = JWTAuth::customClaims($payload)->fromUser($user);

        // Store only the hashed token
        $hashedToken = Hash::make($token);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $hashedToken, 'created_at' => now()]
        );

        // Send mail using new Mailable
        Mail::to($request->email)->send(new ResetPasswordMail($token));

        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent to your email.',
        ]);
    }

    /**
     * Handle the user reset password request.
    */

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
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
    
        if (!$storedToken || !Hash::check($request->token, $storedToken->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or already used token.',
            ], 400);
        }
    
        $user = User::where('email', $email)->first();
        if (!$user) {
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
        try {
            $newAccessToken = JWTAuth::refresh(JWTAuth::getToken());
    
            return response()->json([
                'success' => true,
                'access_token' => $newAccessToken,
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired and cannot be refreshed'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not refresh token'], 500);
        }
    }    

}