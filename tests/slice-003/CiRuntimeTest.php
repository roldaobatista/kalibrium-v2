<?php

declare(strict_types=1);

/**
 * Slice 003 — Testes de runtime para AC-001, AC-002, AC-003, AC-004.
 *
 * Exercita ferramentas reais com input válido e inválido para provar que
 * o CI detectaria problemas (error path) e aceita código correto (happy path).
 */

/**
 * Cria diretório temporário, escreve arquivo PHP, executa comando e retorna exit code.
 */
function runToolOnTempFile(string $command, string $phpContent): int
{
    $tmpDir = sys_get_temp_dir().'/slice003-'.uniqid();
    mkdir($tmpDir, 0755, true);

    $filePath = $tmpDir.'/TestFile.php';
    file_put_contents($filePath, $phpContent);

    $exitCode = null;
    $fullCommand = str_replace(
        ['{FILE}', '{DIR}'],
        [escapeshellarg($filePath), escapeshellarg($tmpDir)],
        $command,
    );
    exec($fullCommand.' 2>&1', $output, $exitCode);

    unlink($filePath);
    rmdir($tmpDir);

    return $exitCode;
}

// --- Verificação de pré-requisitos (TEST-006) ---

test('pré-requisito: vendor/bin/pint existe', function (): void {
    expect(file_exists(base_path('vendor/bin/pint')))->toBeTrue(
        'vendor/bin/pint não encontrado — rode composer install antes dos testes.'
    );
})->group('slice-003', 'tooling');

test('pré-requisito: vendor/bin/phpstan existe', function (): void {
    expect(file_exists(base_path('vendor/bin/phpstan')))->toBeTrue(
        'vendor/bin/phpstan não encontrado — rode composer install antes dos testes.'
    );
})->group('slice-003', 'tooling');

test('pré-requisito: vendor/bin/pest existe', function (): void {
    expect(file_exists(base_path('vendor/bin/pest')))->toBeTrue(
        'vendor/bin/pest não encontrado — rode composer install antes dos testes.'
    );
})->group('slice-003', 'tooling');

// --- AC-001: Pint ---

test('AC-001 error path: pint --test falha com código mal formatado', function (): void {
    $command = sprintf('php %s --test --config %s {DIR}',
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
})->group('slice-003', 'ac-001', 'tooling');

test('AC-001 happy path: pint --test aceita código bem formatado', function (): void {
    $command = sprintf('php %s --test --config %s {DIR}',
        escapeshellarg(base_path('vendor/bin/pint')),
        escapeshellarg(base_path('pint.json')),
    );

    $exitCode = runToolOnTempFile($command, "<?php\n\ndeclare(strict_types=1);\n\nfunction wellFormatted(int \$x, int \$y): int\n{\n    return \$x + \$y;\n}\n");

    expect($exitCode)->toBe(0,
        'AC-001: Pint deve retornar exit 0 para código bem formatado.'
    );
})->group('slice-003', 'ac-001', 'tooling');

// --- AC-002: PHPStan ---

test('AC-002 error path: phpstan falha com erro de tipo nível 8', function (): void {
    $command = sprintf('php %s analyse --level=8 --no-progress --error-format=raw {FILE}',
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
})->group('slice-003', 'ac-002', 'tooling');

test('AC-002 happy path: phpstan aceita código tipado corretamente', function (): void {
    $command = sprintf('php %s analyse --level=8 --no-progress --error-format=raw {FILE}',
        escapeshellarg(base_path('vendor/bin/phpstan')),
    );

    $exitCode = runToolOnTempFile($command, <<<'PHP'
<?php

declare(strict_types=1);

function getNumber(): int
{
    return 42;
}
PHP);

    expect($exitCode)->toBe(0,
        'AC-002: PHPStan nível 8 deve retornar exit 0 para código sem erros de tipo.'
    );
})->group('slice-003', 'ac-002', 'tooling');

// --- AC-003: Pest ---

test('AC-003 error path: pest falha quando um teste está vermelho', function (): void {
    $command = sprintf('php %s {FILE} --no-coverage',
        escapeshellarg(base_path('vendor/bin/pest')),
    );

    $exitCode = runToolOnTempFile($command, <<<'PHP'
<?php

test('falha de propósito', function (): void {
    expect(true)->toBeFalse();
});
PHP);

    expect($exitCode)->not->toBe(0,
        'AC-003: Pest deve retornar exit != 0 quando um teste falha.'
    );
})->group('slice-003', 'ac-003', 'tooling');

test('AC-003 happy path: pest aceita quando todos os testes passam', function (): void {
    $command = sprintf('php %s {FILE} --no-coverage',
        escapeshellarg(base_path('vendor/bin/pest')),
    );

    $exitCode = runToolOnTempFile($command, <<<'PHP'
<?php

test('passa de propósito', function (): void {
    expect(true)->toBeTrue();
});
PHP);

    expect($exitCode)->toBe(0,
        'AC-003: Pest deve retornar exit 0 quando todos os testes passam.'
    );
})->group('slice-003', 'ac-003', 'tooling');

// --- AC-004: Dependência de harness para todos os jobs ---

test('AC-004: todos os jobs dependem de harness (pipeline encadeado)', function (): void {
    $ciYmlPath = base_path('.github/workflows/ci.yml');
    $content = file_get_contents($ciYmlPath);

    // Todos os jobs que devem depender de harness (PHP + JS + security)
    $allJobs = ['php-lint', 'php-static', 'php-test', 'php-rector', 'js-lint', 'security'];

    foreach ($allJobs as $job) {
        preg_match(sprintf('/^  %s:.*?(?=\n  \w|\z)/ms', preg_quote($job, '/')), $content, $block);
        $jobBlock = $block[0] ?? '';

        expect(str_contains($jobBlock, 'needs: harness'))->toBeTrue(
            "AC-004: job {$job} deve conter 'needs: harness' para garantir integridade do pipeline."
        );
    }
})->group('slice-003', 'ac-004', 'tooling');
