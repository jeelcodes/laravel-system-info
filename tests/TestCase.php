<?php

namespace Jeelcodes\LaravelSystemInfo\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Jeelcodes\LaravelSystemInfo\SystemInfoServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            SystemInfoServiceProvider::class,
        ];
    }
}
