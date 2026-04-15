<?php

declare(strict_types=1);

/**
 * Suite de isolamento — CI / Workflow (AC-005, AC-014)
 *
 * Testes estruturais: lêem .github/workflows/ci.yml e assertam
 * que o job tenant-isolation existe com paths filter correto.
 *
 * RED enquanto o job não existir no workflow.
 */

use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-011', 'tenant-isolation');

// ---------------------------------------------------------------------------
// AC-005: Job tenant-isolation existe em ci.yml
// ---------------------------------------------------------------------------

test('AC-005: workflow ci.yml contém job tenant-isolation', function () {
    /** @ac AC-005 */
    $workflowPath = base_path('.github/workflows/ci.yml');

    expect(file_exists($workflowPath))
        ->toBeTrue("Arquivo .github/workflows/ci.yml não encontrado em {$workflowPath}.");

    $content = (string) file_get_contents($workflowPath);

    // O job deve estar declarado explicitamente
    expect($content)
        ->toContain('tenant-isolation:',
            'AC-005: Job "tenant-isolation:" não declarado em .github/workflows/ci.yml. '.
            'Adicione o job com paths filter para app/Models/**, app/Http/**, app/Livewire/**, app/Jobs/**.'
        );
});

test('AC-005: job tenant-isolation referencia o testsuite correto do Pest', function () {
    /** @ac AC-005 */
    $workflowPath = base_path('.github/workflows/ci.yml');
    $content = file_exists($workflowPath) ? (string) file_get_contents($workflowPath) : '';

    $jobStart = strpos($content, 'tenant-isolation:');

    if ($jobStart === false) {
        $this->fail(
            'AC-005: Job "tenant-isolation:" não encontrado em ci.yml. '.
            'Implemente o job antes deste teste.'
        );
    }

    $jobBlock = substr($content, $jobStart, 1500);

    expect($jobBlock)
        ->toContain('--testsuite=tenant-isolation',
            'AC-005: Job não executa "php artisan test --testsuite=tenant-isolation". '.
            'Adicione o comando correto no step de execução.'
        );

    expect($jobBlock)
        ->toContain('needs:',
            'AC-005: Job tenant-isolation deve ter "needs:" (pelo menos harness) para garantir sequência.'
        );
});

// ---------------------------------------------------------------------------
// AC-014: Paths filter — job pulado em PRs que não tocam código sensível
// ---------------------------------------------------------------------------

test('AC-014: job tenant-isolation em ci.yml tem paths filter cobrindo código sensível', function () {
    /** @ac AC-014 */
    $workflowPath = base_path('.github/workflows/ci.yml');

    expect(file_exists($workflowPath))
        ->toBeTrue('.github/workflows/ci.yml não encontrado.');

    $content = (string) file_get_contents($workflowPath);
    $jobStart = strpos($content, 'tenant-isolation:');

    if ($jobStart === false) {
        $this->fail(
            'AC-014: Job "tenant-isolation:" não encontrado em ci.yml. '.
            'Crie o job primeiro (AC-005), depois configure o paths filter.'
        );
    }

    // Analisa o bloco do job (próximos 2000 chars)
    $jobBlock = substr($content, $jobStart, 2000);

    $requiredPaths = ['app/Models', 'app/Http', 'app/Jobs'];
    $missingPaths = [];

    foreach ($requiredPaths as $path) {
        if (! str_contains($jobBlock, $path)) {
            $missingPaths[] = $path;
        }
    }

    expect($missingPaths)
        ->toBeEmpty(
            'AC-014: Os seguintes paths não estão no filter do job tenant-isolation: '.
            implode(', ', $missingPaths).'. '.
            'Adicione paths: [app/Models/**, app/Http/**, app/Livewire/**, app/Jobs/**, tests/tenant-isolation/**].'
        );
});

test('AC-014: job tenant-isolation tem paths filter declarado explicitamente (não roda em todo PR)', function () {
    /** @ac AC-014 */
    $workflowPath = base_path('.github/workflows/ci.yml');
    $content = file_exists($workflowPath) ? (string) file_get_contents($workflowPath) : '';

    $jobStart = strpos($content, 'tenant-isolation:');

    if ($jobStart === false) {
        $this->fail('AC-014: Job "tenant-isolation:" não encontrado em ci.yml.');
    }

    $jobBlock = substr($content, $jobStart, 2000);

    $hasPathsFilter = str_contains($jobBlock, 'paths:')
        || str_contains($jobBlock, 'paths-ignore:');

    expect($hasPathsFilter)
        ->toBeTrue(
            'AC-014: Job tenant-isolation não tem "paths:" filter — '.
            'rodaria em todo PR incluindo mudanças apenas em README.md. '.
            'Adicione: paths: [app/Models/**, app/Http/**, app/Livewire/**, app/Jobs/**, tests/tenant-isolation/**]'
        );
});
