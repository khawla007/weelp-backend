<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class VerifyTokenVersion
{
    /**
     * Reject requests whose JWT `tv` claim does not match the user's
     * current token_version. Lets logout / refresh-reuse globally
     * revoke all outstanding tokens by incrementing token_version.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Public routes carry no bearer; let downstream auth (or lack of it) decide.
        if (! $request->bearerToken()) {
            return $next($request);
        }

        try {
            $payload = JWTAuth::setRequest($request)->parseToken()->getPayload();
        } catch (\Throwable $e) {
            // Defer auth errors to the auth:api guard so public routes still work.
            return $next($request);
        }

        $userId = $payload->get('sub');
        if (! $userId) {
            return $next($request);
        }

        // Read straight from DB; avoid the JWTGuard's cached User instance which can
        // hold a stale `token_version` between requests sharing a container.
        $currentVersion = (int) (User::whereKey($userId)->value('token_version') ?? 1);
        $tokenVersion = (int) $payload->get('tv');

        if ($tokenVersion !== $currentVersion) {
            return response()->json(['error' => 'token_revoked'], 401);
        }

        return $next($request);
    }
}
