<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

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
