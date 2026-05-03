<?php

declare(strict_types=1);

use App\Http\Controllers\Mobile\LoginController as MobileLoginController;
use Illuminate\Support\Facades\Route;

Route::post('/mobile/login', MobileLoginController::class)
    ->middleware(['mobile.tenant', 'mobile.login.throttle'])
    ->name('mobile.login');
