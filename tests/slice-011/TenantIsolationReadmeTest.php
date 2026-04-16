<?php

declare(strict_types=1);

/**
 * Suite de isolamento — README (AC-007)
 *
 * Valida que tests/slice-011/README.md existe e contém:
 * - Instrução acessível (≤ 2 parágrafos antes do exemplo)
 * - 1 exemplo de código copiável mostrando como adicionar model sensível
 * - Referência ao comando de execução local
 */

use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-011', 'tenant-isolation');

// ---------------------------------------------------------------------------
// AC-007: README existe
// ---------------------------------------------------------------------------

test('AC-007: tests/slice-011/README.md existe', function () {
    /** @ac AC-007 */
    $readmePath = base_path('tests/slice-011/README.md');

    expect(file_exists($readmePath))
        ->toBeTrue(
            'AC-007: tests/slice-011/README.md não existe. '.
            'Crie o README com instrução para dev júnior e exemplo de código copiável.'
        );
});

test('AC-007: README contém exemplo de código com bloco de código markdown', function () {
    /** @ac AC-007 */
    $readmePath = base_path('tests/slice-011/README.md');

    if (! file_exists($readmePath)) {
        $this->fail('AC-007: README.md não existe — execute o teste anterior primeiro.');
    }

    $content = (string) file_get_contents($readmePath);

    expect($content)->not->toBeEmpty('AC-007: README.md está vazio.');

    $hasCodeBlock = str_contains($content, '```');

    expect($hasCodeBlock)
        ->toBeTrue(
            'AC-007: README.md não contém nenhum bloco de código (``` ... ```). '.
            'Adicione pelo menos 1 exemplo copiável mostrando como incluir novo model sensível.'
        );
});

test('AC-007: README menciona sensitive_models e o comando de execução local', function () {
    /** @ac AC-007 */
    $readmePath = base_path('tests/slice-011/README.md');

    if (! file_exists($readmePath)) {
        $this->fail('AC-007: README.md não existe.');
    }

    $content = (string) file_get_contents($readmePath);

    expect($content)->toContain('sensitive_models');
    expect($content)->toContain('tenant-isolation');
});

test('AC-007: exemplo de código aparece logo após 1-2 parágrafos de introdução — acessível para dev júnior', function () {
    /** @ac AC-007 */
    $readmePath = base_path('tests/slice-011/README.md');

    if (! file_exists($readmePath)) {
        $this->fail('AC-007: README.md não existe.');
    }

    $content = (string) file_get_contents($readmePath);
    $lines = explode("\n", $content);

    $firstCodeBlockLine = null;
    foreach ($lines as $i => $line) {
        if (str_starts_with(trim($line), '```')) {
            $firstCodeBlockLine = $i;
            break;
        }
    }

    expect($firstCodeBlockLine)
        ->not->toBeNull('AC-007: Nenhum bloco de código encontrado no README.');

    $paragraphsBeforeCode = 0;
    $inParagraph = false;

    for ($i = 0; $i < $firstCodeBlockLine; $i++) {
        $line = trim($lines[$i]);
        if ($line === '') {
            $inParagraph = false;

            continue;
        }
        if (str_starts_with($line, '#')) {
            continue;
        }
        if (! $inParagraph) {
            $paragraphsBeforeCode++;
            $inParagraph = true;
        }
    }

    expect($paragraphsBeforeCode)
        ->toBeLessThanOrEqual(2,
            "AC-007: README tem {$paragraphsBeforeCode} parágrafos antes do primeiro exemplo. ".
            'Máximo 2 — o exemplo deve aparecer imediatamente para dev júnior.'
        );
});
