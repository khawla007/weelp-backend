<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
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

        RateLimiter::for('login', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
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
