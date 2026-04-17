<?php

declare(strict_types=1);

// routes/web.php — slice 016 (ADR-0015): frontend Livewire descartado.
// Autenticacao e telas de aplicacao sao servidas pelo cliente PWA
// (React + Ionic + Capacitor) via API em slices E15-S07+.
// Web layer do backend fica apenas com health-check durante a transicao.

use App\Http\Controllers\HealthCheckController;
use App\Http\Middleware\HealthCheckRateLimit;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthCheckController::class)
    ->middleware(HealthCheckRateLimit::class);

Route::redirect('/', '/health', 302);
