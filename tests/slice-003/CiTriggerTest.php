<?php

declare(strict_types=1);

/**
 * Slice 003 — AC-001, AC-002, AC-003
 *
 * Verifica que o trigger `push` do ci.yml cobre feature branches (não apenas main).
 * Sem `branches: ['**']`, os jobs lint/static-analysis/tests nunca rodam em push
 * de feature branch — tornando AC-001, AC-002 e AC-003 não verificáveis mecanicamente.
 *
 * Estado esperado do ci.yml ATUAL (scaffold slice 001):
 *   on:
 *     push:
 *       branches: [main]     ← só main, NÃO cobre feature branches
 *
 * Estado exigido após slice 003:
 *   on:
 *     push:
 *       branches: ['**']     ← todas as branches
 */

// AC-001: Push em feature branch aciona o job lint — exige branches: ['**']
test('AC-001: trigger push cobre todas as branches (não apenas main)', function (): void {
    $ciYmlPath = base_path('.github/workflows/ci.yml');
    expect(file_exists($ciYmlPath))->toBeTrue("ci.yml não encontrado em {$ciYmlPath}");

    $content = file_get_contents($ciYmlPath);

    // O YAML deve ter push com branches cobrindo qualquer branch.
    // Pattern aceita tanto '**' (com aspas simples) quanto "**" (com aspas duplas).
    $coversAllBranches = (bool) preg_match(
        '/on:\s.*?push:\s.*?branches:\s*\[[\'"]\*\*[\'"]\]/s',
        $content,
    );

    expect($coversAllBranches)->toBeTrue(
        'AC-001 requer push.branches: ["**"] para que o job lint dispare em feature branches. '
        . 'Atualmente o ci.yml só dispara em push para [main].'
    );
})->group('slice-003', 'ac-001');

// AC-002: Push em feature branch aciona o job static-analysis — mesmo trigger
test('AC-002: trigger push cobre feature branches para static-analysis disparar', function (): void {
    $ciYmlPath = base_path('.github/workflows/ci.yml');
    expect(file_exists($ciYmlPath))->toBeTrue("ci.yml não encontrado em {$ciYmlPath}");

    $content = file_get_contents($ciYmlPath);

    // Mesma condição de AC-001: sem ['**'] o job php-static não roda em feature branch.
    $coversAllBranches = (bool) preg_match(
        '/on:\s.*?push:\s.*?branches:\s*\[[\'"]\*\*[\'"]\]/s',
        $content,
    );

    expect($coversAllBranches)->toBeTrue(
        'AC-002 requer push.branches: ["**"] para que php-static dispare em feature branches. '
        . 'PHPStan nível 8 nunca executaria em push de feature com a config atual.'
    );
})->group('slice-003', 'ac-002');

// AC-003: Push em feature branch aciona o job tests — mesmo trigger
test('AC-003: trigger push cobre feature branches para o job tests disparar', function (): void {
    $ciYmlPath = base_path('.github/workflows/ci.yml');
    expect(file_exists($ciYmlPath))->toBeTrue("ci.yml não encontrado em {$ciYmlPath}");

    $content = file_get_contents($ciYmlPath);

    $coversAllBranches = (bool) preg_match(
        '/on:\s.*?push:\s.*?branches:\s*\[[\'"]\*\*[\'"]\]/s',
        $content,
    );

    expect($coversAllBranches)->toBeTrue(
        'AC-003 requer push.branches: ["**"] para que php-test dispare em feature branches. '
        . 'Testes falhando nunca bloqueariam um push de feature com a config atual.'
    );
})->group('slice-003', 'ac-003');
