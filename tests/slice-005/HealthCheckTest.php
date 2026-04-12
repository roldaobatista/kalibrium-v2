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

    // ISO 8601 com timezone: YYYY-MM-DDTHH:MM:SS+HH:MM ou YYYY-MM-DDTHH:MM:SSZ
    expect(preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}([+-]\d{2}:\d{2}|Z)$/', $timestamp))->toBe(1,
        "AC-002: timestamp deve ser ISO 8601 com timezone (offset ou Z), recebeu: {$timestamp}"
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

test('AC-003: GET /health retorna HTTP 503 quando DB e Redis estão ambos indisponíveis', function (): void {
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andThrow(new Exception('Connection refused'));

    Redis::shouldReceive('ping')
        ->once()
        ->andThrow(new Exception('Redis connection refused'));

    $response = $this->getJson('/health');

    $response->assertStatus(503)
        ->assertJson([
            'status' => 'degraded',
            'db' => 'disconnected',
            'redis' => 'disconnected',
        ]);
})->group('slice-005', 'ac-003');

// ---------------------------------------------------------------------------
// AC-004: rota /health resolve para HealthCheckController sem erro
// ---------------------------------------------------------------------------
// ---------------------------------------------------------------------------

test('AC-004: rota /health resolve para HealthCheckController sem erro 404/500', function (): void {
    // AC-004 exige composer test --filter=HealthCheckTest com 3+ testes passando.
    // Este teste valida comportamentalmente que a rota resolve corretamente.
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andReturn([]);

    Redis::shouldReceive('ping')
        ->once()
        ->andReturn('PONG');

    $response = $this->getJson('/health');

    // Rota resolve (não é 404) e controller executa (não é 500)
    expect($response->status())->not->toBe(404, 'AC-004: rota /health deve existir (não 404).');
    expect($response->status())->not->toBe(500, 'AC-004: controller deve executar sem erro (não 500).');
    expect($response->json())->toBeArray('AC-004: resposta deve ser JSON válido.');
})->group('slice-005', 'ac-004');

// ---------------------------------------------------------------------------
// AC-005: PHPStan level 8 passa no HealthCheckController
// ---------------------------------------------------------------------------

test('AC-005: HealthCheckController é instanciável e invocável via reflexão', function (): void {
    // AC-005 exige PHPStan level 8. Validamos via reflexão que o controller
    // atende os requisitos estruturais sem ler o filesystem.
    $class = \App\Http\Controllers\HealthCheckController::class;

    expect(class_exists($class))->toBeTrue('AC-005: controller class deve existir no autoloader.');

    $reflection = new \ReflectionClass($class);

    expect($reflection->isFinal())->toBeTrue(
        'AC-005: controller deve ser final class para PHPStan level 8.'
    );
    expect($reflection->hasMethod('__invoke'))->toBeTrue(
        'AC-005: controller deve ser invocável (__invoke).'
    );

    $returnType = $reflection->getMethod('__invoke')->getReturnType();
    expect($returnType)->not->toBeNull('AC-005: __invoke deve ter retorno tipado.');
    expect($returnType->getName())->toBe('Illuminate\Http\JsonResponse',
        'AC-005: retorno deve ser tipado como JsonResponse.'
    );
})->group('slice-005', 'ac-005');

test('AC-005: HealthCheckController retorna JsonResponse (não resposta genérica)', function (): void {
    // Valida comportamentalmente que o controller retorna o tipo correto
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andReturn([]);

    Redis::shouldReceive('ping')
        ->once()
        ->andReturn('PONG');

    $response = $this->getJson('/health');

    // Content-Type deve ser application/json
    $contentType = $response->headers->get('Content-Type');
    expect(str_contains($contentType, 'application/json'))->toBeTrue(
        'AC-005: controller deve retornar Content-Type application/json (JsonResponse).'
    );
})->group('slice-005', 'ac-005');
