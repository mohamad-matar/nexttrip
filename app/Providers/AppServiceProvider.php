<?php

namespace App\Providers;

use App\Models\Place;
use App\Observers\PlaceObserver;
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
        Place::observe(PlaceObserver::class);
    }
}
