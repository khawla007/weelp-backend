<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class DetectAffiliateMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $creatorId = $request->query('ref');

        if ($creatorId) {
            $isValidCreator = User::where('id', $creatorId)
                ->where('is_creator', true)
                ->exists();

            if ($isValidCreator) {
                Cookie::queue(
                    'affiliate_ref',
                    $creatorId,
                    60 * 24 * 30, // 30 days in minutes
                    '/',
                    null,
                    true,  // secure
                    true,  // httpOnly
                    false,
                    'Lax'  // sameSite
                );
            }
        }

        return $next($request);
    }
}
