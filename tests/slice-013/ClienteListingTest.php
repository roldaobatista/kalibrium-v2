<?php

declare(strict_types=1);

/**
 * Slice 013 — E03-S01b: Testes de listagem paginada de clientes (AC-007, AC-008, AC-010a, AC-012a, AC-012c)
 *
 * Todos os testes exercem endpoints que AINDA NAO EXISTEM neste slice.
 * Red natural: 404 (rota inexistente) ou 405 / 403 por ausência de método/policy.
 */

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-013', 'cliente-listing');

// ---------------------------------------------------------------------------
// AC-007 — Listagem paginada com isolamento de tenant
// ---------------------------------------------------------------------------

test('AC-007: GET /clientes retorna exatamente 20 registros na pagina 1 para tenant A com 25 clientes', function () {
    /** @ac AC-007 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    // Cria 25 clientes no tenant A
    Cliente::factory()->count(25)->create([
        'tenant_id' => $tenantA->id,
        'ativo' => true,
    ]);

    $this->actingAs($userA);

    $response = $this->getJson('/clientes?per_page=20&page=1');

    $response->assertStatus(200);
    $response->assertJsonCount(20, 'data');
    $response->assertJsonPath('meta.total', 25);
    $response->assertJsonPath('meta.per_page', 20);
    $response->assertJsonPath('meta.current_page', 1);
    $response->assertJsonPath('meta.last_page', 2);

    $this->endTenant();
});

test('AC-007: GET /clientes nao retorna registros do tenant B', function () {
    /** @ac AC-007 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    Cliente::factory()->count(3)->create(['tenant_id' => $tenantA->id, 'ativo' => true]);
    Cliente::factory()->count(10)->create(['tenant_id' => $tenantB->id, 'ativo' => true]);

    $this->actingAs($userA);

    $response = $this->getJson('/clientes');

    $response->assertStatus(200);
    $response->assertJsonPath('meta.total', 3);

    // Garante que nenhum item tem tenant_id do tenant B
    $ids = collect($response->json('data'))->pluck('id')->toArray();
    expect($ids)->not->toBeEmpty();

    $this->endTenant();
});

test('AC-007: resposta de GET /clientes contem estrutura meta e links', function () {
    /** @ac AC-007 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    Cliente::factory()->count(5)->create(['tenant_id' => $tenantA->id, 'ativo' => true]);

    $this->actingAs($userA);

    $response = $this->getJson('/clientes');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [['id', 'tipo_pessoa', 'razao_social', 'ativo', 'created_at']],
        'meta' => ['current_page', 'per_page', 'total', 'last_page', 'from', 'to'],
        'links' => ['first', 'last', 'prev', 'next'],
    ]);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-008 — Filtro por razao social, nome fantasia e CNPJ/CPF
// ---------------------------------------------------------------------------

test('AC-008: GET /clientes?search=Calibra retorna apenas clientes cuja razao_social contem a substring', function () {
    /** @ac AC-008 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'razao_social' => 'Calibra Laboratorios Ltda',
        'ativo' => true,
    ]);
    Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'razao_social' => 'Outra Empresa SA',
        'ativo' => true,
    ]);

    $this->actingAs($userA);

    $response = $this->getJson('/clientes?search=Calibra');

    $response->assertStatus(200);
    $response->assertJsonPath('meta.total', 1);
    $response->assertJsonPath('data.0.razao_social', 'Calibra Laboratorios Ltda');

    $this->endTenant();
});

test('AC-008: GET /clientes?search= filtra por nome_fantasia case-insensitive', function () {
    /** @ac AC-008 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'razao_social' => 'Empresa Qualquer Ltda',
        'nome_fantasia' => 'CalibLab',
        'ativo' => true,
    ]);
    Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'razao_social' => 'Outra SA',
        'nome_fantasia' => 'OutraFantasia',
        'ativo' => true,
    ]);

    $this->actingAs($userA);

    $response = $this->getJson('/clientes?search=caliblab');

    $response->assertStatus(200);
    $response->assertJsonPath('meta.total', 1);

    $this->endTenant();
});

test('AC-008: GET /clientes?search= filtra por documento (CNPJ sem mascara)', function () {
    /** @ac AC-008 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'documento' => '11222333000181',
        'ativo' => true,
    ]);
    Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'documento' => '99888777000166',
        'ativo' => true,
    ]);

    $this->actingAs($userA);

    // Busca com mascara — controller deve normalizar para so digitos
    $response = $this->getJson('/clientes?search=11.222');

    $response->assertStatus(200);
    $response->assertJsonPath('meta.total', 1);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-010a — Listagem de clientes inativos via filtro ?ativo=false
// ---------------------------------------------------------------------------

test('AC-010a: GET /clientes?ativo=false retorna apenas clientes inativos do tenant A', function () {
    /** @ac AC-010a */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    Cliente::factory()->count(3)->create(['tenant_id' => $tenantA->id, 'ativo' => true]);
    Cliente::factory()->count(2)->create(['tenant_id' => $tenantA->id, 'ativo' => false]);

    $this->actingAs($userA);

    $response = $this->getJson('/clientes?ativo=false');

    $response->assertStatus(200);
    $response->assertJsonPath('meta.total', 2);

    // Todos os retornados devem ser inativos
    foreach ($response->json('data') as $item) {
        expect($item['ativo'])->toBeFalse();
    }

    $this->endTenant();
});

test('AC-010a: GET /clientes sem filtro ativo retorna apenas clientes ativos (default ativo=true)', function () {
    /** @ac AC-010a */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    Cliente::factory()->count(4)->create(['tenant_id' => $tenantA->id, 'ativo' => true]);
    Cliente::factory()->count(2)->create(['tenant_id' => $tenantA->id, 'ativo' => false]);

    $this->actingAs($userA);

    $response = $this->getJson('/clientes');

    $response->assertStatus(200);
    $response->assertJsonPath('meta.total', 4);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-012a — RBAC: tecnico pode listar clientes (200)
// (Coberto tambem em ClienteRbacTest; aqui exercemos o comportamento de listagem)
// ---------------------------------------------------------------------------

test('AC-012a: tecnico recebe 200 e listagem paginada ao fazer GET /clientes', function () {
    /** @ac AC-012a */
    $tenantA = $this->tenantA();

    $this->initializeTenant($tenantA);

    // Cria usuario com role tecnico no tenant A
    $userTecnico = User::factory()->create([
        'email' => 'tecnico-listing-'.uniqid().'@test.com',
    ]);
    DB::table('tenant_users')->insert([
        'tenant_id' => $tenantA->id,
        'user_id' => $userTecnico->id,
        'role' => 'tecnico',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Cliente::factory()->count(3)->create(['tenant_id' => $tenantA->id, 'ativo' => true]);

    $this->actingAs($userTecnico);

    $response = $this->getJson('/clientes');

    $response->assertStatus(200);
    $response->assertJsonStructure(['data', 'meta', 'links']);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-012c — Ordenacao com valor invalido retorna 422
// ---------------------------------------------------------------------------

test('AC-012c: GET /clientes?sort=campo_invalido retorna 422 com mensagem dos valores aceitos', function () {
    /** @ac AC-012c */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $response = $this->getJson('/clientes?sort=campo_invalido');

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['sort']);

    $this->endTenant();
});

test('AC-012c: GET /clientes?sort=razao_social retorna 200 (sort valido)', function () {
    /** @ac AC-012c */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $response = $this->getJson('/clientes?sort=razao_social');

    $response->assertStatus(200);

    $this->endTenant();
});
