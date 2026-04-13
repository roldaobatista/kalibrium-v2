<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Livewire\Livewire;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('livewire:list', function (): int {
    if (! Livewire::isDiscoverable('ping')) {
        $this->error('Componente Livewire ping nao foi descoberto.');

        return 1;
    }

    $this->line('ping');

    return 0;
})->purpose('Lista componentes Livewire disponiveis');

Schedule::call(function (): void {
    cache()->put('scheduler.heartbeat', now()->toIso8601String(), seconds: 120);
})->everyMinute()->name('scheduler:heartbeat')->withoutOverlapping();
