<?php

declare(strict_types=1);

/**
 * Slice 014 — E03-S02a: Desativação de contato (AC-007, AC-011)
 *
 * Todos os testes exercem endpoints que AINDA NÃO EXISTEM neste slice.
 * Red natural: 404/405 para DELETE /contatos/{id}.
 */

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-014', 'contato-deactivation');

function criarUsuarioDeactivationRole(string $role, int $tenantId): User
{
    $user = User::factory()->create([
        'email' => "{$role}-deact-".uniqid().'@test.com',
    ]);
    DB::table('tenant_users')->insert([
        'tenant_id' => $tenantId,
        'user_id'   => $user->id,
        'role'      => $role,
        'status'    => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $user;
}

function criarContatoDeactivation(int $tenantId, int $clienteId, bool $ativo = true): int
{
    return DB::table('contatos')->insertGetId([
        'tenant_id'  => $tenantId,
        'cliente_id' => $clienteId,
        'nome'       => 'Contato Desativável',
        'email'      => 'desativavel@empresa.test',
        'papel'      => 'comprador',
        'principal'  => false,
        'ativo'      => $ativo,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// AC-011 — Desativação de contato ativo
// ---------------------------------------------------------------------------

test('AC-011: DELETE /contatos/{id} persiste ativo=false, retorna 200 e contato some da listagem padrão', function () {
    /** @ac AC-011 */
    $tenantA  = $this->tenantA();
    $atendente = criarUsuarioDeactivationRole('atendente', $tenantA->id);

    $cliente   = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);
    $contatoId = criarContatoDeactivation($tenantA->id, $cliente->id, true);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $response = $this->deleteJson("/contatos/{$contatoId}");

    $response->assertStatus(200);

    $this->assertDatabaseHas('contatos', [
        'id'   => $contatoId,
        'ativo' => false,
    ]);

    // Contato não deve aparecer na listagem padrão (apenas ativos)
    $listagem = $this->getJson("/clientes/{$cliente->id}/contatos");
    $listagem->assertStatus(200);

    $ids = collect($listagem->json('data'))->pluck('id')->toArray();
    expect($ids)->not->toContain($contatoId);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-007 — Desativação de contato já inativo retorna 409
// ---------------------------------------------------------------------------

test('AC-007: DELETE /contatos/{id} em contato já inativo retorna 409 Conflict', function () {
    /** @ac AC-007 */
    $tenantA  = $this->tenantA();
    $atendente = criarUsuarioDeactivationRole('atendente', $tenantA->id);

    $cliente   = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);
    $contatoId = criarContatoDeactivation($tenantA->id, $cliente->id, false); // já inativo

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $response = $this->deleteJson("/contatos/{$contatoId}");

    $response->assertStatus(409);

    // Estado não alterado
    $this->assertDatabaseHas('contatos', [
        'id'   => $contatoId,
        'ativo' => false,
    ]);

    $this->endTenant();
});
