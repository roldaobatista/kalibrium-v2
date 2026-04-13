<?php

use App\Http\Controllers\HealthCheckController;
use App\Http\Middleware\HealthCheckRateLimit;
use App\Livewire\Ping;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', HealthCheckController::class)
    ->middleware(HealthCheckRateLimit::class);

if (! app()->environment('production')) {
    Route::get('/ping', Ping::class);
}
