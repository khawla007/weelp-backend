<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'no-referrer');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set(
            'Content-Security-Policy',
            "default-src 'none'; frame-ancestors 'none'; base-uri 'none'"
        );

        if ($request->isSecure()) {
            $headers->set(
                'Strict-Transport-Security',
                'max-age=63072000; includeSubDomains'
            );
        }

        return $response;
    }
}
