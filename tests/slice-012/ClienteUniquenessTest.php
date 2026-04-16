<?php

declare(strict_types=1);

/**
 * Slice 012 — E03-S01a: Testes de unicidade de documento (AC-005, AC-006)
 *
 * Todos os testes chamam rotas/classes que AINDA NAO EXISTEM.
 * Red natural: 404 (rota inexistente) ou tabela inexistente.
 */

use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-012', 'cliente-uniqueness');

// ---------------------------------------------------------------------------
// AC-005: CNPJ duplicado no mesmo tenant retorna erro de validacao
// ---------------------------------------------------------------------------

test('AC-005: POST /clientes com CNPJ duplicado no mesmo tenant retorna erro de validacao', function () {
    /** @ac AC-005 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $payload = [
        'tipo_pessoa' => 'PJ',
        'cnpj_cpf' => '11.222.333/0001-81',
        'razao_social' => 'Empresa Original Ltda',
        'logradouro' => 'Rua das Industrias',
        'numero' => '100',
        'bairro' => 'Centro',
        'cidade' => 'Sao Paulo',
        'uf' => 'SP',
        'cep' => '01310100',
        'regime_tributario' => 'presumido',
    ];

    // Primeiro cadastro — deve passar
    $first = $this->postJson('/clientes', $payload);
    $first->assertStatus(201);

    // Segundo cadastro com mesmo CNPJ no mesmo tenant — deve falhar
    $payload['razao_social'] = 'Empresa Duplicata Ltda';
    $second = $this->postJson('/clientes', $payload);

    $second->assertStatus(422);
    $second->assertJsonValidationErrors(['cnpj_cpf']);
});

test('AC-005: POST /clientes com CPF duplicado no mesmo tenant retorna erro de validacao', function () {
    /** @ac AC-005 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $payload = [
        'tipo_pessoa' => 'PF',
        'cnpj_cpf' => '529.982.247-25',
        'razao_social' => 'Joao Original',
        'logradouro' => 'Rua Principal',
        'numero' => '50',
        'bairro' => 'Centro',
        'cidade' => 'Rio de Janeiro',
        'uf' => 'RJ',
        'cep' => '20040020',
        'regime_tributario' => 'isento',
    ];

    $first = $this->postJson('/clientes', $payload);
    $first->assertStatus(201);

    $payload['razao_social'] = 'Joao Duplicata';
    $second = $this->postJson('/clientes', $payload);

    $second->assertStatus(422);
    $second->assertJsonValidationErrors(['cnpj_cpf']);
});

// ---------------------------------------------------------------------------
// AC-006: Mesmo CNPJ em tenant diferente e aceito (isolamento)
// ---------------------------------------------------------------------------

test('AC-006: POST /clientes com mesmo CNPJ em tenant diferente e aceito', function () {
    /** @ac AC-006 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();
    $userA = $this->userA();
    $userB = $this->userB();

    $cnpj = '11.222.333/0001-81';

    // Cadastra no tenant A
    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $payloadA = [
        'tipo_pessoa' => 'PJ',
        'cnpj_cpf' => $cnpj,
        'razao_social' => 'Empresa Tenant A',
        'logradouro' => 'Rua A',
        'numero' => '1',
        'bairro' => 'Centro',
        'cidade' => 'Sao Paulo',
        'uf' => 'SP',
        'cep' => '01310100',
        'regime_tributario' => 'simples',
    ];

    $responseA = $this->postJson('/clientes', $payloadA);
    $responseA->assertStatus(201);

    $this->endTenant();

    // Cadastra no tenant B com mesmo CNPJ — deve ser aceito
    $this->initializeTenant($tenantB);
    $this->actingAs($userB);

    $payloadB = [
        'tipo_pessoa' => 'PJ',
        'cnpj_cpf' => $cnpj,
        'razao_social' => 'Empresa Tenant B',
        'logradouro' => 'Rua B',
        'numero' => '2',
        'bairro' => 'Centro',
        'cidade' => 'Rio de Janeiro',
        'uf' => 'RJ',
        'cep' => '20040020',
        'regime_tributario' => 'real',
    ];

    $responseB = $this->postJson('/clientes', $payloadB);
    $responseB->assertStatus(201);

    // Ambos existem, cada um no seu tenant
    $this->assertDatabaseHas('clientes', [
        'tenant_id' => $tenantA->id,
        'documento' => '11222333000181',
    ]);
    $this->assertDatabaseHas('clientes', [
        'tenant_id' => $tenantB->id,
        'documento' => '11222333000181',
    ]);

    $this->endTenant();
});
