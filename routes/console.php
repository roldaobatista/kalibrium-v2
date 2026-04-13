<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Livewire\Livewire;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('livewire:list {component=ping}', function (string $component): int {
    if (! Livewire::isDiscoverable($component)) {
        $this->error("Componente Livewire {$component} nao foi descoberto.");

        return 1;
    }

    $this->line($component);

    return 0;
})->purpose('Lista componentes Livewire disponiveis');

Schedule::call(function (): void {
    cache()->put('scheduler.heartbeat', now()->toIso8601String(), seconds: 120);
})->everyMinute()->name('scheduler:heartbeat')->withoutOverlapping();
