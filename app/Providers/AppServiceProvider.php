<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

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

        Relation::enforceMorphMap([
            'activity'  => \App\Models\Activity::class,
            'package'   => \App\Models\Package::class,
            'itinerary' => \App\Models\Itinerary::class,
            'transfer'  => \App\Models\Transfer::class,
            'city'      => \App\Models\City::class,
            'place'     => \App\Models\Place::class,
        ]);
    }
}
