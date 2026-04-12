<?php

use App\Http\Controllers\HealthCheckController;
use App\Http\Middleware\HealthCheckRateLimit;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', HealthCheckController::class)
    ->middleware(HealthCheckRateLimit::class);
