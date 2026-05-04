<?php

declare(strict_types=1);

use App\Enums\MobileDeviceStatus;
use App\Models\MobileDevice;
use App\Models\ServiceOrder;
use App\Models\SyncChange;
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

// ---------------------------------------------------------------------------
// Helpers (reutiliza padrão de SyncTest.php)
// ---------------------------------------------------------------------------

function so_tenant(): Tenant
{
    return Tenant::factory()->create();
}

function so_technician(Tenant $tenant): User
{
    $user = User::factory()->create();
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => 'active',
    ]);

    return $user;
}

function so_manager(Tenant $tenant): User
{
    $user = User::factory()->create();
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::MANAGER,
        'status' => 'active',
    ]);

    return $user;
}

function so_token(User $user, Tenant $tenant, string $deviceId = 'device-so-test'): string
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

function so_set_context(Tenant $tenant): void
{
    TenantContext::setTenantId((int) $tenant->id);
    request()->attributes->set('current_tenant', $tenant);
}

function so_create_payload(array $overrides = []): array
{
    return array_merge([
        'local_id' => (string) Str::ulid(),
        'entity_type' => 'service_order',
        'entity_id' => (string) Str::uuid(),
        'action' => 'create',
        'payload' => [
            'client_name' => 'Acme Indústria Ltda',
            'instrument_description' => 'Paquímetro Mitutoyo 200mm',
            'status' => 'received',
            'notes' => null,
            'updated_at' => now()->toIso8601String(),
        ],
    ], $overrides);
}

// ---------------------------------------------------------------------------
// 1. Push create → cria ServiceOrder + SyncChange
// ---------------------------------------------------------------------------

test('push create de OS cria ServiceOrder e SyncChange no tenant', function (): void {
    $tenant = so_tenant();
    $user = so_technician($tenant);
    $token = so_token($user, $tenant);

    so_set_context($tenant);

    $change = so_create_payload(['action' => 'create']);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-so-test')
        ->postJson('/api/mobile/sync/push', [
            'device_id' => 'device-so-test',
            'changes' => [$change],
        ]);

    $response->assertOk();
    $response->assertJsonStructure(['applied' => [['local_id', 'server_id', 'ulid', 'version']]]);
    $response->assertJsonCount(1, 'applied');
    $response->assertJsonCount(0, 'rejected');
    $response->assertJsonPath('applied.0.version', 1);

    $serverId = $response->json('applied.0.server_id');

    expect(
        ServiceOrder::withoutGlobalScope('current_tenant')
            ->where('id', $serverId)
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('client_name', 'Acme Indústria Ltda')
            ->where('status', 'received')
            ->exists()
    )->toBeTrue();

    expect(
        SyncChange::withoutGlobalScope('current_tenant')
            ->where('entity_type', 'service_order')
            ->where('entity_id', $serverId)
            ->where('action', 'create')
            ->where('tenant_id', $tenant->id)
            ->exists()
    )->toBeTrue();
});

// ---------------------------------------------------------------------------
// 2. Push create sem client_name → rejected: validation_error
// ---------------------------------------------------------------------------

test('push create de OS sem client_name e rejeitado com validation_error', function (): void {
    $tenant = so_tenant();
    $user = so_technician($tenant);
    $token = so_token($user, $tenant);

    so_set_context($tenant);

    $change = so_create_payload([
        'payload' => [
            'client_name' => '',
            'instrument_description' => 'Paquímetro',
            'status' => 'received',
            'updated_at' => now()->toIso8601String(),
        ],
    ]);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-so-test')
        ->postJson('/api/mobile/sync/push', [
            'device_id' => 'device-so-test',
            'changes' => [$change],
        ]);

    $response->assertOk();
    $response->assertJsonCount(0, 'applied');
    $response->assertJsonCount(1, 'rejected');
    $response->assertJsonPath('rejected.0.reason', 'validation_error');
});

// ---------------------------------------------------------------------------
// 3. Push create sem instrument_description → rejected: validation_error
// ---------------------------------------------------------------------------

