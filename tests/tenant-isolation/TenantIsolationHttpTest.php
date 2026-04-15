<?php

declare(strict_types=1);

/**
 * Suite de isolamento — HTTP / Rotas (AC-002, AC-010, AC-011, AC-016)
 *
 * Data provider itera rotas autenticadas com IDs cross-tenant.
 * Cobre: query string/header forjado (AC-010), batch de IDs (AC-011),
 * SQL injection em parâmetro de rota (AC-016).
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-011', 'tenant-isolation');

// ---------------------------------------------------------------------------
// AC-002: Rota autenticada com ID de recurso do tenant B → 404 ou 403
// ---------------------------------------------------------------------------

/**
 * @ac AC-002
 */
dataset('authenticated_routes_with_resource_ids', function () {
    return [
        'GET /users/{id} cross-tenant'           => ['GET', '/users/{id}'],
        'GET /plans/{id} cross-tenant'            => ['GET', '/plans/{id}'],
        'GET /consent-subjects/{id} cross-tenant' => ['GET', '/consent-subjects/{id}'],
        'DELETE /users/{id} cross-tenant'         => ['DELETE', '/users/{id}'],
    ];
});

test('AC-002: rota autenticada com ID de recurso do tenant B retorna 404 ou 403, nunca 200 com dados do tenant B', function (string $method, string $uriPattern) {
    /** @ac AC-002 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $idFromTenantB = $this->resolveResourceIdFromTenantB($uriPattern, $tenantB);

    if ($idFromTenantB === null) {
        $this->markTestIncomplete(
            "AC-002: Nenhum recurso do tenant B encontrado para {$uriPattern}. ".
            'Popule a fixture ou verifique se a rota existe.'
        );
    }

    $uri = str_replace('{id}', (string) $idFromTenantB, $uriPattern);

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->call($method, $uri);

    $statusCode = $response->getStatusCode();

    expect($statusCode)
        ->toBeIn([403, 404],
            "Rota {$method} {$uri} retornou HTTP {$statusCode} com ID do tenant B. ".
            'Esperado 403 ou 404. Possível vazamento cross-tenant.'
        );

    // Garantia adicional: payload não deve conter o ID do tenant B em hipótese alguma
    $content = $response->getContent() ?? '';
    expect($content)
        ->not->toContain((string) $tenantB->id,
            "Resposta de {$method} {$uri} contém o ID do tenant B ({$tenantB->id})."
        );
})->with('authenticated_routes_with_resource_ids');

// ---------------------------------------------------------------------------
// AC-010: Query string ?tenant=B não altera contexto
// ---------------------------------------------------------------------------

test('AC-010: query string tenant=B não altera contexto — contexto permanece no tenant A', function () {
    /** @ac AC-010 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/api/tenant-context?tenant={$tenantB->id}");

    $content = $response->getContent() ?? '';

    // Rota deve existir e retornar 200 — sem isso o teste passa trivialmente por rota inexistente
    expect($response->status())
        ->toBe(200,
            'AC-010: Rota GET /api/tenant-context nao existe ou nao retorna 200. '.
            'Implemente a rota que expoe o tenant_id do contexto atual da sessao.'
        );

    // A resposta deve conter o tenant A (prova que a rota funciona)
    expect($content)
        ->toContain((string) $tenantA->id,
            'AC-010: Rota /api/tenant-context nao retornou o tenant_id do tenant A. '.
            'Implemente TenantContextController@show retornando {"tenant_id": <id>}.'
        );

    // O contexto não deve mudar para o tenant B
    expect($content)
        ->not->toContain((string) $tenantB->id,
            'AC-010: Query string ?tenant=B foi aceita e o tenant B aparece na resposta. '.
            'O middleware deve ignorar esta tentativa de forcar o contexto.'
        );
});

test('AC-010: header X-Tenant forjado não altera contexto — contexto permanece no tenant A', function () {
    /** @ac AC-010 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->withHeader('X-Tenant', (string) $tenantB->id)
        ->get('/api/tenant-context');

    $content = $response->getContent() ?? '';

    // Rota deve existir e retornar 200 — sem isso o teste passa trivialmente por rota inexistente
    expect($response->status())
        ->toBe(200,
            'AC-010: Rota GET /api/tenant-context nao existe ou nao retorna 200. '.
            'Implemente a rota que expoe o tenant_id do contexto atual da sessao.'
        );

    // A resposta deve conter o tenant A (prova que o contexto correto está ativo)
    expect($content)
        ->toContain((string) $tenantA->id,
            'AC-010: Com header X-Tenant forjado para B, a resposta nao contem tenant A. '.
            'O middleware deve ignorar o header e manter o contexto da sessao.'
        );

    expect($content)
        ->not->toContain((string) $tenantB->id,
            'AC-010: Header X-Tenant forjado foi aceito — tenant B aparece na resposta. '.
            'O middleware deve ignorar este header nao autorizado.'
        );
});

// ---------------------------------------------------------------------------
// AC-011: Batch de IDs com qualquer ID do tenant B → rejeitado inteiro (403)
// ---------------------------------------------------------------------------

test('AC-011: batch DELETE com IDs misturados (A + B) é rejeitado inteiro com 403 — nenhum ID processado', function () {
    /** @ac AC-011 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $sensitiveTable = $this->firstSensitiveTable();
    $recordA = DB::table($sensitiveTable)->where('tenant_id', $tenantA->id)->first();
    $recordB = DB::table($sensitiveTable)->where('tenant_id', $tenantB->id)->first();

    if ($recordA === null || $recordB === null) {
        $this->markTestIncomplete(
            'AC-011: Necessário pelo menos 1 registro em cada tenant. Popule a fixture.'
        );
    }

    $batchIds = implode(',', [$recordA->id, $recordB->id]);
    $countBefore = DB::table($sensitiveTable)->where('tenant_id', $tenantA->id)->count();

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->delete("/api/calibrations?ids={$batchIds}");

    $statusCode = $response->getStatusCode();

    expect($statusCode)
        ->toBe(403,
            "AC-011: Batch com IDs cross-tenant retornou HTTP {$statusCode}. ".
            'Esperado 403 — operação deve ser rejeitada inteira.'
        );

    // Verifica que NENHUM registro foi deletado
    $countAfter = DB::table($sensitiveTable)->where('tenant_id', $tenantA->id)->count();
    expect($countAfter)
        ->toBe($countBefore,
            'AC-011: Batch cross-tenant foi processado parcialmente — '.
            "registros do tenant A foram afetados ({$countBefore} → {$countAfter})."
        );
});

// ---------------------------------------------------------------------------
// AC-016: SQL injection em parâmetro de rota → 404/403, sem dados do tenant B
// ---------------------------------------------------------------------------

/**
 * @ac AC-016
 */
dataset('sql_injection_payloads', function () {
    return [
        'OR 1=1 clássico'          => ['1 OR 1=1'],
        'UNION SELECT'              => ['1 UNION SELECT id,name FROM tenants--'],
        'Aspas simples'             => ["1' OR '1'='1"],
        'Ponto e vírgula DROP'      => ['1; DROP TABLE users; --'],
        'Subquery tenant_id'        => ['0 OR (SELECT tenant_id FROM users LIMIT 1) IS NOT NULL'],
        'OR negação de tenant'      => ['1 OR tenant_id != 999'],
        'Comentário SQL inline'     => ['1/* comment */OR/* */1=1'],
    ];
});

test('AC-016: SQL injection em parâmetro de rota retorna 404 ou 403 sem expor dados do tenant B', function (string $payload) {
    /** @ac AC-016 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    // Pre-condicao: a rota /instrumentos/{id} deve estar registrada.
    // Sem um ID de injecao, deve retornar 200 (recurso do tenant A) ou 403 — nunca 404 de rota nao registrada.
    $recordA = DB::table('instruments')->where('tenant_id', $tenantA->id)->first()
        ?? DB::table('calibrations')->where('tenant_id', $tenantA->id)->first();

    if ($recordA === null) {
        $this->markTestIncomplete(
            'AC-016: Nenhum instrumento/calibracao do tenant A encontrado para pré-condicao. Popule a fixture.'
        );
    }

    $baseResponse = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get('/instrumentos/'.(string) $recordA->id);

    expect($baseResponse->status())
        ->toBeIn([200, 403],
            'AC-016: Rota GET /instrumentos/{id} retornou '.$baseResponse->status().' para ID valido do tenant A. '.
            'Esperado 200 ou 403. Implemente InstrumentoController@show com escopo de tenant.'
        );

    $encodedPayload = urlencode($payload);
    $uri = "/instrumentos/{$encodedPayload}";

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get($uri);

    $statusCode = $response->getStatusCode();
    $content = $response->getContent() ?? '';

    // Nunca deve retornar 200 com dados em resposta a payload de injeção
    expect($statusCode)
        ->toBeIn([400, 403, 404, 422],
            "AC-016: SQL injection '{$payload}' retornou HTTP {$statusCode}. ".
            'Esperado 400/403/404/422 — nunca 200 com dados.'
        );

    expect($content)
        ->not->toContain((string) $tenantB->id,
            "AC-016: SQL injection '{$payload}' revelou ID do tenant B na resposta."
        );

    if (! empty($tenantB->name)) {
        expect($content)
            ->not->toContain((string) $tenantB->name,
                "AC-016: SQL injection '{$payload}' revelou nome do tenant B."
            );
    }
})->with('sql_injection_payloads');

test('AC-016: tentativa de SQL injection registra log com tenant_context do tenant A', function () {
    /** @ac AC-016 */
    $tenantA = $this->tenantA();

    $capturedLogs = [];
    Log::listen(function ($log) use (&$capturedLogs) {
        $capturedLogs[] = [
            'level'   => $log->level,
            'message' => $log->message,
            'context' => $log->context,
        ];
    });

    $injectionPayload = urlencode('1 OR 1=1');

    $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/instrumentos/{$injectionPayload}");

    // O sistema deve registrar a tentativa identificando o tenant ativo
    $hasTenantContext = collect($capturedLogs)->contains(function ($log) use ($tenantA) {
        $contextStr = json_encode($log['context'] ?? []);

        return str_contains($contextStr, 'tenant_context')
            || str_contains($contextStr, (string) $tenantA->id)
            || str_contains($log['message'] ?? '', 'tenant');
    });

    expect($hasTenantContext)
        ->toBeTrue(
            'AC-016: Nenhum log registrou tenant_context durante SQL injection. '.
            'Implemente logging no middleware de autenticação ou no handler de exceções.'
        );
});
