<?php

declare(strict_types=1);

/**
 * Slice 004 — Deploy staging automatizado (GitHub Actions → VPS)
 *
 * Testes locais que verificam artefatos de infraestrutura verificáveis sem VPS:
 *   AC-001: deploy-staging.yml com trigger workflow_run referenciando ci.yml
 *   AC-002: Nginx config com server_name e root corretos
 *   AC-003: composer.json com laravel/horizon + supervisor config com autorestart=true
 *   AC-004: config/logging.php com canal daily_json e JsonFormatter
 *   AC-005: routes/console.php registra schedule com heartbeat
 */

// ---------------------------------------------------------------------------
// AC-001: workflow deploy-staging.yml existe com trigger correto
// ---------------------------------------------------------------------------

test('AC-001: deploy-staging.yml existe no diretório .github/workflows', function (): void {
    $path = base_path('.github/workflows/deploy-staging.yml');

    expect(file_exists($path))->toBeTrue(
        'AC-001 requer o arquivo .github/workflows/deploy-staging.yml para o deploy automatizado.'
    );
})->group('slice-004', 'ac-001');

test('AC-001: deploy-staging.yml usa trigger workflow_run (não push direto)', function (): void {
    $path = base_path('.github/workflows/deploy-staging.yml');
    expect(file_exists($path))->toBeTrue("deploy-staging.yml não encontrado em {$path}");

    $content = file_get_contents($path);

    expect(str_contains($content, 'workflow_run:'))->toBeTrue(
        'AC-001 requer trigger workflow_run para que deploy só dispare após CI verde.'
    );
})->group('slice-004', 'ac-001');

test('AC-001: deploy-staging.yml referencia o workflow "CI" (ci.yml)', function (): void {
    $path = base_path('.github/workflows/deploy-staging.yml');
    expect(file_exists($path))->toBeTrue("deploy-staging.yml não encontrado em {$path}");

    $content = file_get_contents($path);

    // O trigger workflow_run deve referenciar o nome exato "CI" (definido em ci.yml)
    expect(preg_match('/workflows:\s*\[?\s*["\']CI["\']/', $content))->toBe(1,
        'AC-001 requer workflows: ["CI"] para encadear com o ci.yml do slice-003.'
    );
})->group('slice-004', 'ac-001');

test('AC-001: deploy-staging.yml condiciona execução a conclusion == success', function (): void {
    $path = base_path('.github/workflows/deploy-staging.yml');
    expect(file_exists($path))->toBeTrue("deploy-staging.yml não encontrado em {$path}");

    $content = file_get_contents($path);

    expect(str_contains($content, "conclusion == 'success'"))->toBeTrue(
        'AC-001 requer if: github.event.workflow_run.conclusion == \'success\' para bloquear deploy em CI vermelho.'
    );
})->group('slice-004', 'ac-001');

// ---------------------------------------------------------------------------
// AC-002: Nginx config com server_name e root corretos
// ---------------------------------------------------------------------------

test('AC-002: infra/nginx/kalibrium-staging.conf existe', function (): void {
    $path = base_path('infra/nginx/kalibrium-staging.conf');

    expect(file_exists($path))->toBeTrue(
        'AC-002 requer infra/nginx/kalibrium-staging.conf para o virtual host de staging.'
    );
})->group('slice-004', 'ac-002');

test('AC-002: Nginx config define server_name staging.kalibrium.com.br', function (): void {
    $path = base_path('infra/nginx/kalibrium-staging.conf');
    expect(file_exists($path))->toBeTrue("kalibrium-staging.conf não encontrado em {$path}");

    $content = file_get_contents($path);

    expect(str_contains($content, 'staging.kalibrium.com.br'))->toBeTrue(
        'AC-002 requer server_name staging.kalibrium.com.br no config Nginx.'
    );
})->group('slice-004', 'ac-002');

test('AC-002: Nginx config aponta root para public/', function (): void {
    $path = base_path('infra/nginx/kalibrium-staging.conf');
    expect(file_exists($path))->toBeTrue("kalibrium-staging.conf não encontrado em {$path}");

    $content = file_get_contents($path);

    // root deve apontar para .../public (pode ser /var/www/kalibrium/public ou similar)
    expect(preg_match('/root\s+.*\/public[;\s]/', $content))->toBe(1,
        'AC-002 requer que a diretiva root aponte para o diretório public/ da aplicação Laravel.'
    );
})->group('slice-004', 'ac-002');

// ---------------------------------------------------------------------------
// AC-003: laravel/horizon no composer.json + supervisor com autorestart=true
// ---------------------------------------------------------------------------

test('AC-003: laravel/horizon está em require no composer.json', function (): void {
    $path = base_path('composer.json');
    expect(file_exists($path))->toBeTrue("composer.json não encontrado em {$path}");

    $json = json_decode(file_get_contents($path), true);

    expect(isset($json['require']['laravel/horizon']))->toBeTrue(
        'AC-003 requer laravel/horizon em require (não require-dev) do composer.json.'
    );
})->group('slice-004', 'ac-003');

