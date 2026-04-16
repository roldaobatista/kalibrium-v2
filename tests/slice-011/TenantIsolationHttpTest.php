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

test('AC-011: DELETE /_test/batch com IDs misturados cross-tenant retorna 403 e não deleta nenhum registro', function () {
    /** @ac AC-011
     *
     * Teste funcional via rota /_test/batch (disponível em local/testing, protegida por RestrictToLocalEnv).
     * Cenário: 2 consent_subjects do tenant A + 1 do tenant B.
     * Autenticado como userA, envia DELETE com os 3 IDs misturados.
     * Esperado: 403, nenhum registro deletado (inclusive os do tenant A).
     */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    // Cria 2 registros no tenant A
    $idA1 = DB::table('consent_subjects')->insertGetId([
        'tenant_id' => $tenantA->id,
        'subject_type' => 'customer',
        'email' => 'ac011-a1-'.uniqid().'@test.test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $idA2 = DB::table('consent_subjects')->insertGetId([
        'tenant_id' => $tenantA->id,
        'subject_type' => 'customer',
        'email' => 'ac011-a2-'.uniqid().'@test.test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Cria 1 registro no tenant B
    $idB1 = DB::table('consent_subjects')->insertGetId([
        'tenant_id' => $tenantB->id,
        'subject_type' => 'customer',
        'email' => 'ac011-b1-'.uniqid().'@test.test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // DELETE com IDs misturados (A1, A2, B1)
    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->delete("/_test/batch?ids={$idA1},{$idA2},{$idB1}");

    // Deve rejeitar o batch inteiro com 403
    expect($response->getStatusCode())
        ->toBe(403,
            'AC-011: DELETE /_test/batch com IDs cross-tenant deveria retornar 403, '.
            'mas retornou '.$response->getStatusCode().'.'
        );

    // Nenhum registro foi deletado — nem os do tenant A
    expect(DB::table('consent_subjects')->whereIn('id', [$idA1, $idA2])->count())
        ->toBe(2, 'AC-011: Registros do tenant A foram deletados mesmo com 403. Batch não é atômico.');

    expect(DB::table('consent_subjects')->where('id', $idB1)->count())
        ->toBe(1, 'AC-011: Registro do tenant B foi deletado pelo batch cross-tenant.');
});

// ---------------------------------------------------------------------------
// AC-016: SQL injection em parâmetro de rota → 404/403, sem dados do tenant B
// ---------------------------------------------------------------------------

/**
 * @ac AC-016
 */
dataset('sql_injection_payloads', fn () => require __DIR__.'/Datasets/SqlInjectionPayloads.php');

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

    $logSpy = Log::spy();

    $injectionPayload = urlencode('1 OR 1=1');

    $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/api/tenant-context?tenant={$injectionPayload}");

    // Assert POSITIVO: log emitido com tenant_context=A
    $logSpy->shouldHaveReceived('info')
        ->withArgs(fn ($message, $context = []) => isset($context['tenant_context'])
            && (string) $context['tenant_context'] === (string) $tenantA->id)
        ->atLeast()->once();

    // Assert NEGATIVO: nenhum log emitido com dados do tenant B
    $logSpy->shouldNotHaveReceived('info', function ($message, $context = []) use ($tenantB) {
        $str = json_encode($context).$message;

        return str_contains($str, (string) $tenantB->id)
            || (! empty($tenantB->name) && str_contains($str, (string) $tenantB->name));
    });
});
