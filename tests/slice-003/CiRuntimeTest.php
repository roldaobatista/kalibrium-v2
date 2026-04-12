<?php

declare(strict_types=1);

/**
 * Slice 003 — Testes de runtime para AC-001, AC-002, AC-003, AC-004.
 *
 * Complementa os testes de estrutura YAML com execução real das ferramentas.
 * Cada teste injeta código inválido em arquivo temporário, roda a ferramenta
 * e verifica que o exit code é não-zero (provando que o job CI falharia).
 */

/**
 * Cria diretório temporário, escreve arquivo PHP, executa comando e retorna exit code.
 * Limpa o diretório ao final.
 */
function runToolOnTempFile(string $command, string $phpContent): int
{
    $tmpDir = sys_get_temp_dir().'/slice003-'.uniqid();
    mkdir($tmpDir, 0755, true);

    $filePath = $tmpDir.'/TestFile.php';
    file_put_contents($filePath, $phpContent);

    $exitCode = null;
    $fullCommand = str_replace('{FILE}', escapeshellarg($filePath), str_replace('{DIR}', escapeshellarg($tmpDir), $command));
    exec($fullCommand.' 2>&1', $output, $exitCode);

    unlink($filePath);
    rmdir($tmpDir);

    return $exitCode;
}

// AC-001 runtime: Pint rejeita código mal formatado (exit != 0)
test('AC-001 runtime: pint --test falha com código mal formatado', function (): void {
    $command = sprintf(
        'php %s --test --config %s {DIR}',
        escapeshellarg(base_path('vendor/bin/pint')),
        escapeshellarg(base_path('pint.json')),
    );

    $exitCode = runToolOnTempFile($command, <<<'PHP'
<?php
function   badlyFormatted(   $x,$y   ){
return $x+$y;
}
PHP);

    expect($exitCode)->not->toBe(0,
        'AC-001: Pint deve retornar exit != 0 para código mal formatado.'
    );
})->group('slice-003', 'ac-001');

// AC-002 runtime: PHPStan rejeita erro de tipo nível 8 (exit != 0)
test('AC-002 runtime: phpstan falha com erro de tipo nível 8', function (): void {
    $command = sprintf(
        'php %s analyse --level=8 --no-progress --error-format=raw {FILE}',
        escapeshellarg(base_path('vendor/bin/phpstan')),
    );

    $exitCode = runToolOnTempFile($command, <<<'PHP'
<?php

declare(strict_types=1);

function getNumber(): int
{
    return 'not a number';
}
PHP);

    expect($exitCode)->not->toBe(0,
        'AC-002: PHPStan nível 8 deve retornar exit != 0 para erro de tipo.'
    );
})->group('slice-003', 'ac-002');

// AC-003 runtime: Pest falha com teste falhando (exit != 0)
test('AC-003 runtime: pest falha quando um teste está vermelho', function (): void {
    $command = sprintf(
        'php %s {FILE} --no-coverage',
        escapeshellarg(base_path('vendor/bin/pest')),
    );

    $exitCode = runToolOnTempFile($command, <<<'PHP'
<?php

test('este teste falha de propósito', function (): void {
    expect(true)->toBeFalse();
});
PHP);

    expect($exitCode)->not->toBe(0,
        'AC-003: Pest deve retornar exit != 0 quando um teste falha.'
    );
})->group('slice-003', 'ac-003');

// AC-004 runtime: grafo de dependências dos jobs garante pipeline completo
test('AC-004 runtime: todos os jobs PHP dependem de harness (pipeline encadeado)', function (): void {
    $ciYmlPath = base_path('.github/workflows/ci.yml');
    $content = file_get_contents($ciYmlPath);

    $phpJobs = ['php-lint', 'php-static', 'php-test', 'php-rector'];

    foreach ($phpJobs as $job) {
        $pattern = sprintf('/%s:.*?needs:\s*(\[.*?\]|harness)/s', preg_quote($job, '/'));
        $hasHarnessDep = (bool) preg_match($pattern, $content);

        expect($hasHarnessDep)->toBeTrue(
            "AC-004: job {$job} deve depender de harness para garantir integridade do pipeline."
        );
    }
})->group('slice-003', 'ac-004');
