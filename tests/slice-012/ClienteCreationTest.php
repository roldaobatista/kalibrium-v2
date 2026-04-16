<?php

declare(strict_types=1);

/**
 * Slice 012 — E03-S01a: Testes de criacao de cliente (AC-001, AC-002, AC-003, AC-004)
 *
 * Todos os testes chamam rotas/classes que AINDA NAO EXISTEM.
 * Red natural: 404 (rota inexistente) ou classe inexistente.
 */

use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-012', 'cliente-creation');

// ---------------------------------------------------------------------------
// AC-001: POST /clientes com CNPJ valido cria cliente PJ
// ---------------------------------------------------------------------------

test('AC-001: POST /clientes com CNPJ valido cria cliente PJ com tenant_id correto', function () {
    /** @ac AC-001 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $payload = [
        'tipo_pessoa' => 'PJ',
        'cnpj_cpf' => '11.222.333/0001-81',
        'razao_social' => 'Empresa Teste Ltda',
        'nome_fantasia' => 'EmpTeste',
        'logradouro' => 'Rua das Industrias',
        'numero' => '100',
        'complemento' => 'Sala 1',
        'bairro' => 'Centro',
        'cidade' => 'Sao Paulo',
        'uf' => 'SP',
        'cep' => '01310100',
        'regime_tributario' => 'presumido',
    ];

    $response = $this->postJson('/clientes', $payload);

    $response->assertStatus(201);
    $response->assertJsonPath('data.tipo_pessoa', 'PJ');

    $this->assertDatabaseHas('clientes', [
        'tenant_id' => $tenantA->id,
        'tipo_pessoa' => 'PJ',
        'documento' => '11222333000181',
    ]);
});

test('AC-001: resposta do POST /clientes contem dados do cliente criado', function () {
    /** @ac AC-001 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $payload = [
        'tipo_pessoa' => 'PJ',
        'cnpj_cpf' => '11.222.333/0001-81',
        'razao_social' => 'Calibra Laboratorios Ltda',
        'nome_fantasia' => 'CalibLab',
        'logradouro' => 'Rua das Industrias',
        'numero' => '200',
        'bairro' => 'Distrito Industrial',
        'cidade' => 'Sao Paulo',
        'uf' => 'SP',
        'cep' => '01310100',
        'regime_tributario' => 'simples',
    ];

    $response = $this->postJson('/clientes', $payload);

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'data' => ['id', 'tipo_pessoa', 'razao_social', 'ativo', 'created_at'],
    ]);
    $response->assertJsonPath('data.ativo', true);
});

// ---------------------------------------------------------------------------
// AC-002: POST /clientes com CPF valido cria cliente PF
// ---------------------------------------------------------------------------

test('AC-002: POST /clientes com CPF valido cria cliente PF acessivel via GET', function () {
    /** @ac AC-002 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $payload = [
        'tipo_pessoa' => 'PF',
        'cnpj_cpf' => '529.982.247-25',
        'razao_social' => 'Joao da Silva',
        'logradouro' => 'Rua Principal',
        'numero' => '50',
        'bairro' => 'Centro',
        'cidade' => 'Rio de Janeiro',
        'uf' => 'RJ',
        'cep' => '20040020',
        'regime_tributario' => 'isento',
    ];

    $response = $this->postJson('/clientes', $payload);

    $response->assertStatus(201);
    $response->assertJsonPath('data.tipo_pessoa', 'PF');

    $this->assertDatabaseHas('clientes', [
        'tenant_id' => $tenantA->id,
        'tipo_pessoa' => 'PF',
        'documento' => '52998224725',
    ]);
});

// ---------------------------------------------------------------------------
// AC-003: POST /clientes com CNPJ invalido retorna erro de validacao
// ---------------------------------------------------------------------------

test('AC-003: POST /clientes com CNPJ invalido retorna erro de validacao e nao persiste', function () {
    /** @ac AC-003 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $payload = [
        'tipo_pessoa' => 'PJ',
        'cnpj_cpf' => '11.111.111/1111-11',
        'razao_social' => 'Empresa Invalida',
        'logradouro' => 'Rua Teste',
        'numero' => '1',
        'bairro' => 'Centro',
        'cidade' => 'Sao Paulo',
        'uf' => 'SP',
        'cep' => '01310100',
        'regime_tributario' => 'simples',
    ];

    $response = $this->postJson('/clientes', $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cnpj_cpf']);

    $this->assertDatabaseMissing('clientes', [
        'documento' => '11111111111111',
    ]);
});

test('AC-003: POST /clientes com CNPJ formato correto mas digitos invalidos retorna 422', function () {
    /** @ac AC-003 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $payload = [
        'tipo_pessoa' => 'PJ',
        'cnpj_cpf' => '12.345.678/0001-99',
        'razao_social' => 'Empresa Digito Errado',
        'logradouro' => 'Rua Teste',
        'numero' => '1',
        'bairro' => 'Centro',
        'cidade' => 'Sao Paulo',
        'uf' => 'SP',
        'cep' => '01310100',
        'regime_tributario' => 'simples',
    ];

    $response = $this->postJson('/clientes', $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cnpj_cpf']);
});

// ---------------------------------------------------------------------------
// AC-004: POST /clientes com CPF invalido retorna erro de validacao
// ---------------------------------------------------------------------------

test('AC-004: POST /clientes com CPF invalido (sequencia repetida) retorna erro de validacao', function () {
    /** @ac AC-004 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $payload = [
        'tipo_pessoa' => 'PF',
        'cnpj_cpf' => '111.111.111-11',
        'razao_social' => 'Pessoa Invalida',
        'logradouro' => 'Rua Teste',
        'numero' => '1',
        'bairro' => 'Centro',
        'cidade' => 'Sao Paulo',
        'uf' => 'SP',
        'cep' => '01310100',
        'regime_tributario' => 'isento',
    ];

    $response = $this->postJson('/clientes', $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cnpj_cpf']);

    $this->assertDatabaseMissing('clientes', [
        'documento' => '11111111111',
    ]);
});

test('AC-004: POST /clientes com CPF digitos invalidos retorna 422', function () {
    /** @ac AC-004 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $payload = [
        'tipo_pessoa' => 'PF',
        'cnpj_cpf' => '123.456.789-00',
        'razao_social' => 'Pessoa Digito Errado',
        'logradouro' => 'Rua Teste',
        'numero' => '1',
        'bairro' => 'Centro',
        'cidade' => 'Sao Paulo',
        'uf' => 'SP',
        'cep' => '01310100',
        'regime_tributario' => 'isento',
    ];

    $response = $this->postJson('/clientes', $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cnpj_cpf']);
});
