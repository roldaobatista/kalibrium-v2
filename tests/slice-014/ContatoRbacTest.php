<?php

declare(strict_types=1);

/**
 * Slice 014 — E03-S02a: RBAC de contato (AC-005, AC-006, AC-009, AC-010)
 *
 * Todos os testes exercem endpoints que AINDA NÃO EXISTEM neste slice.
 * Red natural: 404/405 para rotas novas; ausência de policy retorna 201/200 onde deveria ser 403.
 */

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-014', 'contato-rbac');

function criarUsuarioRbacContato(string $role, int $tenantId): User
{
    $user = User::factory()->create([
        'email' => "{$role}-rbac-ctt-".uniqid().'@test.com',
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

function criarContatoRbac(int $tenantId, int $clienteId): int
{
    return DB::table('contatos')->insertGetId([
        'tenant_id' => $tenantId,
        'cliente_id' => $clienteId,
        'nome' => 'Contato RBAC',
        'email' => 'rbac@empresa.test',
        'papel' => 'comprador',
        'principal' => false,
        'ativo' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// AC-005 — RBAC: técnico não pode editar contato
// ---------------------------------------------------------------------------

test('AC-005: técnico recebe 403 ao tentar PUT /contatos/{id}', function () {
    /** @ac AC-005 */
    $tenantA = $this->tenantA();
    $tecnico = criarUsuarioRbacContato('tecnico', $tenantA->id);

    $cliente = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);
    $contatoId = criarContatoRbac($tenantA->id, $cliente->id);

    $this->initializeTenant($tenantA);
    $this->actingAs($tecnico);

    $response = $this->putJson("/contatos/{$contatoId}", [
        'nome' => 'Tentativa Tecnico',
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('contatos', [
        'id' => $contatoId,
        'nome' => 'Contato RBAC', // inalterado
    ]);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-006 — RBAC: técnico não pode desativar contato
// ---------------------------------------------------------------------------

test('AC-006: técnico recebe 403 ao tentar DELETE /contatos/{id}', function () {
    /** @ac AC-006 */
    $tenantA = $this->tenantA();
    $tecnico = criarUsuarioRbacContato('tecnico', $tenantA->id);

    $cliente = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);
    $contatoId = criarContatoRbac($tenantA->id, $cliente->id);

    $this->initializeTenant($tenantA);
    $this->actingAs($tecnico);

    $response = $this->deleteJson("/contatos/{$contatoId}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('contatos', [
        'id' => $contatoId,
        'ativo' => true, // estado inalterado
    ]);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-009 — RBAC: técnico não pode criar contato
// ---------------------------------------------------------------------------

test('AC-009: técnico recebe 403 ao tentar POST /clientes/{id}/contatos', function () {
    /** @ac AC-009 */
    $tenantA = $this->tenantA();
    $tecnico = criarUsuarioRbacContato('tecnico', $tenantA->id);

    $cliente = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);

    $this->initializeTenant($tenantA);
    $this->actingAs($tecnico);

    $response = $this->postJson("/clientes/{$cliente->id}/contatos", [
        'nome' => 'Contato Criado por Tecnico',
        'email' => 'tecnico@empresa.test',
        'papel' => 'comprador',
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('contatos', [
        'cliente_id' => $cliente->id,
        'nome' => 'Contato Criado por Tecnico',
    ]);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-010 — RBAC: técnico pode listar contatos do seu tenant
// ---------------------------------------------------------------------------

test('AC-010: técnico recebe 200 ao fazer GET /clientes/{id}/contatos', function () {
    /** @ac AC-010 */
    $tenantA = $this->tenantA();
    $tecnico = criarUsuarioRbacContato('tecnico', $tenantA->id);

    $cliente = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);
    criarContatoRbac($tenantA->id, $cliente->id);

    $this->initializeTenant($tenantA);
    $this->actingAs($tecnico);

    $response = $this->getJson("/clientes/{$cliente->id}/contatos");

    $response->assertStatus(200);

    $this->endTenant();
});
