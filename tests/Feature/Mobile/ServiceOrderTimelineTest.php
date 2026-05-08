<?php

declare(strict_types=1);

use App\Enums\MobileDeviceStatus;
use App\Models\MobileDevice;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderEvent;
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

function t_tenant(): Tenant
{
    return Tenant::factory()->create();
}

function t_user(Tenant $tenant, string $role = TenantRole::TECHNICIAN): User
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

function t_token(User $user, Tenant $tenant, string $deviceId = 'device-t-test'): string
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

function t_set_context(Tenant $tenant): void
{
    TenantContext::setTenantId((int) $tenant->id);
    request()->attributes->set('current_tenant', $tenant);
}

// ---------------------------------------------------------------------------
// 1. Eventos retornados na ordem correta
// ---------------------------------------------------------------------------

test('timeline retorna eventos ordenados por created_at desc', function (): void {
    $tenant = t_tenant();
    $user = t_user($tenant);
    $token = t_token($user, $tenant);

    t_set_context($tenant);

    $order = ServiceOrder::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'client_name' => 'Cliente',
        'instrument_description' => 'Inst',
        'status' => 'received',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $event1 = ServiceOrderEvent::create([
        'id' => (string) Str::uuid(),
        'service_order_id' => $order->id,
        'user_id' => $user->id,
        'event_type' => 'status_change',
        'old_value' => 'received',
        'new_value' => 'in_progress',
        'created_at' => now()->subMinutes(5),
    ]);

    $event2 = ServiceOrderEvent::create([
        'id' => (string) Str::uuid(),
        'service_order_id' => $order->id,
        'user_id' => $user->id,
        'event_type' => 'status_change',
        'old_value' => 'in_progress',
        'new_value' => 'completed',
        'created_at' => now(),
    ]);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-t-test')
        ->getJson("/api/mobile/service-orders/{$order->id}/timeline");

    $response->assertOk();
    $eventIds = array_column($response->json('events'), 'id');
    expect($eventIds[0])->toBe($event2->id);
    expect($eventIds[1])->toBe($event1->id);
});

// ---------------------------------------------------------------------------
// 2. Apenas membros/responsável acessam
// ---------------------------------------------------------------------------

test('timeline e acessivel apenas para membro ou responsavel da OS', function (): void {
    $tenant = t_tenant();
    $owner = t_user($tenant);
    $outsider = t_user($tenant);
    $tokenOutsider = t_token($outsider, $tenant);

    t_set_context($tenant);

    $order = ServiceOrder::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'client_name' => 'Cliente',
        'instrument_description' => 'Inst',
        'status' => 'received',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->withToken($tokenOutsider)
        ->withHeader('X-Device-Id', 'device-t-test')
        ->getJson("/api/mobile/service-orders/{$order->id}/timeline");

    $response->assertStatus(404);
});

// ---------------------------------------------------------------------------
// 3. Membro da equipe acessa
// ---------------------------------------------------------------------------

test('timeline e acessivel por membro da equipe da OS', function (): void {
    $tenant = t_tenant();
    $owner = t_user($tenant);
    $member = t_user($tenant);
    $tokenMember = t_token($member, $tenant);

    t_set_context($tenant);

    $order = ServiceOrder::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'client_name' => 'Cliente',
        'instrument_description' => 'Inst',
        'status' => 'received',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ServiceOrderMember::create([
        'service_order_id' => $order->id,
        'user_id' => $member->id,
        'role' => 'technician',
    ]);

    ServiceOrderEvent::create([
        'id' => (string) Str::uuid(),
        'service_order_id' => $order->id,
        'user_id' => $owner->id,
        'event_type' => 'status_change',
        'old_value' => 'received',
        'new_value' => 'in_progress',
        'created_at' => now(),
    ]);

    $response = $this->withToken($tokenMember)
        ->withHeader('X-Device-Id', 'device-t-test')
        ->getJson("/api/mobile/service-orders/{$order->id}/timeline");

    $response->assertOk();
    expect($response->json('events'))->toHaveCount(1);
    expect($response->json('events.0.user_name'))->toBe($owner->name);
});
