<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareCountryFromUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // Get country from URL parameter first
        $country = $request->route('country');

        // If not in URL, try to get from session
        if (!$country) {
            $country = session('country');
        }

        // If still no country, try to detect from IP
        if (!$country) {
            $position = \Stevebauman\Location\Facades\Location::get();
            $country = strtolower($position->countryCode ?? 'us');
            session(['country' => $country]);
        }

        // Share country with all views
        view()->share('country', $country);

        // Also share with Inertia
        inertia()->share('country', $country);

        return $next($request);
    }
}