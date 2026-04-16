<?php

declare(strict_types=1);

/**
 * Slice 013 — E03-S01b: Testes de detalhe de cliente (AC-012b, AC-013)
 *
 * Todos os testes exercem endpoints que AINDA NAO EXISTEM neste slice.
 * Red natural: 404 (rota inexistente) ou ausência de metodo show().
 */

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-013', 'cliente-show');

// ---------------------------------------------------------------------------
// AC-012b — RBAC: tecnico pode ver detalhe; resposta contem contatos_count e instrumentos_count
// ---------------------------------------------------------------------------

test('AC-012b: GET /clientes/{id} retorna 200 com dados completos do cliente incluindo contatos_count e instrumentos_count', function () {
    /** @ac AC-012b */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'razao_social' => 'Empresa Show Test',
        'ativo' => true,
    ]);

    $this->actingAs($userA);

    $response = $this->getJson("/clientes/{$cliente->id}");

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'id', 'tipo_pessoa', 'cnpj_cpf', 'razao_social', 'nome_fantasia',
            'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf', 'cep',
            'regime_tributario', 'limite_credito', 'ativo',
            'contatos_count', 'instrumentos_count',
            'created_at', 'updated_at',
        ],
    ]);

    $this->endTenant();
});

test('AC-012b: GET /clientes/{id} retorna contatos_count=0 e instrumentos_count=0 quando nao ha relacionamentos', function () {
    /** @ac AC-012b */
    $tenantA = $this->tenantA();
    $userA = $this->userA();

    $this->initializeTenant($tenantA);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'ativo' => true,
    ]);

    $this->actingAs($userA);

    $response = $this->getJson("/clientes/{$cliente->id}");

    $response->assertStatus(200);
    $response->assertJsonPath('data.contatos_count', 0);
    $response->assertJsonPath('data.instrumentos_count', 0);

    $this->endTenant();
});

test('AC-012b: tecnico faz GET /clientes/{id} e recebe 200 com dados completos', function () {
    /** @ac AC-012b */
    $tenantA = $this->tenantA();

    $this->initializeTenant($tenantA);

    $userTecnico = User::factory()->create([
        'email' => 'tecnico-show-'.uniqid().'@test.com',
    ]);
    DB::table('tenant_users')->insert([
        'tenant_id' => $tenantA->id,
        'user_id' => $userTecnico->id,
        'role' => 'tecnico',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cliente = Cliente::factory()->create([
        'tenant_id' => $tenantA->id,
        'ativo' => true,
    ]);

    $this->actingAs($userTecnico);

    $response = $this->getJson("/clientes/{$cliente->id}");

    $response->assertStatus(200);
    $response->assertJsonPath('data.id', $cliente->id);
    $response->assertJsonPath('data.contatos_count', 0);
    $response->assertJsonPath('data.instrumentos_count', 0);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-013 — Cross-tenant: cliente de outro tenant retorna 404
// ---------------------------------------------------------------------------

test('AC-013: GET /clientes/{id} com ID de cliente do tenant B retorna 404 para usuario do tenant A', function () {
    /** @ac AC-013 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();
    $userA = $this->userA();

    // Cria cliente no tenant B
    $this->initializeTenant($tenantB);
    $clienteB = Cliente::factory()->create([
        'tenant_id' => $tenantB->id,
        'ativo' => true,
    ]);
    $this->endTenant();

    // Usuario A tenta acessar cliente do tenant B
    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    $response = $this->getJson("/clientes/{$clienteB->id}");

    $response->assertStatus(404);

    $this->endTenant();
});

test('AC-013: global scope de tenant impede acesso cross-tenant mesmo com ID valido', function () {
    /** @ac AC-013 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();
    $userA = $this->userA();

    // Cria 5 clientes no tenant B para garantir que IDs existem no banco
    $this->initializeTenant($tenantB);
    $clientesB = Cliente::factory()->count(5)->create(['tenant_id' => $tenantB->id]);
    $this->endTenant();

    $this->initializeTenant($tenantA);
    $this->actingAs($userA);

    // Tenta acessar todos os clientes do tenant B — todos devem retornar 404
    foreach ($clientesB as $clienteB) {
        $response = $this->getJson("/clientes/{$clienteB->id}");
        $response->assertStatus(404);
    }

    $this->endTenant();
});
