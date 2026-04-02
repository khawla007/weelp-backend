<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
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
        Relation::enforceMorphMap([
            'activity' => \App\Models\Activity::class,
            'package' => \App\Models\Package::class,
            'itinerary' => \App\Models\Itinerary::class,
            'transfer' => \App\Models\Transfer::class,
        ]);
    }
}
