<?php

declare(strict_types=1);

/**
 * Slice 014 — E03-S02a: Validação de WhatsApp (AC-012)
 *
 * Todos os testes exercem endpoints que AINDA NÃO EXISTEM neste slice.
 * Red natural: 404/405 para POST /clientes/{id}/contatos.
 */

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-014', 'contato-validation');

function criarUsuarioValidationRole(string $role, int $tenantId): User
{
    $user = User::factory()->create([
        'email' => "{$role}-val-".uniqid().'@test.com',
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
// AC-012 — Validação de formato de WhatsApp (sem DDD = 8 dígitos = 422)
// ---------------------------------------------------------------------------

test('AC-012: POST com whatsapp "99999-9999" (sem DDD, 8 dígitos) retorna 422 com erro no campo whatsapp', function () {
    /** @ac AC-012 */
    $tenantA = $this->tenantA();
    $atendente = criarUsuarioValidationRole('atendente', $tenantA->id);

    $cliente = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $response = $this->postJson("/clientes/{$cliente->id}/contatos", [
        'nome' => 'Contato WhatsApp Inválido',
        'whatsapp' => '99999-9999', // sem DDD, apenas 8 dígitos
        'papel' => 'comprador',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('whatsapp');

    $this->endTenant();
});

test('AC-012: POST com whatsapp "99999999" (apenas 8 dígitos numéricos sem DDD) retorna 422', function () {
    /** @ac AC-012 */
    $tenantA = $this->tenantA();
    $atendente = criarUsuarioValidationRole('atendente', $tenantA->id);

    $cliente = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $response = $this->postJson("/clientes/{$cliente->id}/contatos", [
        'nome' => 'Contato WhatsApp Curto',
        'whatsapp' => '99999999', // 8 dígitos — menos que o mínimo de 10
        'papel' => 'comprador',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('whatsapp');

    $this->endTenant();
});

test('AC-012: POST com whatsapp "11987654321" (10+ dígitos com DDD) é aceito no campo whatsapp', function () {
    /** @ac AC-012 */
    $tenantA = $this->tenantA();
    $atendente = criarUsuarioValidationRole('atendente', $tenantA->id);

    $cliente = Cliente::factory()->create(['tenant_id' => $tenantA->id, 'ativo' => true]);

    $this->initializeTenant($tenantA);
    $this->actingAs($atendente);

    $response = $this->postJson("/clientes/{$cliente->id}/contatos", [
        'nome' => 'Contato WhatsApp Válido',
        'whatsapp' => '11987654321', // 11 dígitos — DDD + número
        'papel' => 'comprador',
    ]);

    // Deve criar com 201 (não 422) — confirma que a validação aceita o formato correto
    $response->assertStatus(201);

    $this->endTenant();
});
