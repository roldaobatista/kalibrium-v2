<?php

declare(strict_types=1);

/**
 * Slice 003 — AC-001, AC-002, AC-003
 *
 * Verifica que o trigger `push` do ci.yml cobre feature branches (não apenas main).
 * Sem `branches: ['**']`, os jobs lint/static-analysis/tests nunca rodam em push
 * de feature branch — tornando AC-001, AC-002 e AC-003 não verificáveis mecanicamente.
 */
dataset('ci-trigger-acs', [
    'AC-001 (lint)' => ['ac-001', 'o job lint dispare em feature branches'],
    'AC-002 (static-analysis)' => ['ac-002', 'php-static dispare em feature branches'],
    'AC-003 (tests)' => ['ac-003', 'php-test dispare em feature branches'],
]);

test('trigger push cobre todas as branches para que', function (string $group, string $descricao): void {
    $ciYmlPath = base_path('.github/workflows/ci.yml');
    expect(file_exists($ciYmlPath))->toBeTrue("ci.yml não encontrado em {$ciYmlPath}");

    $content = file_get_contents($ciYmlPath);

    $coversAllBranches = (bool) preg_match(
        '/on:\s.*?push:\s.*?branches:\s*\[[\'"]\*\*[\'"]\]/s',
        $content,
    );

    expect($coversAllBranches)->toBeTrue(
        "Requer push.branches: [\"**\"] para que {$descricao}."
    );
})->with('ci-trigger-acs')->group('slice-003');
