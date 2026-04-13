<?php

declare(strict_types=1);

require_once __DIR__.'/TestHelpers.php';

test('AC-002: GET /ping fora de production retorna 200 e Livewire OK', function (): void {
    $response = slice006_ping_request('testing');

    expect($response['exit'])->toBe(0, 'AC-002: a requisicao tecnica deve executar sem erro. STDERR: '.$response['stderr']);
    expect($response['payload']['status'] ?? null)->toBe(200, 'AC-002: /ping fora de production deve responder 200.');
    expect($response['payload']['body'] ?? '')->toContain('Livewire OK', 'AC-002: /ping deve expor o texto Livewire OK.');
})->group('slice-006', 'ac-002');

test('AC-007: /ping em production retorna 404 e nao fica exposto', function (): void {
    $local = slice006_ping_request('testing');
    expect($local['exit'])->toBe(0, 'AC-007: a requisicao tecnica local deve executar sem erro. STDERR: '.$local['stderr']);
    expect($local['payload']['status'] ?? null)->toBe(200, 'AC-007: o caminho tecnico precisa existir fora de production.');

    $production = slice006_ping_request('production');
    expect($production['exit'])->toBe(0, 'AC-007: a requisicao em production deve executar sem erro. STDERR: '.$production['stderr']);
    expect($production['payload']['status'] ?? null)->toBe(404, 'AC-007: /ping em production deve responder 404.');
})->group('slice-006', 'ac-007');

test('AC-SEC-001: production nao expõe diagnostico, estado, stack trace ou contador', function (): void {
    $local = slice006_ping_request('testing');
    expect($local['exit'])->toBe(0, 'AC-SEC-001: a requisicao local deve executar sem erro. STDERR: '.$local['stderr']);
    expect($local['payload']['status'] ?? null)->toBe(200, 'AC-SEC-001: o caminho tecnico deve existir fora de production.');
    expect($local['payload']['body'] ?? '')->toContain('Livewire OK');

    $production = slice006_ping_request('production');
    expect($production['exit'])->toBe(0, 'AC-SEC-001: a requisicao em production deve executar sem erro. STDERR: '.$production['stderr']);
    expect($production['payload']['status'] ?? null)->toBe(404, 'AC-SEC-001: production deve responder 404.');

    $body = (string) ($production['payload']['body'] ?? '');
    expect($body)->not->toContain('Livewire OK');
    expect($body)->not->toContain('counter');
    expect($body)->not->toContain('state');
    expect($body)->not->toContain('stack');
})->group('slice-006', 'ac-sec-001', 'security');
