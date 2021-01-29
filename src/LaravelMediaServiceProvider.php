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
        $this->mergeConfigFrom(__DIR__ . '/config/hnp-media.php', 'hnp-media');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->mergeConfigFrom(__DIR__.'/config/hnp-media.php', 'media-media');
        $this->publishes([
            __DIR__.'/config/hnp-media.php' => config_path('hnp-media.php'),
        ], 'config');

        if (! class_exists('CreateMediaTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_media_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_media_table.php'),
            ], 'migrations');
        }
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

    }
}
