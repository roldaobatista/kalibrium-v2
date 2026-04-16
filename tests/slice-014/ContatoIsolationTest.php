<?php

declare(strict_types=1);

/**
 * Slice 014 — E03-S02a: Isolamento de tenant (AC-008, AC-013, AC-014, AC-016)
 *
 * Todos os testes exercem endpoints que AINDA NÃO EXISTEM neste slice.
 * Red natural: 404/405 para rotas novas; ausência de ScopesToCurrentTenant
 * retornaria 200 onde deveria ser 404 para cross-tenant.
 */

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-014', 'contato-isolation');

function criarUsuarioIsolationRole(string $role, int $tenantId): User
{
    $user = User::factory()->create([
        'email' => "{$role}-iso-ctt-".uniqid().'@test.com',
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

function criarContatoIsolation(int $tenantId, int $clienteId, array $overrides = []): int
{
    return DB::table('contatos')->insertGetId(array_merge([
        'tenant_id' => $tenantId,
        'cliente_id' => $clienteId,
        'nome' => 'Contato Isolamento',
        'email' => 'isolamento@empresa.test',
        'papel' => 'comprador',
        'principal' => false,
        'ativo' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

// ---------------------------------------------------------------------------
// AC-008 — Isolamento: GET /contatos/{id} de outro tenant retorna 404
// ---------------------------------------------------------------------------

test('AC-008: GET /contatos/{id} de contato do tenant B retorna 404 para usuário do tenant A', function () {
    /** @ac AC-008 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();
    $atendente = criarUsuarioIsolationRole('atendente', $tenantA->id);

    $clienteB = Cliente::factory()->create(['tenant_id' => $tenantB->id, 'ativo' => true]);
    $contatoB = criarContatoIsolation($tenantB->id, $clienteB->id);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $response = $this->getJson("/contatos/{$contatoB}");

    $response->assertStatus(404);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-013 — Isolamento: PUT em contato de outro tenant retorna 404
// ---------------------------------------------------------------------------

test('AC-013: PUT /contatos/{id} de contato do tenant B retorna 404 para atendente do tenant A', function () {
    /** @ac AC-013 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();
    $atendente = criarUsuarioIsolationRole('atendente', $tenantA->id);

    $clienteB = Cliente::factory()->create(['tenant_id' => $tenantB->id, 'ativo' => true]);
    $contatoB = criarContatoIsolation($tenantB->id, $clienteB->id);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $response = $this->putJson("/contatos/{$contatoB}", [
        'nome' => 'Tentativa Cross-Tenant',
    ]);

    $response->assertStatus(404);

    $this->assertDatabaseHas('contatos', [
        'id' => $contatoB,
        'nome' => 'Contato Isolamento', // inalterado
    ]);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-014 — Isolamento: DELETE em contato de outro tenant retorna 404
// ---------------------------------------------------------------------------

test('AC-014: DELETE /contatos/{id} de contato do tenant B retorna 404 para atendente do tenant A', function () {
    /** @ac AC-014 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();
    $atendente = criarUsuarioIsolationRole('atendente', $tenantA->id);

    $clienteB = Cliente::factory()->create(['tenant_id' => $tenantB->id, 'ativo' => true]);
    $contatoB = criarContatoIsolation($tenantB->id, $clienteB->id, ['ativo' => true]);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $response = $this->deleteJson("/contatos/{$contatoB}");

    $response->assertStatus(404);

    $this->assertDatabaseHas('contatos', [
        'id' => $contatoB,
        'ativo' => true, // estado inalterado
    ]);

    $this->endTenant();
});

// ---------------------------------------------------------------------------
// AC-016 — GET /contatos/{id} do próprio tenant retorna 200 com campos esperados
// ---------------------------------------------------------------------------

test('AC-016: GET /contatos/{id} do próprio tenant retorna 200 com campos id, cliente_id, nome, email, whatsapp, papel, principal, ativo', function () {
    /** @ac AC-016 */
    $tenantA = $this->tenantA();
    $atendente = criarUsuarioIsolationRole('atendente', $tenantA->id);

    $cliente = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);
    $contatoId = criarContatoIsolation($tenantA->id, $cliente->id, [
        'nome' => 'Contato Visível',
        'email' => 'visivel@empresa.test',
        'whatsapp' => '11987654321',
    ]);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $response = $this->getJson("/contatos/{$contatoId}");

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => ['id', 'cliente_id', 'nome', 'email', 'whatsapp', 'papel', 'principal', 'ativo'],
    ]);
    $response->assertJsonPath('data.id', $contatoId);
    $response->assertJsonPath('data.cliente_id', $cliente->id);

    $this->endTenant();
});
