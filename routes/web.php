<?php

declare(strict_types=1);

// routes/web.php — frontend Livewire descartado (ADR-0015). O backend reexpoe
// os endpoints de dominio para serem consumidos pelo cliente PWA (E15+) e
// pela suite de testes. Em E15-S07+ esses mesmos controllers serao replicados
// em routes/api.php sob /api com auth via Sanctum.

use App\Http\Controllers\Auth\WebLoginController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ContatoController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\Privacy\ConsentSubjectStoreController;
use App\Http\Controllers\Privacy\LgpdCategoryStoreController;
use App\Http\Controllers\TenantSettingsController;
use App\Http\Middleware\HealthCheckRateLimit;
use App\Livewire\Dashboard\IndexPage as DashboardIndexPage;
use App\Livewire\MobileDevices\IndexPage as MobileDevicesIndexPage;
use App\Livewire\Technicians\IndexPage as TechniciansIndexPage;
use App\Livewire\Technicians\NotesPage as TechnicianNotesPage;
use App\Livewire\Technicians\ServiceOrdersPage as TechnicianServiceOrdersPage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;

Route::get('/health', HealthCheckController::class)
    ->middleware(HealthCheckRateLimit::class);

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

// Rotas de autenticação web (login, logout, recuperação de senha)
Route::prefix('auth')->group(function (): void {
    // Login web
    Route::get('/login', [WebLoginController::class, 'show'])
        ->middleware('guest')
        ->name('login');

    Route::post('/login', [WebLoginController::class, 'store'])
        ->middleware(['guest', 'throttle:5,1']);

    // Logout
    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    // Recuperação de senha — view
    Route::get('/forgot-password', function () {
        return view('auth.forgot-password');
    })->middleware('guest')->name('password.request');

    // Recuperação de senha — envio do link
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware(['guest', 'throttle:5,1'])
        ->name('password.email');

    // Redefinição de senha (Fortify com ignoreRoutes() — registrada manualmente)
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('guest')
        ->name('password.update');

    // View de reset (link do e-mail)
    Route::get('/reset-password/{token}', function (string $token) {
        return view('auth.reset-password', ['token' => $token, 'email' => request('email')]);
    })->middleware('guest')->name('password.reset');
});

Route::middleware(['auth', 'tenant.context'])->group(function (): void {
    Route::get('/dashboard', DashboardIndexPage::class)->name('dashboard');

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

    Route::get('/technicians', TechniciansIndexPage::class)
        ->name('technicians.index');

    Route::get('/technicians/{technicianUserId}/notes', TechnicianNotesPage::class)
        ->name('technicians.notes');

    Route::get('/technicians/{technicianUserId}/service-orders', TechnicianServiceOrdersPage::class)
        ->name('technicians.service-orders');
});
