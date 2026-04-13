<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

uses(LaravelTestCase::class);

require_once __DIR__.'/TestHelpers.php';

test('AC-004: phpstan analyse em app/Livewire/Ping.php retorna exit 0', function (): void {
    $path = base_path('app/Livewire/Ping.php');
    expect(file_exists($path))->toBeTrue(
        'AC-004: app/Livewire/Ping.php precisa existir para a analise estaticar o componente real.'
    );

    $result = slice006_run_process([
        PHP_BINARY,
        base_path('vendor/bin/phpstan'),
        'analyse',
        $path,
        '--level=8',
        '--no-progress',
    ]);

    expect($result['exit'])->toBe(0, 'AC-004: phpstan deve retornar exit 0. STDERR: '.$result['stderr']);
})->group('slice-006', 'ac-004');

test('AC-009: phpstan retorna exit diferente de 0 para um Ping com erro de tipo', function (): void {
    $source = base_path('app/Livewire/Ping.php');
    expect(file_exists($source))->toBeTrue(
        'AC-009: app/Livewire/Ping.php precisa existir para provar a falha de tipo em um fixture derivado.'
    );

    $brokenFixture = slice006_create_broken_php_fixture();

    try {
        $result = slice006_run_process([
            PHP_BINARY,
            base_path('vendor/bin/phpstan'),
            'analyse',
            $brokenFixture,
            '--level=8',
            '--no-progress',
        ]);

        expect($result['exit'])->not->toBe(0, 'AC-009: phpstan deve falhar no fixture com erro de tipo.');
    } finally {
        slice006_cleanup_temp_file($brokenFixture);
    }
})->group('slice-006', 'ac-009');
