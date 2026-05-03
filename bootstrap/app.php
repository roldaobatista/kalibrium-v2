<?php

use App\Http\Middleware\CheckMobileDeviceStatus;
use App\Http\Middleware\EnsureReadOnlyTenantMode;
use App\Http\Middleware\EnsureTwoFactorChallengeCompleted;
use App\Http\Middleware\MobileForgotPasswordRateLimit;
use App\Http\Middleware\MobileLoginRateLimit;
use App\Http\Middleware\ResolveMobileTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetCurrentTenantContext;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Rotas de desenvolvimento local — carregadas apenas quando APP_ENV=local
            // E APP_ENABLE_TEST_LOGIN=true. Barreira dupla: nunca em produção/staging.
            if (app()->environment('local') && config('app.enable_test_login') === true) {
                Route::middleware('web')
                    ->group(base_path('routes/dev.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->statefulApi();
        $middleware->alias([
            'auth.2fa.completed' => EnsureTwoFactorChallengeCompleted::class,
            'mobile.device.status' => CheckMobileDeviceStatus::class,
            'mobile.forgot.throttle' => MobileForgotPasswordRateLimit::class,
            'mobile.login.throttle' => MobileLoginRateLimit::class,
            'mobile.tenant' => ResolveMobileTenant::class,
            'tenant.context' => SetCurrentTenantContext::class,
            'tenant.read-only' => EnsureReadOnlyTenantMode::class,
        ]);
        // Garante que CheckMobileDeviceStatus rode ANTES do Authenticate (auth:sanctum),
        // para que devices wiped retornem wipe:true mesmo com token expirado.
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: CheckMobileDeviceStatus::class,
        );
        $middleware->redirectGuestsTo('/auth/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
