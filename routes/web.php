<?php

declare(strict_types=1);

// routes/web.php — frontend Livewire descartado (ADR-0015). O backend reexpoe
// os endpoints de dominio para serem consumidos pelo cliente PWA (E15+) e
// pela suite de testes. Em E15-S07+ esses mesmos controllers serao replicados
// em routes/api.php sob /api com auth via Sanctum.

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ContatoController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\Privacy\ConsentSubjectStoreController;
use App\Http\Controllers\Privacy\LgpdCategoryStoreController;
use App\Http\Controllers\TenantSettingsController;
use App\Http\Middleware\HealthCheckRateLimit;
use App\Livewire\MobileDevices\IndexPage as MobileDevicesIndexPage;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthCheckController::class)
    ->middleware(HealthCheckRateLimit::class);

Route::redirect('/', '/health', 302);

// Rotas temporárias para aceite visual — REMOVER após os prints serem gerados
if (app()->environment('local')) {
    Route::get('/aceite-login/{userId}', function (int $userId) {
        $user = User::findOrFail($userId);
        auth()->login($user);

        return redirect('/mobile-devices');
    })->name('aceite.login');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
}

Route::middleware(['auth', 'tenant.context'])->group(function (): void {
    Route::apiResource('clientes', ClienteController::class);
    Route::apiResource('clientes.contatos', ContatoController::class);

    Route::put('/settings/tenant', TenantSettingsController::class)
        ->name('settings.tenant.update');

    Route::post('/consent/subjects', ConsentSubjectStoreController::class)
        ->name('consent.subjects.store');

    Route::post('/settings/privacy/lgpd-categories', LgpdCategoryStoreController::class)
        ->name('settings.privacy.lgpd-categories.store');

    Route::get('/mobile-devices', MobileDevicesIndexPage::class)
        ->name('mobile-devices.index');
});