test('push create de OS sem instrument_description e rejeitado com validation_error', function (): void {
    $tenant = so_tenant();
    $user = so_technician($tenant);
    $token = so_token($user, $tenant);

    so_set_context($tenant);

    $change = so_create_payload([
        'payload' => [
            'client_name' => 'Acme',
            'instrument_description' => '   ',
            'status' => 'received',
            'updated_at' => now()->toIso8601String(),
        ],
    ]);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-so-test')
        ->postJson('/api/mobile/sync/push', [
            'device_id' => 'device-so-test',
            'changes' => [$change],
        ]);

    $response->assertOk();
    $response->assertJsonCount(0, 'applied');
    $response->assertJsonPath('rejected.0.reason', 'validation_error');
});

// ---------------------------------------------------------------------------
// 4. Push update com updated_at mais recente → applied, version incrementa
// ---------------------------------------------------------------------------

test('push update de OS com updated_at mais recente e aplicado e incrementa version', function (): void {
    $tenant = so_tenant();
    $user = so_technician($tenant);
    $token = so_token($user, $tenant);

    so_set_context($tenant);

    $order = ServiceOrder::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'client_name' => 'Cliente Antigo',
        'instrument_description' => 'Instrumento Antigo',
        'status' => 'received',
        'version' => 1,
        'updated_at' => now()->subMinutes(10),
        'created_at' => now()->subMinutes(10),
    ]);

    $change = so_create_payload([
        'action' => 'update',
        'entity_id' => $order->id,
        'payload' => [
            'client_name' => 'Cliente Novo',
            'instrument_description' => 'Instrumento Novo',
            'status' => 'in_calibration',
            'updated_at' => now()->toIso8601String(),
        ],
    ]);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-so-test')
        ->postJson('/api/mobile/sync/push', [
            'device_id' => 'device-so-test',
            'changes' => [$change],
        ]);

    $response->assertOk();
    $response->assertJsonCount(1, 'applied');
    $response->assertJsonPath('applied.0.version', 2);

    $order->refresh();
    expect($order->client_name)->toBe('Cliente Novo');
    expect($order->status)->toBe('in_calibration');
    expect($order->version)->toBe(2);
});

// ---------------------------------------------------------------------------
// 5. Push update com updated_at mais antigo → rejected: stale_update
// ---------------------------------------------------------------------------

test('push update de OS com updated_at mais antigo e rejeitado como stale_update', function (): void {
    $tenant = so_tenant();
    $user = so_technician($tenant);
    $token = so_token($user, $tenant);

    so_set_context($tenant);

    $order = ServiceOrder::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'client_name' => 'Cliente Atual',
        'instrument_description' => 'Instrumento Atual',
        'status' => 'received',
        'version' => 2,
        'updated_at' => now(),
        'created_at' => now()->subMinutes(20),
    ]);

    $change = so_create_payload([
        'action' => 'update',
        'entity_id' => $order->id,
        'payload' => [
            'client_name' => 'Tentativa Stale',
            'instrument_description' => 'Instrumento Stale',
            'status' => 'completed',
            'updated_at' => now()->subMinutes(5)->toIso8601String(),
        ],
    ]);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-so-test')
        ->postJson('/api/mobile/sync/push', [
            'device_id' => 'device-so-test',
            'changes' => [$change],
        ]);

    $response->assertOk();
    $response->assertJsonCount(0, 'applied');
    $response->assertJsonCount(1, 'rejected');
    $response->assertJsonPath('rejected.0.reason', 'stale_update');

    $order->refresh();
    expect($order->client_name)->toBe('Cliente Atual');
});

// ---------------------------------------------------------------------------
// 6. Pull retorna OS do tenant escopada pelo user
// ---------------------------------------------------------------------------

test('pull retorna sync_change de OS do usuario no tenant', function (): void {
    $tenant = so_tenant();
    $user = so_technician($tenant);
    $token = so_token($user, $tenant);

    so_set_context($tenant);

    SyncChange::factory()->create([
        'tenant_id' => $tenant->id,
        'source_user_id' => $user->id,
        'entity_type' => 'service_order',
        'action' => 'create',
        'ulid' => (string) Str::ulid(),
    ]);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-so-test')
        ->getJson('/api/mobile/sync/pull');

    $response->assertOk();

    $entityTypes = array_column($response->json('changes'), 'entity_type');
    expect($entityTypes)->toContain('service_order');
});

// ---------------------------------------------------------------------------
// 7. Multi-tenant: OS do tenant A não aparece em pull do tenant B
// ---------------------------------------------------------------------------

