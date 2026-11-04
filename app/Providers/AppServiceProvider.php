<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
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
        // Outside production, fail loudly on N+1 queries, on attributes that
        // mass assignment would silently drop and on reading attributes that
        // were not loaded. Production keeps running instead of throwing.
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
