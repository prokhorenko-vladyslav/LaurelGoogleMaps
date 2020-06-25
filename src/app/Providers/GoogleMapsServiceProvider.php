<?php

namespace Laurel\GoogleMaps\App\Providers;

use Illuminate\Support\ServiceProvider;
use Laurel\GoogleMaps\GoogleMaps;

/**
 * GoogleMaps service provider
 *
 * Class GoogleMapsServiceProvider
 * @package Laurel\GoogleMaps\App\Providers
 */
class GoogleMapsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/google_maps.php', 'google_maps');
        $this->publishes([
            __DIR__ . '/../../config/google_maps.php' => config_path('laurel/google_maps.php')
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
