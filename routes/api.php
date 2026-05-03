<?php

declare(strict_types=1);

use App\Http\Controllers\Mobile\LoginController as MobileLoginController;
use App\Http\Controllers\Mobile\MeController as MobileMeController;
use Illuminate\Support\Facades\Route;

Route::post('/mobile/login', MobileLoginController::class)
    ->middleware(['mobile.tenant', 'mobile.login.throttle'])
    ->name('mobile.login');

Route::middleware(['mobile.device.status', 'auth:sanctum'])->group(function (): void {
    Route::get('/mobile/me', MobileMeController::class)->name('mobile.me');
});
