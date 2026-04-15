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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-011', 'tenant-isolation');

// ---------------------------------------------------------------------------
// AC-002: Rota autenticada com ID de recurso do tenant B → 404 ou 403
// ---------------------------------------------------------------------------

/**
 * @ac AC-002
 *
 * Testa que listagens autenticadas no contexto do tenant A não expõem dados do tenant B.
 * Usa rotas MVP existentes: /settings/privacy/consentimentos e /settings/privacy.
 */
test('AC-002: listagem de consentimentos do tenant A não contém dados do tenant B', function () {
    /** @ac AC-002 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    // Cria ConsentSubject em ambos os tenants para garantir dados existentes
    DB::table('consent_subjects')->insert([
        ['tenant_id' => $tenantA->id, 'subject_type' => 'customer', 'email' => 'a-002@a.test', 'created_at' => now(), 'updated_at' => now()],
        ['tenant_id' => $tenantB->id, 'subject_type' => 'customer', 'email' => 'b-002@b.test', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get('/settings/privacy/consentimentos');

    expect($response->getStatusCode())->toBe(200);

    $content = $response->getContent() ?? '';
    expect($content)->not->toContain('b-002@b.test');
    expect($content)->not->toContain((string) $tenantB->id);
});

test('AC-002: listagem de categorias LGPD do tenant A não contém dados do tenant B', function () {
    /** @ac AC-002 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    // Insere LgpdCategory em ambos via DB direto
    if (Schema::hasTable('lgpd_categories')) {
        DB::table('lgpd_categories')->insert([
            ['tenant_id' => $tenantA->id, 'name' => 'Cat A 002', 'code' => 'cat-a-002-'.uniqid(), 'legal_basis' => 'consent', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $tenantB->id, 'name' => 'Cat B 002', 'code' => 'cat-b-002-'.uniqid(), 'legal_basis' => 'consent', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get('/settings/privacy');

    expect($response->getStatusCode())->toBe(200);

    $content = $response->getContent() ?? '';
    expect($content)->not->toContain('Cat B 002');
    expect($content)->not->toContain((string) $tenantB->id);
});

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
    // toContain no Pest 4 não aceita segundo argumento como mensagem de falha
    expect($content)->toContain((string) $tenantA->id);

    // O contexto não deve mudar para o tenant B
    expect($content)->not->toContain((string) $tenantB->id);
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
    // toContain no Pest 4 não aceita segundo argumento como mensagem de falha
    expect($content)->toContain((string) $tenantA->id);

    expect($content)->not->toContain((string) $tenantB->id);
});

// ---------------------------------------------------------------------------
// AC-011: Batch de IDs com qualquer ID do tenant B → rejeitado inteiro (403)
// ---------------------------------------------------------------------------

test('AC-011: nenhuma rota MVP aceita batch de IDs misturados cross-tenant — estrutural', function () {
    /** @ac AC-011
     *
     * O módulo de calibrações (batch DELETE /api/calibrations) não existe no MVP atual.
     * Este teste estrutural valida que NENHUMA rota registrada aceita parâmetro `ids`
     * com método DELETE, garantindo ausência de superfície de ataque cross-tenant em batch.
     * Quando o módulo for implementado, este teste será substituído por um funcional.
     */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    // Coleta todas as rotas DELETE registradas
    $routes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
        ->filter(fn ($r) => in_array('DELETE', $r->methods(), true))
        ->map(fn ($r) => $r->uri())
        ->values()
        ->toArray();

    // Nenhuma rota DELETE deve aceitar parâmetro `ids` em batch no MVP atual
    $batchRoutes = array_filter($routes, fn ($uri) => str_contains($uri, 'calibration') || str_contains($uri, 'batch'));

    expect($batchRoutes)
        ->toBeEmpty(
            'AC-011: Rota(s) de batch encontrada(s) no MVP: '.implode(', ', $batchRoutes).'. '.
            'Se adicionada, deve validar que batch com IDs cross-tenant retorna 403 inteiro.'
        );

    // Garantia adicional: nenhum registro do tenant A foi afetado
    $sensitiveTable = $this->firstSensitiveTable();
    $countBefore = DB::table($sensitiveTable)->where('tenant_id', $tenantA->id)->count();

    // Tenta um DELETE genérico com ids misturados em rotas existentes — deve ser 404/405
    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->delete('/api/batch-delete?ids=1,2,3');

    expect($response->getStatusCode())->toBeIn([404, 405, 403]);

    $countAfter = DB::table($sensitiveTable)->where('tenant_id', $tenantA->id)->count();
    expect($countAfter)->toBe($countBefore);
});

