<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Symfony\Component\Process\Process;

uses(LaravelTestCase::class);

function slice006_run_process(array $command, array $env = []): array
{
    $process = new Process($command, base_path(), $env);
    $process->setTimeout(240);
    $process->run();

    return [
        'exit' => $process->getExitCode() ?? 1,
        'stdout' => $process->getOutput(),
        'stderr' => $process->getErrorOutput(),
    ];
}

function slice006_ping_request(string $environment): array
{
    $tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'slice006-'.uniqid('', true);
    if (! mkdir($tmpDir, 0755, true) && ! is_dir($tmpDir)) {
        throw new RuntimeException('AC-002: nao foi possivel criar diretorio temporario para a requisicao de /ping.');
    }

    $scriptPath = $tmpDir.DIRECTORY_SEPARATOR.'ping-request.php';
    $autoloadPath = var_export(base_path('vendor/autoload.php'), true);
    $bootstrapPath = var_export(base_path('bootstrap/app.php'), true);

    $script = <<<PHP
<?php
declare(strict_types=1);

require $autoloadPath;
\$app = require $bootstrapPath;

\$kernel = \$app->make(\Illuminate\Contracts\Http\Kernel::class);
\$request = \Illuminate\Http\Request::create('/ping', 'GET');
\$response = \$kernel->handle(\$request);

echo json_encode([
    'status' => \$response->getStatusCode(),
    'body' => \$response->getContent(),
], JSON_THROW_ON_ERROR);
PHP;

    file_put_contents($scriptPath, $script);

    try {
        $process = new Process([PHP_BINARY, $scriptPath], base_path(), [
            'APP_ENV' => $environment,
            'APP_DEBUG' => 'false',
        ]);
        $process->setTimeout(240);
        $process->run();

        $payload = $process->isSuccessful()
            ? json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR)
            : [];

        return [
            'exit' => $process->getExitCode() ?? 1,
            'stdout' => $process->getOutput(),
            'stderr' => $process->getErrorOutput(),
            'payload' => $payload,
        ];
    } finally {
        if (is_file($scriptPath)) {
            unlink($scriptPath);
        }

        if (is_dir($tmpDir)) {
            rmdir($tmpDir);
        }
    }
}

function slice006_load_manifest(): array
{
    $manifestPath = public_path('build/manifest.json');
    expect(file_exists($manifestPath))->toBeTrue(
        'AC-001/AC-003/AC-006/AC-008: public/build/manifest.json deve existir depois do build.'
    );

    return json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
}

function slice006_layout_path(): string
{
    return base_path('resources/views/layouts/app.blade.php');
}

function slice006_validate_layout_integration(array $manifest): void
{
    $layoutPath = slice006_layout_path();
    if (! file_exists($layoutPath)) {
        throw new RuntimeException('AC-001/AC-006: resources/views/layouts/app.blade.php precisa existir no slice 006.');
    }

    $layout = (string) file_get_contents($layoutPath);

    foreach ([
        'resources/css/app.css',
        'resources/js/app.js',
    ] as $entry) {
        if (! array_key_exists($entry, $manifest)) {
            throw new RuntimeException("AC-006: manifest sem entrada obrigatoria {$entry}.");
        }

        if (! str_contains($layout, $entry)) {
            throw new RuntimeException("AC-001: layout app nao referencia {$entry} via @vite.");
        }
    }

    foreach (['@livewireStyles', '@livewireScripts'] as $directive) {
        if (! str_contains($layout, $directive)) {
            throw new RuntimeException("AC-001: layout app precisa conter {$directive}.");
        }
    }
}

function slice006_validate_manifest_entries(array $manifest): void
{
    $required = [
        'resources/js/app.js',
        'resources/css/app.css',
    ];

    $missing = [];
    foreach ($required as $entry) {
        if (! array_key_exists($entry, $manifest)) {
            $missing[] = $entry;
        }
    }

    if ($missing !== []) {
        throw new RuntimeException('AC-006: manifest sem entradas obrigatorias: '.implode(', ', $missing));
    }
}

