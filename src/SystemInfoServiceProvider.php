<?php

namespace Jeelcodes\LaravelSystemInfo;

use Illuminate\Support\ServiceProvider;

class SystemInfoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            'system-info'
        );

        $this->publishes([
            __DIR__ . '/../config/system-info.php' =>
                \config_path('system-info.php'),
        ], 'system-info-config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/system-info.php',
            'system-info'
        );
    }
}