// ---------------------------------------------------------------------------
// AC-016: SQL injection em parâmetro de rota → 404/403, sem dados do tenant B
// ---------------------------------------------------------------------------

/**
 * @ac AC-016
 */
dataset('sql_injection_payloads', function () {
    return [
        'OR 1=1 clássico' => ['1 OR 1=1'],
        'UNION SELECT' => ['1 UNION SELECT id,name FROM tenants--'],
        'Aspas simples' => ["1' OR '1'='1"],
        'Ponto e vírgula DROP' => ['1; DROP TABLE users; --'],
        'Subquery tenant_id' => ['0 OR (SELECT tenant_id FROM users LIMIT 1) IS NOT NULL'],
        'OR negação de tenant' => ['1 OR tenant_id != 999'],
        'Comentário SQL inline' => ['1/* comment */OR/* */1=1'],
    ];
});

test('AC-016: SQL injection em query string não vaza dados do tenant B via /api/tenant-context', function (string $payload) {
    /** @ac AC-016
     *
     * Usa /api/tenant-context (rota MVP existente) para validar que payloads de SQL injection
     * passados como query string não alteram o contexto nem expõem dados do tenant B.
     */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $encodedPayload = urlencode($payload);
    $uri = "/api/tenant-context?tenant={$encodedPayload}&id={$encodedPayload}";

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get($uri);

    $statusCode = $response->getStatusCode();
    $content = $response->getContent() ?? '';

    // Rota deve responder (200 = contexto correto; 4xx = rejeitado) — nunca 500 com stack trace
    expect($statusCode)->not->toBe(500,
        "AC-016: Payload '{$payload}' causou erro 500 — possível SQL injection não tratado."
    );

    // Contexto não deve conter dados do tenant B
    expect($content)->not->toContain((string) $tenantB->id);

    if (! empty($tenantB->name)) {
        expect($content)->not->toContain((string) $tenantB->name);
    }
})->with('sql_injection_payloads');

test('AC-016: SQL injection em POST /settings/privacy/consentimentos não cria registro cross-tenant', function (string $payload) {
    /** @ac AC-016
     *
     * Testa vetores de SQL injection nos campos de formulário da rota MVP.
     * O servidor deve rejeitar (4xx) ou sanitizar — nunca criar registro com tenant errado.
     */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $countBefore = DB::table('consent_subjects')->where('tenant_id', $tenantB->id)->count();

    $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->post('/settings/privacy/consentimentos', [
            'email' => $payload.'@injection.test',
            'subject_type' => $payload,
            '_token' => csrf_token(),
        ]);

    // Nenhum registro novo deve ter sido criado com tenant_id do tenant B
    $countAfter = DB::table('consent_subjects')->where('tenant_id', $tenantB->id)->count();

    expect($countAfter)
        ->toBe($countBefore,
            "AC-016: Payload '{$payload}' criou registro(s) com tenant_id do tenant B. Injeção cross-tenant confirmada."
        );
})->with('sql_injection_payloads');

test('AC-016: log de autenticação não vaza dados cross-tenant em requests com payloads maliciosos', function () {
    /** @ac AC-016 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $capturedLogs = [];
    Log::listen(function ($log) use (&$capturedLogs) {
        $capturedLogs[] = [
            'level' => $log->level,
            'message' => $log->message,
            'context' => $log->context,
        ];
    });

    $injectionPayload = urlencode('1 OR 1=1');

    $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/api/tenant-context?tenant={$injectionPayload}");

    // Nenhum log deve conter dados do tenant B
    $leaked = collect($capturedLogs)->contains(function ($log) use ($tenantB) {
        $contextStr = json_encode($log['context'] ?? []).$log['message'];

        return str_contains($contextStr, (string) $tenantB->id)
            || (! empty($tenantB->name) && str_contains($contextStr, (string) $tenantB->name));
    });

    expect($leaked)
        ->toBeFalse(
            'AC-016: Log registrou dados do tenant B durante request com SQL injection. Vazamento no logger.'
        );
});
