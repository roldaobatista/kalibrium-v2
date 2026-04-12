<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    cache()->put('scheduler.heartbeat', now()->toIso8601String(), seconds: 120);
})->everyMinute()->name('scheduler:heartbeat')->withoutOverlapping();
