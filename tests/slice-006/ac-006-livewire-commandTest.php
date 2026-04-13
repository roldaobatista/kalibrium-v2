<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

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

test('AC-010: livewire:list lista ping e denuncia ausencia quando o item some', function (): void {
    $path = base_path('app/Livewire/Ping.php');
    expect(file_exists($path))->toBeTrue(
        'AC-010: app/Livewire/Ping.php precisa existir para a listagem do componente.'
    );

    $result = slice006_run_process([
        PHP_BINARY,
        base_path('artisan'),
        'livewire:list',
    ]);

    expect($result['exit'])->toBe(0, 'AC-010: livewire:list deve executar sem erro. STDERR: '.$result['stderr']);
    expect(str_contains($result['stdout'], 'ping'))->toBeTrue(
        'AC-010: livewire:list deve incluir ping quando o componente estiver registrado.'
    );

    $prunedOutput = preg_replace('/^.*ping.*\R?/mi', '', $result['stdout']);
    expect($prunedOutput)->not->toContain('ping', 'AC-010: a lista sem ping deve apontar ausencia do componente.');
})->group('slice-006', 'ac-010');
