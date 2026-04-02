<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class OtpController extends Controller
{
    // OTP configuration
    private const OTP_LENGTH = 6;

    private const OTP_EXPIRY_SECONDS = 600; // 10 minutes

    private const MAX_ATTEMPTS = 3;

    private const RESEND_COOLDOWN_SECONDS = 30;

    private const RATE_LIMIT_PER_HOUR = 3;

    /**
     * Send OTP to user's email
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'username' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed|regex:/[A-Za-z]/|regex:/\d/|regex:/[@#$%^&+=]/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = $request->email;

        // Rate limiting: check OTP requests per email per hour
        $rateLimitKey = "otp:ratelimit:{$email}";
        $requestCount = Cache::get($rateLimitKey, 0);

        if ($requestCount >= self::RATE_LIMIT_PER_HOUR) {
            return response()->json([
                'success' => false,
                'message' => 'Too many OTP requests. Please try again later.',
            ], 429);
        }

        // Check resend cooldown
        $cooldownKey = "otp:cooldown:{$email}";
        if (Cache::has($cooldownKey)) {
            $remainingSeconds = Cache::get($cooldownKey) - time();

            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting another OTP',
                'retry_after' => $remainingSeconds,
            ], 429);
        }

        // Generate 6-digit OTP
        $otp = $this->generateOtp();

        // Store OTP data in cache
        $otpData = [
            'otp' => $otp,
            'name' => $request->name,
            'username' => $request->username,
            'email' => $email,
            'password' => Hash::make($request->password),
            'attempts' => 0,
            'created_at' => now(),
        ];

        Cache::put("otp:{$email}", $otpData, self::OTP_EXPIRY_SECONDS);

        // Set rate limit counter (expires in 1 hour)
        Cache::put($rateLimitKey, $requestCount + 1, 3600);

        // Set resend cooldown
        Cache::put($cooldownKey, time() + self::RESEND_COOLDOWN_SECONDS, self::RESEND_COOLDOWN_SECONDS);

        // Send OTP email
        try {
            Mail::to($email)->send(new OtpMail($otp, $request->name));

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your email',
                'expires_in' => self::OTP_EXPIRY_SECONDS,
                'resend_cooldown' => self::RESEND_COOLDOWN_SECONDS,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP email',
            ], 500);
        }
    }

    /**
     * Verify OTP and create user account
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP format',
                'errors' => $validator->errors(),
            ], 400);
        }

        $email = $request->email;
        $submittedOtp = $request->otp;

        // Get OTP data from cache
        $otpData = Cache::get("otp:{$email}");

        if (! $otpData) {
            return response()->json([
                'success' => false,
                'message' => 'OTP not found or expired',
            ], 404);
        }

        // Check attempts
        if ($otpData['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget("otp:{$email}");

            return response()->json([
                'success' => false,
                'message' => 'Maximum attempts exceeded. Please request a new OTP',
            ], 429);
        }

        // Verify OTP
        if ($otpData['otp'] !== $submittedOtp) {
            $otpData['attempts']++;
            Cache::put("otp:{$email}", $otpData, self::OTP_EXPIRY_SECONDS);

            $attemptsRemaining = self::MAX_ATTEMPTS - $otpData['attempts'];

            return response()->json([
                'success' => false,
                'message' => 'Incorrect OTP',
                'attempts_remaining' => $attemptsRemaining,
            ], 422);
        }

        // OTP is correct - create user account

        // Check if email already registered (race condition check)
        if (User::where('email', $otpData['email'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Email already registered',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create user
            $user = User::create([
                'name' => $otpData['name'],
                'email' => $otpData['email'],
                'password' => $otpData['password'],
                'role' => User::ROLE_CUSTOMER,
                'email_verified_at' => now(),
            ]);

            // Store username in user_meta
            UserMeta::create([
                'user_id' => $user->id,
                'username' => $otpData['username'],
            ]);

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            // Clear OTP from cache
            Cache::forget("otp:{$email}");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Account created successfully',
                'access_token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create account',
            ], 500);
        }
    }

    /**
     * Generate cryptographically secure 6-digit OTP
     */
    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }
}
