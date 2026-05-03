<?php

use App\Http\Middleware\CheckMobileDeviceStatus;
use App\Http\Middleware\EnsureReadOnlyTenantMode;
use App\Http\Middleware\EnsureTwoFactorChallengeCompleted;
use App\Http\Middleware\MobileLoginRateLimit;
use App\Http\Middleware\ResolveMobileTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetCurrentTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->statefulApi();
        $middleware->alias([
            'auth.2fa.completed' => EnsureTwoFactorChallengeCompleted::class,
            'mobile.device.status' => CheckMobileDeviceStatus::class,
            'mobile.login.throttle' => MobileLoginRateLimit::class,
            'mobile.tenant' => ResolveMobileTenant::class,
            'tenant.context' => SetCurrentTenantContext::class,
            'tenant.read-only' => EnsureReadOnlyTenantMode::class,
        ]);
        $middleware->redirectGuestsTo('/auth/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
