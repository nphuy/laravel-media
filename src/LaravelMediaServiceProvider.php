<?php

namespace HNP\LaravelMedia;

use Illuminate\Support\ServiceProvider;

class LaravelMediaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/hnp_media.php', 'hnp_media');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/hnp_media.php' => base_path('config/hnp_media.php'),
        ], 'hnp_media');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

    }
}
