<?php

declare(strict_types=1);

/**
 * Slice 014 — E03-S02a: Edição de contato (AC-003, AC-004)
 *
 * Todos os testes exercem endpoints que AINDA NÃO EXISTEM neste slice.
 * Red natural: 404/405 para PUT /contatos/{id}.
 */

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-014', 'contato-update');

function criarUsuarioUpdateRole(string $role, int $tenantId): User
{
    $user = User::factory()->create([
        'email' => "{$role}-update-".uniqid().'@test.com',
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

function criarContatoNoBanco(int $tenantId, int $clienteId, array $overrides = []): int
{
    return DB::table('contatos')->insertGetId(array_merge([
        'tenant_id'  => $tenantId,
        'cliente_id' => $clienteId,
        'nome'       => 'Contato Editável',
        'email'      => 'editavel@empresa.test',
        'papel'      => 'comprador',
        'principal'  => false,
        'ativo'      => true,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

// ---------------------------------------------------------------------------
// AC-003 — Edição de contato (campos editáveis)
// ---------------------------------------------------------------------------

test('AC-003: PUT /contatos/{id} persiste novos valores, retorna 200 e cliente_id permanece imutável', function () {
    /** @ac AC-003 */
    $tenantA  = $this->tenantA();
    $atendente = criarUsuarioUpdateRole('atendente', $tenantA->id);

    $cliente   = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);
    $contatoId = criarContatoNoBanco($tenantA->id, $cliente->id, ['papel' => 'comprador']);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $response = $this->putJson("/contatos/{$contatoId}", [
        'nome'  => 'Novo Nome Editado',
        'papel' => 'outro',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('contatos', [
        'id'         => $contatoId,
        'nome'       => 'Novo Nome Editado',
        'papel'      => 'outro',
        'cliente_id' => $cliente->id, // inalterado
    ]);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-004 — Payload vazio no PUT retorna 422
// ---------------------------------------------------------------------------

test('AC-004: PUT /contatos/{id} com payload vazio retorna 422', function () {
    /** @ac AC-004 */
    $tenantA  = $this->tenantA();
    $atendente = criarUsuarioUpdateRole('atendente', $tenantA->id);

    $cliente   = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);
    $contatoId = criarContatoNoBanco($tenantA->id, $cliente->id);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $response = $this->putJson("/contatos/{$contatoId}", []);

    $response->assertStatus(422);

    $this->endTenant();
});
