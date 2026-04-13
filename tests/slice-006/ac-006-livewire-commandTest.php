<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Factory\Factory;
use Livewire\Finder\Finder;

uses(LaravelTestCase::class);

require_once __DIR__.'/TestHelpers.php';

test('AC-005: php artisan livewire:list retorna exit 0 e lista ping', function (): void {
    $path = base_path('app/Livewire/Ping.php');
    expect(file_exists($path))->toBeTrue(
        'AC-005: app/Livewire/Ping.php precisa existir para o comando livewire:list.'
    );

    $result = slice006_run_process([
        PHP_BINARY,
        base_path('artisan'),
        'livewire:list',
    ]);

    expect($result['exit'])->toBe(0, 'AC-005: livewire:list deve retornar exit 0. STDERR: '.$result['stderr']);
    expect(str_contains($result['stdout'], 'ping'))->toBeTrue(
        'AC-005: livewire:list deve listar o componente ping.'
    );
})->group('slice-006', 'ac-005');

test('AC-010: livewire:list falha quando ping nao esta registrado', function (): void {
    $path = base_path('app/Livewire/Ping.php');
    expect(file_exists($path))->toBeTrue(
        'AC-010: app/Livewire/Ping.php precisa existir para a listagem do componente.'
    );

    $originalFinder = app('livewire.finder');
    $originalFactory = app('livewire.factory');

    try {
        $finder = new Finder;
        $finder->addLocation(classNamespace: 'App\\Livewire\\Ausente');

        app()->instance('livewire.finder', $finder);
        app()->instance('livewire.factory', new Factory($finder, app('livewire.compiler')));

        $exitCode = Artisan::call('livewire:list');
        $output = Artisan::output();
    } finally {
        app()->instance('livewire.finder', $originalFinder);
        app()->instance('livewire.factory', $originalFactory);
    }

    expect($exitCode)->not->toBe(0, 'AC-010: livewire:list deve falhar quando ping nao esta registrado.');
    expect(str_contains($output, 'ping'))->toBeFalse(
        'AC-010: livewire:list nao deve listar ping quando Ping nao esta registrado.'
    );
})->group('slice-006', 'ac-010');
