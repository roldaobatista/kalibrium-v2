<?php

declare(strict_types=1);

/**
 * Slice 014 — E03-S02a: Criação de contato (AC-001, AC-002, AC-015, AC-017)
 *
 * Todos os testes exercem endpoints que AINDA NÃO EXISTEM neste slice.
 * Red natural: 404/405 para rotas novas de contato.
 */

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-014', 'contato-creation');

// ---------------------------------------------------------------------------
// Helper local: cria usuário com role específica no tenant
// ---------------------------------------------------------------------------

function criarUsuarioContatoRole(string $role, int $tenantId): User
{
    $user = User::factory()->create([
        'email' => "{$role}-contato-".uniqid().'@test.com',
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
// AC-001 — Criação de contato vinculado a cliente
// ---------------------------------------------------------------------------

test('AC-001: POST /clientes/{id}/contatos cria contato com ativo=true e retorna 201', function () {
    /** @ac AC-001 */
    $tenantA = $this->tenantA();
    $atendente = criarUsuarioContatoRole('atendente', $tenantA->id);

    $cliente = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $payload = [
        'nome' => 'Contato Primário',
        'email' => 'contato@empresa.test',
        'papel' => 'comprador',
    ];

    $response = $this->postJson("/clientes/{$cliente->id}/contatos", $payload);

    $response->assertStatus(201);

    $this->assertDatabaseHas('contatos', [
        'cliente_id' => $cliente->id,
        'nome' => 'Contato Primário',
        'ativo' => true,
    ]);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-002 — Múltiplos contatos por cliente
// ---------------------------------------------------------------------------

test('AC-002: cliente pode ter múltiplos contatos com papéis distintos e GET lista ambos', function () {
    /** @ac AC-002 */
    $tenantA = $this->tenantA();
    $atendente = criarUsuarioContatoRole('atendente', $tenantA->id);

    $cliente = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $this->postJson("/clientes/{$cliente->id}/contatos", [
        'nome' => 'Contato Comprador',
        'email' => 'comprador@empresa.test',
        'papel' => 'comprador',
    ]);

    $this->postJson("/clientes/{$cliente->id}/contatos", [
        'nome' => 'Contato Responsável',
        'email' => 'responsavel@empresa.test',
        'papel' => 'responsavel_tecnico',
    ]);

    $listagem = $this->getJson("/clientes/{$cliente->id}/contatos");

    $listagem->assertStatus(200);

    $dados = $listagem->json('data');
    expect(count($dados))->toBeGreaterThanOrEqual(2);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-015 — Validação condicional: POST sem email e sem whatsapp retorna 422
// ---------------------------------------------------------------------------

test('AC-015: POST sem email e sem whatsapp retorna 422 com erro de validação', function () {
    /** @ac AC-015 */
    $tenantA = $this->tenantA();
    $atendente = criarUsuarioContatoRole('atendente', $tenantA->id);

    $cliente = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $response = $this->postJson("/clientes/{$cliente->id}/contatos", [
        'nome' => 'Contato Sem Contato',
        'papel' => 'comprador',
        // sem email, sem whatsapp
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('email');

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-017 — PUT ignora tentativa de mutar cliente_id
// ---------------------------------------------------------------------------

test('AC-017: PUT /contatos/{id} com cliente_id diferente não altera o cliente_id original', function () {
    /** @ac AC-017 */
    $tenantA = $this->tenantA();
    $atendente = criarUsuarioContatoRole('atendente', $tenantA->id);

    $clienteOriginal = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);
    $clienteOutro = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);

    // Cria contato diretamente no banco (model ainda não existe — falha aqui = red)
    $contatoId = DB::table('contatos')->insertGetId([
        'tenant_id' => $tenantA->id,
        'cliente_id' => $clienteOriginal->id,
        'nome' => 'Contato Original',
        'email' => 'original@empresa.test',
        'papel' => 'comprador',
        'principal' => false,
        'ativo' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $response = $this->putJson("/contatos/{$contatoId}", [
        'cliente_id' => $clienteOutro->id,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('contatos', [
        'id' => $contatoId,
        'cliente_id' => $clienteOriginal->id, // inalterado
    ]);

    $this->endTenant();
});
