<?php

namespace Rise3d\ViewerApi;

use Illuminate\Support\ServiceProvider;

class ViewerApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Register the main class to use with the facade
        $this->app->singleton('viewer-api', function () {
            return new ViewerApi;
        });
    }
}
