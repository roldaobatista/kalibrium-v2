<?php

declare(strict_types=1);

/**
 * Slice 003 — AC-004
 *
 * Verifica que TODOS os jobs necessários existem no ci.yml para que um PR para main
 * possa exibir check verde. O job `php-rector` ainda NÃO existe no scaffold atual
 * (slice 001 criou: harness, php-lint, php-static, php-test, js-lint, security).
 *
 * AC-004: PR para main com todos os jobs verdes exibe check verde no GitHub.
 * Para isso, o workflow deve ter os 7 jobs definidos no plan:
 *   harness, php-lint, php-static, php-test, js-lint, security, php-rector
 */

// AC-004: job php-rector existe no workflow
test('AC-004: job php-rector existe no ci.yml (Rector --dry-run)', function (): void {
    $ciYmlPath = base_path('.github/workflows/ci.yml');
    expect(file_exists($ciYmlPath))->toBeTrue("ci.yml não encontrado em {$ciYmlPath}");

    $content = file_get_contents($ciYmlPath);

    // Procura pela chave do job php-rector na seção jobs:
    expect(str_contains($content, 'php-rector:'))->toBeTrue(
        'AC-004 requer o job php-rector no ci.yml. '
        .'Sem ele, o check do GitHub não inclui validação de Rector e o pipeline está incompleto.'
    );
})->group('slice-003', 'ac-004');

// AC-004: job php-rector roda Rector em --dry-run (não altera arquivos)
test('AC-004: job php-rector usa flag --dry-run (CI read-only)', function (): void {
    $ciYmlPath = base_path('.github/workflows/ci.yml');
    expect(file_exists($ciYmlPath))->toBeTrue("ci.yml não encontrado em {$ciYmlPath}");

    $content = file_get_contents($ciYmlPath);

    // Verifica que o ci.yml contém o comando rector com --dry-run
    $hasRectorDryRun = str_contains($content, 'rector process --dry-run')
        || str_contains($content, 'rector --dry-run');

    expect($hasRectorDryRun)->toBeTrue(
        'AC-004 requer que o job php-rector execute `rector process --dry-run`. '
        .'Rector sem --dry-run aplicaria mudanças no runner (efeito colateral inaceitável em CI).'
    );
})->group('slice-003', 'ac-004');

// AC-004: todos os 7 jobs necessários estão definidos
test('AC-004: todos os 7 jobs do pipeline estão presentes no ci.yml', function (): void {
    $ciYmlPath = base_path('.github/workflows/ci.yml');
    expect(file_exists($ciYmlPath))->toBeTrue("ci.yml não encontrado em {$ciYmlPath}");

    $content = file_get_contents($ciYmlPath);

    $requiredJobs = [
        'harness:',
        'php-lint:',
        'php-static:',
        'php-test:',
        'js-lint:',
        'security:',
        'php-rector:',
    ];

    $missingJobs = array_filter(
        $requiredJobs,
        fn (string $job) => ! str_contains($content, $job),
    );

    expect($missingJobs)->toBeEmpty(
        'AC-004 requer que todos os jobs estejam definidos para o check do GitHub estar completo. '
        .'Jobs ausentes: '.implode(', ', $missingJobs)
    );
})->group('slice-003', 'ac-004');
