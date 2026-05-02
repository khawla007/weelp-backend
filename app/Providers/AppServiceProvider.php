<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') && config('app.debug') === true) {
            throw new \RuntimeException('APP_DEBUG must be false in production.');
        }

        if ($this->app->environment('production')) {
            $origins = config('cors.allowed_origins', []);

            if (config('cors.supports_credentials') === true && empty($origins)) {
                throw new \RuntimeException(
                    'CORS supports_credentials=true requires explicit allowed_origins in production.'
                );
            }

            foreach ($origins as $origin) {
                if (trim((string) $origin) === '') {
                    throw new \RuntimeException('CORS allowed_origins contains empty entry in production.');
                }
                if ($origin === '*') {
                    throw new \RuntimeException('CORS allowed_origins cannot be wildcard in production.');
                }
                if (str_starts_with($origin, 'http://')) {
                    throw new \RuntimeException(
                        'CORS allowed_origins must use https:// in production: '.$origin
                    );
                }
            }
        }

        $trustedProxies = config('security.trusted_proxies');

        if ($this->app->environment('production') && empty($trustedProxies)) {
            throw new \RuntimeException(
                'TRUSTED_PROXIES must be a CIDR list (e.g. "10.0.0.0/8,192.168.0.0/16") '
                .'or "*" in production. Without it request->ip() and request->isSecure() '
                .'report the load balancer instead of the real client.'
            );
        }

        if (! empty($trustedProxies)) {
            $proxies = $trustedProxies === '*'
                ? '*'
                : array_map('trim', explode(',', $trustedProxies));

            TrustProxies::at($proxies);
            TrustProxies::withHeaders(
                Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
            );
        }

        RateLimiter::for('login', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        RateLimiter::for('verify_email', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email', '')));
            $key = $email !== '' ? $email.'|'.$request->ip() : $request->ip();

            return Limit::perMinute(5)->by($key);
        });

        Relation::enforceMorphMap([
            'activity' => \App\Models\Activity::class,
            'package' => \App\Models\Package::class,
            'itinerary' => \App\Models\Itinerary::class,
            'transfer' => \App\Models\Transfer::class,
            'city' => \App\Models\City::class,
            'place' => \App\Models\Place::class,
        ]);
    }
}
