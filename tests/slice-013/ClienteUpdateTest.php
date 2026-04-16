<?php

declare(strict_types=1);

/**
 * Slice 013 — E03-S01b: Testes de edicao de cliente (AC-009, AC-009b)
 *
 * Todos os testes exercem endpoints que AINDA NAO EXISTEM neste slice.
 * Red natural: 404 (rota inexistente) ou ausência de metodo update().
 */

use App\Models\Cliente;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-013', 'cliente-update');

// ---------------------------------------------------------------------------
// AC-009 — Edicao de cliente (todos os campos editaveis)
// ---------------------------------------------------------------------------

test('AC-009: PUT /clientes/{id} persiste novos valores dos campos editaveis', function () {
    /** @ac AC-009 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'razao_social' => 'Empresa Original Ltda',
        'nome_fantasia' => 'Original',
        'logradouro' => 'Rua Velha',
        'numero' => '10',
        'bairro' => 'Bairro Velho',
        'cidade' => 'Curitiba',
        'uf' => 'PR',
        'cep' => '80010000',
        'regime_tributario' => 'simples',
        'limite_credito' => 1000.00,
        'ativo' => true,
    ]);

    $this->actingAs($userA);

    $payload = [
        'razao_social' => 'Empresa Atualizada Ltda',
        'nome_fantasia' => 'Atualizada',
        'logradouro' => 'Rua Nova',
        'numero' => '999',
        'complemento' => 'Sala 42',
        'bairro' => 'Bairro Novo',
        'cidade' => 'Sao Paulo',
        'uf' => 'SP',
        'cep' => '01310100',
        'regime_tributario' => 'presumido',
        'limite_credito' => 9999.99,
    ];

    $response = $this->putJson("/clientes/{$cliente->id}", $payload);

    $response->assertStatus(200);
    $response->assertJsonPath('data.razao_social', 'Empresa Atualizada Ltda');
    $response->assertJsonPath('data.nome_fantasia', 'Atualizada');
    $response->assertJsonPath('data.cidade', 'Sao Paulo');
    $response->assertJsonPath('data.uf', 'SP');

    // Verifica persistencia no banco
    $this->assertDatabaseHas('clientes', [
        'id' => $cliente->id,
        'razao_social' => 'Empresa Atualizada Ltda',
        'cidade' => 'Sao Paulo',
    ]);

    $this->endTenant();
});

test('AC-009: PUT /clientes/{id} nao altera campos imutaveis cnpj_cpf e tipo_pessoa', function () {
    /** @ac AC-009 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'tipo_pessoa' => 'PJ',
        'documento' => '11222333000181',
        'razao_social' => 'Empresa Imutavel Ltda',
        'ativo' => true,
    ]);

    $this->actingAs($userA);

    // Tenta alterar campos imutaveis — devem ser ignorados
    $payload = [
        'razao_social' => 'Nome Novo Ltda',
        'cnpj_cpf' => '99.888.777/0001-66', // deve ser ignorado
        'tipo_pessoa' => 'PF',                   // deve ser ignorado
    ];

    $response = $this->putJson("/clientes/{$cliente->id}", $payload);

    $response->assertStatus(200);

    // Banco deve manter documento e tipo_pessoa originais
    $this->assertDatabaseHas('clientes', [
        'id' => $cliente->id,
        'tipo_pessoa' => 'PJ',
        'documento' => '11222333000181',
    ]);

    $this->endTenant();
});

test('AC-009: PUT /clientes/{id} com subset valido de campos persiste apenas os campos enviados', function () {
    /** @ac AC-009 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'razao_social' => 'Empresa Subset Ltda',
        'limite_credito' => 500.00,
        'ativo' => true,
    ]);

    $this->actingAs($userA);

    // Envia apenas limite_credito
    $response = $this->putJson("/clientes/{$cliente->id}", [
        'limite_credito' => 1500.00,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('clientes', [
        'id' => $cliente->id,
        'razao_social' => 'Empresa Subset Ltda', // nao alterado
        'limite_credito' => 1500.00,
    ]);

    $this->endTenant();
});

test('AC-009: PUT /clientes/{id} retorna o schema completo da ClienteResource na resposta', function () {
    /** @ac AC-009 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'ativo' => true,
    ]);

    $this->actingAs($userA);

    $response = $this->putJson("/clientes/{$cliente->id}", [
        'razao_social' => 'Empresa Schema Check Ltda',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'id', 'tipo_pessoa', 'cnpj_cpf', 'razao_social',
            'regime_tributario', 'limite_credito', 'ativo',
            'created_at', 'updated_at',
        ],
    ]);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-009b — Edicao com payload vazio retorna 422
// ---------------------------------------------------------------------------

test('AC-009b: PUT /clientes/{id} com payload vazio {} retorna 422', function () {
    /** @ac AC-009b */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'ativo' => true,
    ]);

    $this->actingAs($userA);

    $response = $this->putJson("/clientes/{$cliente->id}", []);

    $response->assertStatus(422);

    $this->endTenant();
});

test('AC-009b: PUT /clientes/{id} com payload vazio nao altera o registro no banco', function () {
    /** @ac AC-009b */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'razao_social' => 'Empresa Inalterada Ltda',
        'ativo' => true,
    ]);

    $this->actingAs($userA);

    $response = $this->putJson("/clientes/{$cliente->id}", []);

    // PUT com payload vazio deve retornar 422 E nao alterar o registro
    $response->assertStatus(422);

    $this->assertDatabaseHas('clientes', [
        'id' => $cliente->id,
        'razao_social' => 'Empresa Inalterada Ltda',
    ]);

    $this->endTenant();
});

test('AC-009b: PUT /clientes/{id} com apenas campos imutaveis retorna 422', function () {
    /** @ac AC-009b */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'ativo' => true,
    ]);

    $this->actingAs($userA);

    // cnpj_cpf e tipo_pessoa nao sao campos editaveis — payload efetivamente vazio
    $response = $this->putJson("/clientes/{$cliente->id}", [
        'cnpj_cpf' => '11.222.333/0001-81',
        'tipo_pessoa' => 'PJ',
    ]);

    $response->assertStatus(422);

    $this->endTenant();
});
