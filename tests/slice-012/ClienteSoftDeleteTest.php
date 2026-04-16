<?php

declare(strict_types=1);

/**
 * Slice 012 — E03-S01a: Testes de soft-delete de cliente (AC-007, AC-008)
 *
 * Todos os testes chamam rotas/classes que AINDA NAO EXISTEM.
 * Red natural: 404 (rota inexistente) ou tabela inexistente.
 */

use Illuminate\Support\Facades\DB;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-012', 'cliente-soft-delete');

// ---------------------------------------------------------------------------
// AC-007: DELETE /clientes/{id} de cliente ativo retorna 200, ativo=false, deleted_at preenchido
// ---------------------------------------------------------------------------

test('AC-007: DELETE /clientes/{id} de cliente ativo retorna 200 e seta ativo=false', function () {
    /** @ac AC-007 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    // Cria cliente ativo via endpoint
    $payload = [
        'tipo_pessoa' => 'PJ',
        'cnpj_cpf' => '11.222.333/0001-81',
        'razao_social' => 'Empresa Para Deletar',
        'logradouro' => 'Rua Teste',
        'numero' => '1',
        'bairro' => 'Centro',
        'cidade' => 'Sao Paulo',
        'uf' => 'SP',
        'cep' => '01310100',
        'regime_tributario' => 'simples',
    ];

    $createResponse = $this->postJson('/clientes', $payload);
    $createResponse->assertStatus(201);
    $clienteId = $createResponse->json('data.id');

    // DELETE do cliente ativo
    $deleteResponse = $this->deleteJson("/clientes/{$clienteId}");

    $deleteResponse->assertStatus(200);
    $deleteResponse->assertJsonPath('data.ativo', false);

    $this->endTenant();
});

test('AC-007: DELETE /clientes/{id} preenche deleted_at no registro', function () {
    /** @ac AC-007 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $payload = [
        'tipo_pessoa' => 'PF',
        'cnpj_cpf' => '529.982.247-25',
        'razao_social' => 'Pessoa Para Deletar',
        'logradouro' => 'Rua Teste',
        'numero' => '2',
        'bairro' => 'Centro',
        'cidade' => 'Rio de Janeiro',
        'uf' => 'RJ',
        'cep' => '20040020',
        'regime_tributario' => 'isento',
    ];

    $createResponse = $this->postJson('/clientes', $payload);
    $createResponse->assertStatus(201);
    $clienteId = $createResponse->json('data.id');

    $this->deleteJson("/clientes/{$clienteId}");

    // Verifica que deleted_at foi preenchido (usando withTrashed para SoftDeletes)
    $this->assertDatabaseHas('clientes', [
        'id' => $clienteId,
        'ativo' => false,
    ]);

    // deleted_at nao pode ser null
    $this->assertDatabaseMissing('clientes', [
        'id' => $clienteId,
        'deleted_at' => null,
    ]);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-008: DELETE /clientes/{id} de cliente ja inativo retorna 409
// ---------------------------------------------------------------------------

test('AC-008: DELETE /clientes/{id} de cliente ja inativo retorna 409', function () {
    /** @ac AC-008 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    // Cria e desativa o cliente
    $payload = [
        'tipo_pessoa' => 'PJ',
        'cnpj_cpf' => '11.222.333/0001-81',
        'razao_social' => 'Empresa Ja Inativa',
        'logradouro' => 'Rua Teste',
        'numero' => '3',
        'bairro' => 'Centro',
        'cidade' => 'Sao Paulo',
        'uf' => 'SP',
        'cep' => '01310100',
        'regime_tributario' => 'simples',
    ];

    $createResponse = $this->postJson('/clientes', $payload);
    $createResponse->assertStatus(201);
    $clienteId = $createResponse->json('data.id');

    // Primeira desativacao — deve retornar 200
    $firstDelete = $this->deleteJson("/clientes/{$clienteId}");
    $firstDelete->assertStatus(200);

    // Segunda desativacao — deve retornar 409
    $secondDelete = $this->deleteJson("/clientes/{$clienteId}");
    $secondDelete->assertStatus(409);

    $this->endTenant();
});

test('AC-008: DELETE /clientes/{id} ja inativo nao altera o registro', function () {
    /** @ac AC-008 */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $payload = [
        'tipo_pessoa' => 'PF',
        'cnpj_cpf' => '529.982.247-25',
        'razao_social' => 'Pessoa Ja Inativa',
        'logradouro' => 'Rua Teste',
        'numero' => '4',
        'bairro' => 'Centro',
        'cidade' => 'Sao Paulo',
        'uf' => 'SP',
        'cep' => '01310100',
        'regime_tributario' => 'isento',
    ];

    $createResponse = $this->postJson('/clientes', $payload);
    $createResponse->assertStatus(201);
    $clienteId = $createResponse->json('data.id');

    // Desativa
    $this->deleteJson("/clientes/{$clienteId}");

    // Captura updated_at apos primeira desativacao
    $record = DB::table('clientes')
        ->where('id', $clienteId)
        ->first();
    $updatedAtAfterFirst = $record->updated_at;

    // Tenta desativar novamente — 409, sem alteracao
    $secondDelete = $this->deleteJson("/clientes/{$clienteId}");
    $secondDelete->assertStatus(409);

    $recordAfterSecond = DB::table('clientes')
        ->where('id', $clienteId)
        ->first();

    expect($recordAfterSecond->updated_at)->toBe($updatedAtAfterFirst);

    $this->endTenant();
});