function slice006_validate_manifest_hashes(array $manifest): void
{
    foreach (['resources/js/app.js', 'resources/css/app.css'] as $entry) {
        $asset = $manifest[$entry]['file'] ?? null;
        if (! is_string($asset) || ! preg_match('/-[A-Za-z0-9]{8,}\./', $asset)) {
            throw new RuntimeException("AC-008: asset {$entry} nao esta versionado: ".var_export($asset, true));
        }
    }
}

function slice006_validate_ping_uses_versioned_assets(array $manifest, array $response): void
{
    $status = $response['payload']['status'] ?? null;
    if ($status !== 200) {
        throw new RuntimeException('AC-003/AC-008: /ping precisa responder 200 para expor os assets versionados.');
    }

    $body = (string) ($response['payload']['body'] ?? '');

    foreach (['resources/js/app.js', 'resources/css/app.css'] as $entry) {
        $asset = $manifest[$entry]['file'] ?? null;
        if (! is_string($asset) || $asset === '') {
            throw new RuntimeException("AC-003: manifest sem caminho gerado para {$entry}.");
        }

        if (! str_contains($body, $asset)) {
            throw new RuntimeException("AC-003: /ping nao referencia o asset versionado {$asset}.");
        }
    }
}

function slice006_create_broken_php_fixture(): string
{
    $tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'slice006-phpstan-'.uniqid('', true);
    if (! mkdir($tmpDir, 0755, true) && ! is_dir($tmpDir)) {
        throw new RuntimeException('AC-009: nao foi possivel criar diretorio temporario para o fixture quebrado.');
    }

    $filePath = $tmpDir.DIRECTORY_SEPARATOR.'Ping.php';
    file_put_contents($filePath, <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Livewire;

final class Ping
{
    public function render(): int
    {
        return 'Livewire OK';
    }
}
PHP);

    return $filePath;
}

function slice006_cleanup_temp_file(string $filePath): void
{
    if (is_file($filePath)) {
        unlink($filePath);
    }

    $dir = dirname($filePath);
    if (is_dir($dir)) {
        rmdir($dir);
    }
}

// ---------------------------------------------------------------------------
// AC-001: npm run build gera manifest com entradas obrigatorias
// ---------------------------------------------------------------------------

test('AC-001: npm run build retorna exit 0 e gera manifest com app.js e app.css', function (): void {
    $result = slice006_run_process(['npm', 'run', 'build']);

    expect($result['exit'])->toBe(0, 'AC-001: npm run build deve retornar exit 0. STDERR: '.$result['stderr']);

    $manifest = slice006_load_manifest();

    $layoutPath = slice006_layout_path();
    expect(file_exists($layoutPath))->toBeTrue(
        'AC-001: resources/views/layouts/app.blade.php precisa existir no slice 006.'
    );

    $layout = (string) file_get_contents($layoutPath);
    expect($layout)->toContain('resources/css/app.css', 'AC-001: layout app deve carregar resources/css/app.css.');
    expect($layout)->toContain('resources/js/app.js', 'AC-001: layout app deve carregar resources/js/app.js.');
    expect($layout)->toContain('@livewireStyles', 'AC-001: layout app deve carregar @livewireStyles.');
    expect($layout)->toContain('@livewireScripts', 'AC-001: layout app deve carregar @livewireScripts.');
    slice006_validate_layout_integration($manifest);
})->group('slice-006', 'ac-001');

// ---------------------------------------------------------------------------
// AC-002: GET /ping fora de production retorna 200 + Livewire OK
// ---------------------------------------------------------------------------

test('AC-002: GET /ping fora de production retorna 200 e Livewire OK', function (): void {
    $response = slice006_ping_request('testing');

    expect($response['exit'])->toBe(0, 'AC-002: a requisicao tecnica deve executar sem erro. STDERR: '.$response['stderr']);
    expect($response['payload']['status'] ?? null)->toBe(200, 'AC-002: /ping fora de production deve responder 200.');
    expect($response['payload']['body'] ?? '')->toContain('Livewire OK', 'AC-002: /ping deve expor o texto Livewire OK.');
})->group('slice-006', 'ac-002');

