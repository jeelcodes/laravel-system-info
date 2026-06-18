<?php

use Illuminate\Support\Facades\Route;
use Jeelcodes\LaravelSystemInfo\Http\Controllers\SystemInfoController;

Route::get(
    config('system-info.route_prefix'),
    [SystemInfoController::class, 'index']
);

Route::get(
    config('system-info.route_prefix') . '/package-details',
    [SystemInfoController::class, 'packageDetails']
)->name('system-info.package-details');