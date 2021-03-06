<?php

namespace HNP\LaravelMedia;

use Illuminate\Support\ServiceProvider;
use HNP\LaravelMedia\Commands\Regenerate as RegenarateCommand;

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
        $this->app->bind('command.laravel-media:regenerate', RegenarateCommand::class);
        $this->commands([
            'command.laravel-media:regenerate',
        ]);
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
                __DIR__.'/../database/migrations/create_media_table.php.stub' => database_path('migrations/2021_02_02_081034_create_media_table.php'),
            ], 'migrations');
        }
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

    }
}