test('OS do tenant A nao aparece em pull do tenant B', function (): void {
    $tenantA = so_tenant();
    $tenantB = so_tenant();
    $userA = so_technician($tenantA);
    $userB = so_technician($tenantB);
    $tokenA = so_token($userA, $tenantA, 'device-a-so');
    $tokenB = so_token($userB, $tenantB, 'device-b-so');

    so_set_context($tenantA);

    $change = so_create_payload(['action' => 'create']);

    $pushResponse = $this->withToken($tokenA)
        ->withHeader('X-Device-Id', 'device-a-so')
        ->postJson('/api/mobile/sync/push', [
            'device_id' => 'device-a-so',
            'changes' => [$change],
        ]);

    $pushResponse->assertOk();
    $serverId = $pushResponse->json('applied.0.server_id');

    TenantContext::reset();
    so_set_context($tenantB);

    $pullResponse = $this->withToken($tokenB)
        ->withHeader('X-Device-Id', 'device-b-so')
        ->getJson('/api/mobile/sync/pull');

    $pullResponse->assertOk();

    $entityIds = array_column($pullResponse->json('changes'), 'entity_id');
    expect($entityIds)->not->toContain($serverId);
});

// ---------------------------------------------------------------------------
// 8. Action desconhecida (delete) → rejected: unknown_action, registro intacto
// ---------------------------------------------------------------------------

test('push com action delete e rejeitado como unknown_action e nao apaga o registro', function (): void {
    $tenant = so_tenant();
    $user = so_technician($tenant);
    $token = so_token($user, $tenant, 'device-delete-test');

    so_set_context($tenant);

    $order = ServiceOrder::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'client_name' => 'Cliente Persistente',
        'instrument_description' => 'Instrumento Persistente',
        'status' => 'received',
        'version' => 1,
        'updated_at' => now()->subMinutes(5),
        'created_at' => now()->subMinutes(5),
    ]);

    $change = so_create_payload([
        'action' => 'delete',
        'entity_id' => $order->id,
        'payload' => [
            'client_name' => 'Cliente Persistente',
            'instrument_description' => 'Instrumento Persistente',
            'status' => 'received',
            'updated_at' => now()->toIso8601String(),
        ],
    ]);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-delete-test')
        ->postJson('/api/mobile/sync/push', [
            'device_id' => 'device-delete-test',
            'changes' => [$change],
        ]);

    $response->assertOk();
    $response->assertJsonCount(0, 'applied');
    $response->assertJsonCount(1, 'rejected');
    $response->assertJsonPath('rejected.0.reason', 'unknown_action');

    expect(
        ServiceOrder::withoutGlobalScope('current_tenant')
            ->where('id', $order->id)
            ->exists()
    )->toBeTrue();
});

// ---------------------------------------------------------------------------
// 9. Hijack: técnico B não consegue atualizar OS do técnico A
// ---------------------------------------------------------------------------

test('tecnico B nao consegue atualizar OS do tecnico A usando payload hijack', function (): void {
    $tenant = so_tenant();
    $userA = so_technician($tenant);
    $userB = so_technician($tenant);
    $tokenB = so_token($userB, $tenant, 'device-b-so');

    so_set_context($tenant);

    $order = ServiceOrder::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $userA->id,
        'client_name' => 'Cliente do A',
        'instrument_description' => 'Instrumento do A',
        'status' => 'received',
        'version' => 1,
        'updated_at' => now()->subMinutes(10),
        'created_at' => now()->subMinutes(10),
    ]);

    $change = so_create_payload([
        'action' => 'update',
        'entity_id' => $order->id,
        'payload' => [
            'client_name' => 'Invadido',
            'instrument_description' => 'Invadido',
            'status' => 'cancelled',
            'updated_at' => now()->toIso8601String(),
        ],
    ]);

    $response = $this->withToken($tokenB)
        ->withHeader('X-Device-Id', 'device-b-so')
        ->postJson('/api/mobile/sync/push', [
            'device_id' => 'device-b-so',
            'changes' => [$change],
        ]);

    $response->assertOk();
    $response->assertJsonCount(0, 'applied');
    $response->assertJsonCount(1, 'rejected');
    $response->assertJsonPath('rejected.0.reason', 'not_found');

    $order->refresh();
    expect($order->client_name)->not->toBe('Invadido');
});
