<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

// AC-002: db:check retorna exit 0 com JSON correto
test('db:check returns connected status for db and redis', function () {
    $this->artisan('db:check')
        ->expectsOutput('{"db":"connected","redis":"connected"}')
        ->assertExitCode(0);
})->group('integration');

// Error path: db:check returns failure when database is unavailable
test('db:check returns disconnected when database fails', function () {
    DB::shouldReceive('select')->andThrow(new RuntimeException('Connection refused'));

    $this->artisan('db:check')
        ->assertExitCode(1);
})->group('integration');

// Error path: db:check returns failure when redis is unavailable
test('db:check returns disconnected when redis fails', function () {
    Redis::shouldReceive('connection')->andThrow(new RuntimeException('Connection refused'));

    $this->artisan('db:check')
        ->assertExitCode(1);
})->group('integration');
