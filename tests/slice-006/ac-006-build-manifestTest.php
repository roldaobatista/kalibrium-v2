<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

uses(LaravelTestCase::class);

require_once __DIR__.'/TestHelpers.php';

test('AC-001: npm run build retorna exit 0 e gera manifest com app.js e app.css', function (): void {
    $result = slice006_run_process(['npm', 'run', 'build']);

    expect($result['exit'])->toBe(0, 'AC-001: npm run build deve retornar exit 0. STDERR: '.$result['stderr']);

    $manifest = slice006_load_manifest();
    slice006_validate_layout_integration($manifest);
})->group('slice-006', 'ac-001');

test('AC-003: manifest gerado usa hash de conteudo e /ping referencia os assets versionados', function (): void {
    $result = slice006_run_process(['npm', 'run', 'build']);

    expect($result['exit'])->toBe(0, 'AC-003: npm run build deve retornar exit 0. STDERR: '.$result['stderr']);

    $manifest = slice006_load_manifest();
    slice006_validate_manifest_entries($manifest);
    slice006_validate_manifest_hashes($manifest);

    $response = slice006_ping_request('testing');
    expect($response['payload']['status'] ?? null)->toBe(200, 'AC-003: /ping precisa responder 200 para expor os assets versionados.');
    expect($response['payload']['body'] ?? '')->toContain($manifest['resources/js/app.js']['file'] ?? '', 'AC-003: /ping deve carregar o JS versionado gerado pelo build.');
    expect($response['payload']['body'] ?? '')->toContain($manifest['resources/css/app.css']['file'] ?? '', 'AC-003: /ping deve carregar o CSS versionado gerado pelo build.');
})->group('slice-006', 'ac-003');

test('AC-006: validacao do manifest aponta a entrada Vite ausente', function (): void {
    $result = slice006_run_process(['npm', 'run', 'build']);

    expect($result['exit'])->toBe(0, 'AC-006: npm run build deve retornar exit 0. STDERR: '.$result['stderr']);

    $manifest = slice006_load_manifest();
    unset($manifest['resources/js/app.js']);

    expect(function () use ($manifest): void {
        slice006_validate_manifest_entries($manifest);
    })->toThrow(RuntimeException::class, 'AC-006: manifest sem entradas obrigatorias: resources/js/app.js');
})->group('slice-006', 'ac-006');

test('AC-008: validacao de hash aponta asset nao versionado', function (): void {
    $result = slice006_run_process(['npm', 'run', 'build']);

    expect($result['exit'])->toBe(0, 'AC-008: npm run build deve retornar exit 0. STDERR: '.$result['stderr']);

    $manifest = slice006_load_manifest();
    $manifest['resources/js/app.js']['file'] = 'assets/app.js';

    expect(function () use ($manifest): void {
        slice006_validate_manifest_hashes($manifest);
    })->toThrow(RuntimeException::class, "AC-008: asset resources/js/app.js nao esta versionado: 'assets/app.js'");
})->group('slice-006', 'ac-008');
