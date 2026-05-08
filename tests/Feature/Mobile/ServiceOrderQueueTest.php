<?php

declare(strict_types=1);

use App\Enums\MobileDeviceStatus;
use App\Models\MobileDevice;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderMember;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

afterEach(function (): void {
    TenantContext::reset();
});

function q_tenant(): Tenant
{
    return Tenant::factory()->create();
}

function q_user(Tenant $tenant, string $role = TenantRole::TECHNICIAN): User
{
    $user = User::factory()->create();
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => 'active',
    ]);

    return $user;
}

function q_token(User $user, Tenant $tenant, string $deviceId = 'device-q-test'): string
{
    MobileDevice::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => $deviceId,
        'status' => MobileDeviceStatus::Approved,
    ]);

    $token = $user->createToken(
        name: 'mobile:tenant:'.$tenant->id,
        abilities: ['mobile:full'],
        expiresAt: now()->addDays(4),
    );

    return $token->plainTextToken;
}

function q_set_context(Tenant $tenant): void
{
    TenantContext::setTenantId((int) $tenant->id);
    request()->attributes->set('current_tenant', $tenant);
}

// ---------------------------------------------------------------------------
// 1. Técnico vê apenas suas OSs (responsável ou membro)
// ---------------------------------------------------------------------------

test('queue retorna apenas OSs onde o tecnico e responsavel ou membro', function (): void {
    $tenant = q_tenant();
    $tecA = q_user($tenant);
    $tecB = q_user($tenant);
    $tokenA = q_token($tecA, $tenant);

    q_set_context($tenant);

    $orderA = ServiceOrder::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $tecA->id,
        'client_name' => 'Cliente A',
        'instrument_description' => 'Inst A',
        'status' => 'received',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $orderB = ServiceOrder::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $tecB->id,
        'client_name' => 'Cliente B',
        'instrument_description' => 'Inst B',
        'status' => 'received',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ServiceOrderMember::create([
        'service_order_id' => $orderA->id,
        'user_id' => $tecA->id,
        'role' => 'technician',
    ]);

    $response = $this->withToken($tokenA)
        ->withHeader('X-Device-Id', 'device-q-test')
        ->getJson('/api/mobile/queue');

    $response->assertOk();
    $ids = array_column($response->json('orders'), 'id');
    expect($ids)->toContain($orderA->id);
    expect($ids)->not->toContain($orderB->id);
});

// ---------------------------------------------------------------------------
// 2. Isolamento multi-tenant
// ---------------------------------------------------------------------------

test('queue nao retorna OS de outro tenant', function (): void {
    $tenantA = q_tenant();
    $tenantB = q_tenant();
    $userA = q_user($tenantA);
    $tokenA = q_token($userA, $tenantA, 'device-a');

    q_set_context($tenantA);

    $orderB = ServiceOrder::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantB->id,
        'user_id' => $userA->id,
        'client_name' => 'Cliente B',
        'instrument_description' => 'Inst B',
        'status' => 'received',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->withToken($tokenA)
        ->withHeader('X-Device-Id', 'device-a')
        ->getJson('/api/mobile/queue');

    $response->assertOk();
    $ids = array_column($response->json('orders'), 'id');
    expect($ids)->not->toContain($orderB->id);
});

// ---------------------------------------------------------------------------
// 3. Ordenação correta
// ---------------------------------------------------------------------------

test('queue ordena OSs com status recebido antes de concluido', function (): void {
    $tenant = q_tenant();
    $user = q_user($tenant);
    $token = q_token($user, $tenant);

    q_set_context($tenant);

    $completed = ServiceOrder::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'client_name' => 'Concluido',
        'instrument_description' => 'Inst',
        'status' => 'completed',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $received = ServiceOrder::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'client_name' => 'Recebido',
        'instrument_description' => 'Inst',
        'status' => 'received',
        'created_at' => now()->subMinute(),
        'updated_at' => now()->subMinute(),
    ]);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-q-test')
        ->getJson('/api/mobile/queue');

    $response->assertOk();
    $ids = array_column($response->json('orders'), 'id');
    expect($ids[0])->toBe($received->id);
    expect($ids[1])->toBe($completed->id);
});
