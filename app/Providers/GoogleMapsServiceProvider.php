<?php

namespace Laurel\GoogleMaps\App\Providers;

use Illuminate\Support\ServiceProvider;
use Laurel\GoogleMaps\GoogleMaps;

class GoogleMapsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/laurel_google_maps.php', 'laurel_google_maps');
        $this->publishes([
            __DIR__ . '/../../config/laurel_google_maps.php' => config_path('laurel_google_maps.php')
        ], 'config');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        GoogleMaps::instance();
    }
}
