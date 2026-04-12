<?php

declare(strict_types=1);

/**
 * Slice 003 — Testes de runtime para AC-001, AC-002, AC-003, AC-004.
 *
 * Complementa os testes de estrutura YAML com execução real das ferramentas.
 * Cada teste injeta código inválido em arquivo temporário, roda a ferramenta
 * e verifica que o exit code é não-zero (provando que o job CI falharia).
 */

// AC-001 runtime: Pint rejeita código mal formatado (exit != 0)
test('AC-001 runtime: pint --test falha com código mal formatado', function (): void {
    $tmpDir = sys_get_temp_dir().'/slice003-pint-'.uniqid();
    mkdir($tmpDir, 0755, true);

    // Código PHP intencionalmente mal formatado (espaços inconsistentes, sem declare)
    file_put_contents($tmpDir.'/BadFormat.php', <<<'PHP'
<?php
function   badlyFormatted(   $x,$y   ){
return $x+$y;
}
PHP);

    // Pint com preset laravel deve rejeitar este arquivo
    $exitCode = null;
    exec(
        sprintf('php %s --test --config %s %s 2>&1',
            escapeshellarg(base_path('vendor/bin/pint')),
            escapeshellarg(base_path('pint.json')),
            escapeshellarg($tmpDir)
        ),
        $output,
        $exitCode,
    );

    // Cleanup
    @unlink($tmpDir.'/BadFormat.php');
    @rmdir($tmpDir);

    expect($exitCode)->not->toBe(0,
        'AC-001: Pint deve retornar exit != 0 para código mal formatado. '
        .'Se retornou 0, o job lint do CI não detectaria o problema.'
    );
})->group('slice-003', 'ac-001');

// AC-002 runtime: PHPStan rejeita erro de tipo nível 8 (exit != 0)
test('AC-002 runtime: phpstan falha com erro de tipo nível 8', function (): void {
    $tmpDir = sys_get_temp_dir().'/slice003-phpstan-'.uniqid();
    mkdir($tmpDir, 0755, true);

    // Código com erro de tipo óbvio: retorno string onde int é declarado
    file_put_contents($tmpDir.'/BadType.php', <<<'PHP'
<?php

declare(strict_types=1);

function getNumber(): int
{
    return 'not a number';
}
PHP);

    // PHPStan nível 8 deve rejeitar
    $exitCode = null;
    exec(
        sprintf('php %s analyse --level=8 --no-progress --error-format=raw %s 2>&1',
            escapeshellarg(base_path('vendor/bin/phpstan')),
            escapeshellarg($tmpDir.'/BadType.php')
        ),
        $output,
        $exitCode,
    );

    // Cleanup
    @unlink($tmpDir.'/BadType.php');
    @rmdir($tmpDir);

    expect($exitCode)->not->toBe(0,
        'AC-002: PHPStan nível 8 deve retornar exit != 0 para erro de tipo. '
        .'Se retornou 0, o job static-analysis do CI não detectaria o problema.'
    );
})->group('slice-003', 'ac-002');

// AC-003 runtime: Pest falha com teste falhando (exit != 0)
test('AC-003 runtime: pest falha quando um teste está vermelho', function (): void {
    $tmpDir = sys_get_temp_dir().'/slice003-pest-'.uniqid();
    mkdir($tmpDir, 0755, true);

    // Teste Pest que falha intencionalmente
    file_put_contents($tmpDir.'/FailingTest.php', <<<'PHP'
<?php

test('este teste falha de propósito', function (): void {
    expect(true)->toBeFalse();
});
PHP);

    $exitCode = null;
    exec(
        sprintf('php %s %s --no-coverage 2>&1',
            escapeshellarg(base_path('vendor/bin/pest')),
            escapeshellarg($tmpDir.'/FailingTest.php')
        ),
        $output,
        $exitCode,
    );

    // Cleanup
    @unlink($tmpDir.'/FailingTest.php');
    @rmdir($tmpDir);

    expect($exitCode)->not->toBe(0,
        'AC-003: Pest deve retornar exit != 0 quando um teste falha. '
        .'Se retornou 0, o job tests do CI não detectaria testes falhando.'
    );
})->group('slice-003', 'ac-003');

// AC-004 runtime: grafo de dependências dos jobs garante pipeline completo
test('AC-004 runtime: todos os jobs PHP dependem de harness (pipeline encadeado)', function (): void {
    $ciYmlPath = base_path('.github/workflows/ci.yml');
    $content = file_get_contents($ciYmlPath);

    // Jobs que devem depender de harness para que o check final seja válido
    $phpJobs = ['php-lint', 'php-static', 'php-test', 'php-rector'];

    foreach ($phpJobs as $job) {
        // Extrai bloco do job e verifica needs: harness
        $pattern = sprintf('/%s:.*?needs:\s*(\[.*?\]|harness)/s', preg_quote($job, '/'));
        $hasHarnessDep = (bool) preg_match($pattern, $content);

        expect($hasHarnessDep)->toBeTrue(
            "AC-004: job {$job} deve depender de harness para garantir integridade do pipeline. "
            .'Sem dependência, o job pode rodar antes do harness validar selos.'
        );
    }
})->group('slice-003', 'ac-004');