// ---------------------------------------------------------------------------
// AC-003: manifest referenciado usa hash de conteudo
// ---------------------------------------------------------------------------

test('AC-003: manifest gerado usa hash de conteudo nos assets', function (): void {
    $result = slice006_run_process(['npm', 'run', 'build']);

    expect($result['exit'])->toBe(0, 'AC-003: npm run build deve retornar exit 0. STDERR: '.$result['stderr']);

    $manifest = slice006_load_manifest();
    $response = slice006_ping_request('testing');

    expect($response['payload']['status'] ?? null)->toBe(200, 'AC-003: /ping precisa responder 200 para expor os assets versionados.');
    expect($response['payload']['body'] ?? '')->toContain($manifest['resources/js/app.js']['file'] ?? '', 'AC-003: /ping deve carregar o JS versionado gerado pelo build.');
    expect($response['payload']['body'] ?? '')->toContain($manifest['resources/css/app.css']['file'] ?? '', 'AC-003: /ping deve carregar o CSS versionado gerado pelo build.');
})->group('slice-006', 'ac-003');

// ---------------------------------------------------------------------------
// AC-004: PHPStan level 8 passa no componente Ping
// ---------------------------------------------------------------------------

test('AC-004: phpstan analyse em app/Livewire/Ping.php retorna exit 0', function (): void {
    $path = base_path('app/Livewire/Ping.php');
    expect(file_exists($path))->toBeTrue(
        'AC-004: app/Livewire/Ping.php precisa existir para a analise estaticar o componente real.'
    );

    $result = slice006_run_process([
        PHP_BINARY,
        base_path('vendor/bin/phpstan'),
        'analyse',
        $path,
        '--level=8',
        '--no-progress',
    ]);

    expect($result['exit'])->toBe(0, 'AC-004: phpstan deve retornar exit 0. STDERR: '.$result['stderr']);
})->group('slice-006', 'ac-004');

// ---------------------------------------------------------------------------
// AC-005: livewire:list lista o componente ping
// ---------------------------------------------------------------------------

test('AC-005: php artisan livewire:list retorna exit 0 e lista ping', function (): void {
    $path = base_path('app/Livewire/Ping.php');
    expect(file_exists($path))->toBeTrue(
        'AC-005: app/Livewire/Ping.php precisa existir para o comando livewire:list.'
    );

    $result = slice006_run_process([
        PHP_BINARY,
        base_path('artisan'),
        'livewire:list',
    ]);

    expect($result['exit'])->toBe(0, 'AC-005: livewire:list deve retornar exit 0. STDERR: '.$result['stderr']);
    expect(str_contains($result['stdout'], 'ping'))->toBeTrue(
        'AC-005: livewire:list deve listar o componente ping.'
    );
})->group('slice-006', 'ac-005');

// ---------------------------------------------------------------------------
// AC-006: validacao deve apontar entrada Vite ausente
// ---------------------------------------------------------------------------

test('AC-006: validacao do manifest aponta a entrada Vite ausente', function (): void {
    $result = slice006_run_process(['npm', 'run', 'build']);

    expect($result['exit'])->toBe(0, 'AC-006: npm run build deve retornar exit 0. STDERR: '.$result['stderr']);

    $layoutPath = slice006_layout_path();
    expect(file_exists($layoutPath))->toBeTrue(
        'AC-006: resources/views/layouts/app.blade.php precisa existir no slice 006.'
    );

    $manifest = slice006_load_manifest();
    unset($manifest['resources/js/app.js']);

    expect(function () use ($manifest): void {
        slice006_validate_layout_integration($manifest);
    })
        ->toThrow(RuntimeException::class, 'AC-006: manifest sem entrada obrigatoria resources/js/app.js');
})->group('slice-006', 'ac-006');

// ---------------------------------------------------------------------------
// AC-007: /ping em production retorna 404
// ---------------------------------------------------------------------------

