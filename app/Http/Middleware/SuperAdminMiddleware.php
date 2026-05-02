<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || $user->role !== User::ROLE_SUPER_ADMIN) {
            return response()->json(['message' => 'Forbidden — super_admin required'], 403);
        }

        return $next($request);
    }
}