test('AC-003: infra/supervisor/horizon-staging.conf existe', function (): void {
    $path = base_path('infra/supervisor/horizon-staging.conf');

    expect(file_exists($path))->toBeTrue(
        'AC-003 requer infra/supervisor/horizon-staging.conf para o Supervisor gerenciar o Horizon.'
    );
})->group('slice-004', 'ac-003');

test('AC-003: supervisor config define autorestart=true para o Horizon', function (): void {
    $path = base_path('infra/supervisor/horizon-staging.conf');
    expect(file_exists($path))->toBeTrue("horizon-staging.conf não encontrado em {$path}");

    $content = file_get_contents($path);

    expect(str_contains($content, 'autorestart=true'))->toBeTrue(
        'AC-003 requer autorestart=true no config do Supervisor para que horizon:terminate resulte em reinício automático.'
    );
})->group('slice-004', 'ac-003');

test('AC-003: supervisor config define autostart=true para o Horizon', function (): void {
    $path = base_path('infra/supervisor/horizon-staging.conf');
    expect(file_exists($path))->toBeTrue("horizon-staging.conf não encontrado em {$path}");

    $content = file_get_contents($path);

    expect(str_contains($content, 'autostart=true'))->toBeTrue(
        'AC-003 requer autostart=true para que o Horizon suba automaticamente após boot do VPS.'
    );
})->group('slice-004', 'ac-003');

// ---------------------------------------------------------------------------
// AC-004: config/logging.php com canal daily_json e JsonFormatter
// ---------------------------------------------------------------------------

test('AC-004: config/logging.php define canal daily_json', function (): void {
    $path = config_path('logging.php');
    expect(file_exists($path))->toBeTrue("config/logging.php não encontrado em {$path}");

    $content = file_get_contents($path);

    expect(str_contains($content, 'daily_json'))->toBeTrue(
        'AC-004 requer canal daily_json em config/logging.php para log em JSON rotativo.'
    );
})->group('slice-004', 'ac-004');

test('AC-004: canal daily_json usa JsonFormatter do Monolog', function (): void {
    $path = config_path('logging.php');
    expect(file_exists($path))->toBeTrue("config/logging.php não encontrado em {$path}");

    $content = file_get_contents($path);

    expect(str_contains($content, 'JsonFormatter'))->toBeTrue(
        'AC-004 requer Monolog\Formatter\JsonFormatter configurado no canal daily_json.'
    );
})->group('slice-004', 'ac-004');

test('AC-004: canal daily_json usa driver daily (rotação automática)', function (): void {
    $path = config_path('logging.php');
    expect(file_exists($path))->toBeTrue("config/logging.php não encontrado em {$path}");

    // Carrega o array de config para inspecionar a estrutura real
    $config = require $path;

    expect(isset($config['channels']['daily_json']))->toBeTrue(
        'AC-004 requer que o canal daily_json exista no array channels de config/logging.php.'
    );

    expect($config['channels']['daily_json']['driver'] ?? '')->toBe('daily',
        'AC-004 requer driver: daily no canal daily_json para rotação automática de arquivos de log.'
    );
})->group('slice-004', 'ac-004');

// ---------------------------------------------------------------------------
// AC-005: routes/console.php registra heartbeat no Scheduler
// ---------------------------------------------------------------------------

test('AC-005: routes/console.php contém registro do heartbeat', function (): void {
    $path = base_path('routes/console.php');
    expect(file_exists($path))->toBeTrue("routes/console.php não encontrado em {$path}");

    $content = file_get_contents($path);

    expect(str_contains($content, 'heartbeat'))->toBeTrue(
        'AC-005 requer que routes/console.php registre um schedule com nome/chave heartbeat (não apenas exista).'
    );
})->group('slice-004', 'ac-005');

test('AC-005: routes/console.php registra o job de heartbeat do scheduler', function (): void {
    $path = base_path('routes/console.php');
    expect(file_exists($path))->toBeTrue("routes/console.php não encontrado em {$path}");

    $content = file_get_contents($path);

    expect(str_contains($content, 'heartbeat'))->toBeTrue(
        'AC-005 requer que routes/console.php registre um schedule com nome/chave heartbeat.'
    );
})->group('slice-004', 'ac-005');

test('AC-005: heartbeat é agendado com everyMinute()', function (): void {
    $path = base_path('routes/console.php');
    expect(file_exists($path))->toBeTrue("routes/console.php não encontrado em {$path}");

    $content = file_get_contents($path);

    expect(str_contains($content, 'everyMinute()'))->toBeTrue(
        'AC-005 requer everyMinute() no schedule do heartbeat para que a crontab de 1 em 1 minuto o execute.'
    );
})->group('slice-004', 'ac-005');

test('AC-005: heartbeat usa withoutOverlapping() para evitar execuções concorrentes', function (): void {
    $path = base_path('routes/console.php');
    expect(file_exists($path))->toBeTrue("routes/console.php não encontrado em {$path}");

    $content = file_get_contents($path);

    expect(str_contains($content, 'withoutOverlapping()'))->toBeTrue(
        'AC-005 requer withoutOverlapping() no heartbeat para evitar sobreposição de execuções do Scheduler.'
    );
})->group('slice-004', 'ac-005');
