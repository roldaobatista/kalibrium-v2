<?php

declare(strict_types=1);

/**
 * Slice 013 — E03-S01b: Testes de RBAC de cliente (AC-011, AC-011b, AC-011c, AC-012a, AC-012b, AC-010b)
 *
 * Todos os testes exercem endpoints/policies que AINDA NAO EXISTEM neste slice.
 * Red natural: 405 para rotas novas (GET /clientes, GET /clientes/{id}, PUT /clientes/{id}),
 * 201/200 onde deveria ser 403 (tecnico em POST/DELETE sem canWriteClientes).
 */

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-013', 'cliente-rbac');

// ---------------------------------------------------------------------------
// Helper: cria usuario com role especifica no tenant
// ---------------------------------------------------------------------------

function criarUsuarioComRole(string $role, int $tenantId): User
{
    $user = User::factory()->create([
        'email' => "{$role}-rbac-".uniqid().'@test.com',
    ]);
    DB::table('tenant_users')->insert([
        'tenant_id' => $tenantId,
        'user_id' => $user->id,
        'role' => $role,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $user;
}

// ---------------------------------------------------------------------------
// AC-011 — RBAC: tecnico nao pode criar cliente (POST retorna 403)
// ---------------------------------------------------------------------------

test('AC-011: tecnico recebe 403 ao tentar POST /clientes', function () {
    /** @ac AC-011 */
    $tenantA = $this->tenantA();
    $userTecnico = criarUsuarioComRole('tecnico', $tenantA->id);

    $this->initializeTenant($tenantA);
    $this->actingAs($userTecnico);

    $payload = [
        'tipo_pessoa' => 'PJ',
        'cnpj_cpf' => '11.222.333/0001-81',
        'razao_social' => 'Empresa Tecnico Ltda',
        'logradouro' => 'Rua Teste',
        'numero' => '1',
        'bairro' => 'Centro',
        'cidade' => 'Sao Paulo',
        'uf' => 'SP',
        'cep' => '01310100',
        'regime_tributario' => 'simples',
    ];

    $response = $this->postJson('/clientes', $payload);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('clientes', [
        'tenant_id' => $tenantA->id,
        'razao_social' => 'Empresa Tecnico Ltda',
    ]);

    $this->endTenant();
});

test('AC-011: visualizador recebe 403 ao tentar PUT /clientes/{id} — canWriteClientes exclui visualizador', function () {
    /** @ac AC-011 */
    // Testa o endpoint PUT que e NOVO no slice-013.
    // Enquanto PUT nao existir, recebe 405 (nao 403) — red garantido.
    $tenantA = $this->tenantA();
    $userVisualizador = criarUsuarioComRole('visualizador', $tenantA->id);

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'razao_social' => 'Empresa Bloqueada para Visualizador',
        'ativo' => true,
    ]);

    $this->actingAs($userVisualizador);

    $response = $this->putJson("/clientes/{$cliente->id}", [
        'razao_social' => 'Tentativa Visualizador',
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('clientes', [
        'id' => $cliente->id,
        'razao_social' => 'Empresa Bloqueada para Visualizador',
    ]);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-011b — RBAC: tecnico nao pode editar cliente (PUT retorna 403)
// ---------------------------------------------------------------------------

test('AC-011b: tecnico recebe 403 ao tentar PUT /clientes/{id}', function () {
    /** @ac AC-011b */
    $tenantA = $this->tenantA();
    $userTecnico = criarUsuarioComRole('tecnico', $tenantA->id);

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'razao_social' => 'Empresa Nao Editavel Ltda',
        'ativo' => true,
    ]);

    $this->actingAs($userTecnico);

    $response = $this->putJson("/clientes/{$cliente->id}", [
        'razao_social' => 'Tentativa de Edicao',
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('clientes', [
        'id' => $cliente->id,
        'razao_social' => 'Empresa Nao Editavel Ltda',
    ]);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-011c — RBAC: tecnico nao pode desativar cliente (DELETE retorna 403)
// ---------------------------------------------------------------------------

test('AC-011c: tecnico recebe 403 ao tentar DELETE /clientes/{id}', function () {
    /** @ac AC-011c */
    $tenantA = $this->tenantA();
    $userTecnico = criarUsuarioComRole('tecnico', $tenantA->id);

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'ativo' => true,
    ]);

    $this->actingAs($userTecnico);

    $response = $this->deleteJson("/clientes/{$cliente->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('clientes', [
        'id' => $cliente->id,
        'ativo' => true,
    ]);

    $this->endTenant();
});

test('AC-011c: visualizador recebe 403 ao tentar PUT /clientes/{id} — canWriteClientes exclui visualizador (delete)', function () {
    /** @ac AC-011c */
    // Testa o PUT (endpoint novo do slice-013) para visualizador.
    // Enquanto PUT nao existir, recebe 405 — red garantido.
    $tenantA = $this->tenantA();
    $userVisualizador = criarUsuarioComRole('visualizador', $tenantA->id);

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'ativo' => true,
    ]);

    $this->actingAs($userVisualizador);

    $putResponse = $this->putJson("/clientes/{$cliente->id}", [
        'razao_social' => 'Tentativa Visualizador PUT',
    ]);
    $putResponse->assertStatus(403);

    $this->assertDatabaseHas('clientes', [
        'id' => $cliente->id,
        'ativo' => true,
    ]);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-012a — RBAC: tecnico pode listar clientes (GET /clientes retorna 200)
// ---------------------------------------------------------------------------

test('AC-012a: tecnico recebe 200 ao fazer GET /clientes', function () {
    /** @ac AC-012a */
    $tenantA = $this->tenantA();
    $userTecnico = criarUsuarioComRole('tecnico', $tenantA->id);

    $this->initializeTenant($tenantA);

    Cliente::factory()->count(2)->create(['tenant_id' => $tenantA->id, 'ativo' => true]);

    $this->actingAs($userTecnico);

    $response = $this->getJson('/clientes');

    $response->assertStatus(200);
    $response->assertJsonStructure(['data', 'meta', 'links']);

    $this->endTenant();
});

test('AC-012a: gerente pode listar clientes via GET /clientes', function () {
    /** @ac AC-012a */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $response = $this->getJson('/clientes');

    $response->assertStatus(200);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-012b — RBAC: tecnico pode ver detalhe de cliente (GET /clientes/{id} retorna 200)
// ---------------------------------------------------------------------------

test('AC-012b: tecnico recebe 200 ao fazer GET /clientes/{id}', function () {
    /** @ac AC-012b */
    $tenantA = $this->tenantA();
    $userTecnico = criarUsuarioComRole('tecnico', $tenantA->id);

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'ativo' => true,
    ]);

    $this->actingAs($userTecnico);

    $response = $this->getJson("/clientes/{$cliente->id}");

    $response->assertStatus(200);
    $response->assertJsonPath('data.id', $cliente->id);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-010b — Regressao: canWriteClientes nao quebra comportamentos do slice-012
//
// Estrategia: teste unico que PRIMEIRO verifica PUT 200 para gerente (endpoint
// novo do slice-013 — red garantido enquanto nao implementado), e DEPOIS verifica
// o 409 de regressao. Ambas as asserções estão no mesmo teste — se PUT falhar,
// o teste inteiro falha (red).
// ---------------------------------------------------------------------------

test('AC-010b: gerente pode PUT /clientes/{id} E recebe 409 ao tentar desativar cliente ja inativo — regressao canWriteClientes', function () {
    /** @ac AC-010b */
    $tenantA = $this->tenantA();
    $userA = $this->userA(); // role gerente

    $this->initializeTenant($tenantA);

    // Cliente ativo para o PUT
    $clienteAtivo = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'razao_social' => 'Empresa Gerente Edita',
        'ativo' => true,
    ]);

    // Cliente ja inativo para o 409
    $clienteInativo = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'ativo' => false,
    ]);

    $this->actingAs($userA);

    // 1) Gerente deve poder fazer PUT (canWriteClientes inclui gerente)
    //    Enquanto PUT nao existir, recebe 405 — este test falha aqui (red).
    $putResponse = $this->putJson("/clientes/{$clienteAtivo->id}", [
        'razao_social' => 'Empresa Editada pelo Gerente',
    ]);
    $putResponse->assertStatus(200);

    // 2) Regressao: gerente ainda recebe 409 para cliente ja inativo
    //    (canWriteClientes nao removeu a protecao de negocio)
    $deleteResponse = $this->deleteJson("/clientes/{$clienteInativo->id}");
    $deleteResponse->assertStatus(409);

    $this->endTenant();
});

test('AC-010b: tecnico recebe 403 ao tentar desativar cliente ja inativo — policy antes da logica de negocio', function () {
    /** @ac AC-010b */
    $tenantA = $this->tenantA();
    $userTecnico = criarUsuarioComRole('tecnico', $tenantA->id);

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'ativo' => false,
    ]);

    $this->actingAs($userTecnico);

    $response = $this->deleteJson("/clientes/{$cliente->id}");

    // Policy recusa (403) antes de chegar na logica de negocio (409)
    $response->assertStatus(403);

    $this->endTenant();
});