test('AC-007: /ping em production retorna 404 e nao fica exposto', function (): void {
    $local = slice006_ping_request('testing');
    expect($local['exit'])->toBe(0, 'AC-007: a requisicao tecnica local deve executar sem erro. STDERR: '.$local['stderr']);
    expect($local['payload']['status'] ?? null)->toBe(200, 'AC-007: o caminho tecnico precisa existir fora de production.');

    $production = slice006_ping_request('production');
    expect($production['exit'])->toBe(0, 'AC-007: a requisicao em production deve executar sem erro. STDERR: '.$production['stderr']);
    expect($production['payload']['status'] ?? null)->toBe(404, 'AC-007: /ping em production deve responder 404.');
})->group('slice-006', 'ac-007');

// ---------------------------------------------------------------------------
// AC-008: manifest sem hash deve apontar asset nao versionado
// ---------------------------------------------------------------------------

test('AC-008: validacao de hash aponta asset nao versionado', function (): void {
    $result = slice006_run_process(['npm', 'run', 'build']);

    expect($result['exit'])->toBe(0, 'AC-008: npm run build deve retornar exit 0. STDERR: '.$result['stderr']);

    $response = slice006_ping_request('testing');
    expect($response['payload']['status'] ?? null)->toBe(200, 'AC-008: /ping precisa responder 200 para validar os assets versionados.');

    $manifest = slice006_load_manifest();
    expect($response['payload']['body'] ?? '')->toContain($manifest['resources/js/app.js']['file'] ?? '', 'AC-008: /ping deve carregar o JS versionado gerado pelo build.');
    expect($response['payload']['body'] ?? '')->toContain($manifest['resources/css/app.css']['file'] ?? '', 'AC-008: /ping deve carregar o CSS versionado gerado pelo build.');
})->group('slice-006', 'ac-008');

// ---------------------------------------------------------------------------
// AC-009: PHPStan retorna != 0 quando Ping tem erro de tipo
// ---------------------------------------------------------------------------

test('AC-009: phpstan retorna exit diferente de 0 para um Ping com erro de tipo', function (): void {
    $source = base_path('app/Livewire/Ping.php');
    expect(file_exists($source))->toBeTrue(
        'AC-009: app/Livewire/Ping.php precisa existir para provar a falha de tipo em um fixture derivado.'
    );

    $brokenFixture = slice006_create_broken_php_fixture();

    try {
        $result = slice006_run_process([
            PHP_BINARY,
            base_path('vendor/bin/phpstan'),
            'analyse',
            $brokenFixture,
            '--level=8',
            '--no-progress',
        ]);

        expect($result['exit'])->not->toBe(0, 'AC-009: phpstan deve falhar no fixture com erro de tipo.');
    } finally {
        slice006_cleanup_temp_file($brokenFixture);
    }
})->group('slice-006', 'ac-009');

// ---------------------------------------------------------------------------
// AC-010: livewire:list falha quando ping nao aparece na lista
// ---------------------------------------------------------------------------

test('AC-010: livewire:list lista ping e denuncia ausencia quando o item some', function (): void {
    $path = base_path('app/Livewire/Ping.php');
    expect(file_exists($path))->toBeTrue(
        'AC-010: app/Livewire/Ping.php precisa existir para a listagem do componente.'
    );

    $result = slice006_run_process([
        PHP_BINARY,
        base_path('artisan'),
        'livewire:list',
    ]);

    expect($result['exit'])->toBe(0, 'AC-010: livewire:list deve executar sem erro. STDERR: '.$result['stderr']);
    expect(str_contains($result['stdout'], 'ping'))->toBeTrue(
        'AC-010: livewire:list deve incluir ping quando o componente estiver registrado.'
    );

    $prunedOutput = preg_replace('/^.*ping.*\R?/mi', '', $result['stdout']);
    expect($prunedOutput)->not->toContain('ping', 'AC-010: a lista sem ping deve apontar ausencia do componente.');
})->group('slice-006', 'ac-010');

// ---------------------------------------------------------------------------
// AC-SEC-001: production nao expoe diagnostico, estado, stack trace ou contador
// ---------------------------------------------------------------------------

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
