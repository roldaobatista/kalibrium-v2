<?php

declare(strict_types=1);

/**
 * Slice 005 — Healthcheck endpoint GET /health
 *
 * Testes de feature cobrindo os 5 ACs do spec:
 *   AC-001: GET /health retorna HTTP 200 + status "ok" quando DB e Redis estão up
 *   AC-002: Resposta contém os campos status, db, redis, timestamp
 *   AC-003: Com DB indisponível → HTTP 503 + status "degraded" + db "disconnected"
 *   AC-004: A suite tem pelo menos 3 testes (coberto pelos cenários acima)
 *   AC-005: PHPStan level 8 passa no HealthCheckController (teste de artefato)
 *
 * Todos os testes nascem RED — o controller e o middleware ainda não existem.
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

// ---------------------------------------------------------------------------
// AC-001: HTTP 200 + status "ok" quando DB e Redis estão disponíveis
// ---------------------------------------------------------------------------

test('AC-001: GET /health retorna HTTP 200 quando DB e Redis estão up', function (): void {
    // DB::select e Redis::ping devem responder normalmente (mocks simulam sucesso)
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andReturn([]);

    Redis::shouldReceive('ping')
        ->once()
        ->andReturn('PONG');

    $response = $this->getJson('/health');

    $response->assertStatus(200);
})->group('slice-005', 'ac-001');

test('AC-001: GET /health retorna status "ok" no JSON quando todos os componentes estão conectados', function (): void {
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andReturn([]);

    Redis::shouldReceive('ping')
        ->once()
        ->andReturn('PONG');

    $response = $this->getJson('/health');

    $response->assertStatus(200)
        ->assertJson(['status' => 'ok']);
})->group('slice-005', 'ac-001');

// ---------------------------------------------------------------------------
// AC-002: JSON contém os campos status, db, redis, timestamp
// ---------------------------------------------------------------------------

test('AC-002: resposta JSON contém os campos obrigatórios status, db, redis e timestamp', function (): void {
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andReturn([]);

    Redis::shouldReceive('ping')
        ->once()
        ->andReturn('PONG');

    $response = $this->getJson('/health');

    $response->assertStatus(200)
        ->assertJsonStructure(['status', 'db', 'redis', 'timestamp']);
})->group('slice-005', 'ac-002');

test('AC-002: campos db e redis retornam "connected" quando os serviços estão up', function (): void {
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andReturn([]);

    Redis::shouldReceive('ping')
        ->once()
        ->andReturn('PONG');

    $response = $this->getJson('/health');

    $response->assertStatus(200)
        ->assertJson([
            'db' => 'connected',
            'redis' => 'connected',
        ]);
})->group('slice-005', 'ac-002');

test('AC-002: campo timestamp é uma string ISO 8601 com timezone', function (): void {
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andReturn([]);

    Redis::shouldReceive('ping')
        ->once()
        ->andReturn('PONG');

    $response = $this->getJson('/health');

    $response->assertStatus(200);

    $timestamp = $response->json('timestamp');
    expect($timestamp)->toBeString();

    // ISO 8601 com timezone: YYYY-MM-DDTHH:MM:SS+HH:MM ou Z
    expect(preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $timestamp))->toBe(1,
        "AC-002: timestamp deve ser ISO 8601 com timezone, recebeu: {$timestamp}"
    );
})->group('slice-005', 'ac-002');

// ---------------------------------------------------------------------------
// AC-003: DB indisponível → HTTP 503 + status "degraded" + db "disconnected"
// ---------------------------------------------------------------------------

test('AC-003: GET /health retorna HTTP 503 quando DB está indisponível', function (): void {
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andThrow(new Exception('Connection refused'));

    Redis::shouldReceive('ping')
        ->once()
        ->andReturn('PONG');

    $response = $this->getJson('/health');

    $response->assertStatus(503);
})->group('slice-005', 'ac-003');

test('AC-003: status é "degraded" e db é "disconnected" quando DB falha', function (): void {
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andThrow(new Exception('Connection refused'));

    Redis::shouldReceive('ping')
        ->once()
        ->andReturn('PONG');

    $response = $this->getJson('/health');

    $response->assertStatus(503)
        ->assertJson([
            'status' => 'degraded',
            'db' => 'disconnected',
            'redis' => 'connected',
        ]);
})->group('slice-005', 'ac-003');

test('AC-003: GET /health retorna HTTP 503 quando Redis está indisponível', function (): void {
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andReturn([]);

    Redis::shouldReceive('ping')
        ->once()
        ->andThrow(new Exception('Redis connection refused'));

    $response = $this->getJson('/health');

    $response->assertStatus(503)
        ->assertJson([
            'status' => 'degraded',
            'db' => 'connected',
            'redis' => 'disconnected',
        ]);
})->group('slice-005', 'ac-003');

// ---------------------------------------------------------------------------
// AC-004: meta — suite tem pelo menos 3 testes (garantido pelos cenários acima)
// AC-004 é coberto estruturalmente — os 3+ testes dos ACs anteriores satisfazem
// este critério. O teste abaixo verifica que o arquivo de testes existe e pode
// ser filtrado via --filter=HealthCheckTest
// ---------------------------------------------------------------------------

test('AC-004: HealthCheckController existe para que composer test --filter=HealthCheckTest funcione end-to-end', function (): void {
    // AC-004 exige que `composer test --filter=HealthCheckTest` passe com 3+ testes.
    // Para isso, o controller precisa existir e os testes de feature (AC-001/002/003)
    // precisam passar. Este teste valida a pré-condição: controller existe.
    $controllerPath = app_path('Http/Controllers/HealthCheckController.php');

    expect(file_exists($controllerPath))->toBeTrue(
        'AC-004 requer HealthCheckController.php para que os testes de feature (ok, db-fail, redis-fail) passem.'
    );

    // Verifica que o controller é invocável (__invoke)
    $content = file_get_contents($controllerPath);
    expect(str_contains($content, '__invoke'))->toBeTrue(
        'AC-004 requer controller invocável com método __invoke.'
    );
})->group('slice-005', 'ac-004');

// ---------------------------------------------------------------------------
// AC-005: PHPStan level 8 passa no HealthCheckController
// ---------------------------------------------------------------------------

test('AC-005: HealthCheckController.php existe para análise do PHPStan', function (): void {
    $path = base_path('app/Http/Controllers/HealthCheckController.php');

    expect(file_exists($path))->toBeTrue(
        'AC-005 requer app/Http/Controllers/HealthCheckController.php para o phpstan analyse --level=8.'
    );
})->group('slice-005', 'ac-005');

test('AC-005: PHPStan level 8 não reporta erros no HealthCheckController', function (): void {
    $controllerPath = base_path('app/Http/Controllers/HealthCheckController.php');

    expect(file_exists($controllerPath))->toBeTrue(
        'AC-005: HealthCheckController.php deve existir antes de rodar PHPStan.'
    );

    $phpstanBin = base_path('vendor/bin/phpstan');

    expect(file_exists($phpstanBin))->toBeTrue(
        'AC-005: vendor/bin/phpstan deve existir (instalado via composer).'
    );

    $output = '';
    $exitCode = 0;

    exec(
        sprintf(
            'cd %s && %s analyse %s --level=8 --no-progress 2>&1',
            escapeshellarg(base_path()),
            escapeshellarg($phpstanBin),
            escapeshellarg($controllerPath)
        ),
        $outputLines,
        $exitCode
    );

    $output = implode("\n", $outputLines);

    expect($exitCode)->toBe(0,
        "AC-005: PHPStan level 8 reportou erros no HealthCheckController:\n{$output}"
    );
})->group('slice-005', 'ac-005');
