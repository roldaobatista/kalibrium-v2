<?php

declare(strict_types=1);

use App\Enums\MobileDeviceStatus;
use App\Livewire\MobileDevices\IndexPage;
use App\Models\MobileDevice;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

// Garante limpeza do TenantContext estático após cada teste.
afterEach(function (): void {
    TenantContext::reset();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function manager_setup(): array
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['password' => Hash::make('Senha@123')]);
    $tenantUser = TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::MANAGER,
        'status' => 'active',
    ]);

    return compact('tenant', 'user', 'tenantUser');
}

function technician_setup(Tenant $tenant): array
{
    $user = User::factory()->create();
    $tenantUser = TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => 'active',
    ]);

    return compact('user', 'tenantUser');
}

/**
 * Seta o contexto de tenant tanto no request attributes (para requests HTTP normais)
 * quanto no TenantContext estático (para o global scope dentro do Livewire test).
 */
function set_tenant_context(Tenant $tenant, TenantUser $tenantUser): void
{
    TenantContext::setTenantId((int) $tenant->id);
    request()->attributes->set('current_tenant', $tenant);
    request()->attributes->set('current_tenant_id', $tenant->id);
    request()->attributes->set('current_tenant_user', $tenantUser);
}

// ---------------------------------------------------------------------------
// Isolamento multi-tenant
// ---------------------------------------------------------------------------

test('lista mostra apenas devices do tenant atual', function (): void {
    ['tenant' => $tenantA, 'user' => $managerA, 'tenantUser' => $tuA] = manager_setup();
    ['tenant' => $tenantB] = manager_setup();

    $deviceA = MobileDevice::factory()->create(['tenant_id' => $tenantA->id]);
    MobileDevice::factory()->create(['tenant_id' => $tenantB->id]);

    set_tenant_context($tenantA, $tuA);

    $component = Livewire::actingAs($managerA)
        ->test(IndexPage::class);

    $devices = $component->viewData('devices');

    expect($devices->pluck('id')->toArray())->toContain($deviceA->id);
    expect($devices->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Autorização
// ---------------------------------------------------------------------------

test('tecnico comum nao pode acessar a tela (403)', function (): void {
    ['tenant' => $tenant] = manager_setup();
    ['user' => $techUser, 'tenantUser' => $techTU] = technician_setup($tenant);

    set_tenant_context($tenant, $techTU);

    Livewire::actingAs($techUser)
        ->test(IndexPage::class)
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Aprovar
// ---------------------------------------------------------------------------

test('aprovar muda status para approved e seta approved_by e approved_at', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = manager_setup();

    $device = MobileDevice::factory()->pending()->create(['tenant_id' => $tenant->id]);

    set_tenant_context($tenant, $tu);

    Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->call('aprovar', $device->id);

    $device->refresh();

    expect($device->status)->toBe(MobileDeviceStatus::Approved);
    expect($device->approved_by_user_id)->toBe($manager->id);
    expect($device->approved_at)->not->toBeNull();
});

test('aprovar registra log de auditoria', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = manager_setup();

    $device = MobileDevice::factory()->pending()->create(['tenant_id' => $tenant->id]);

    set_tenant_context($tenant, $tu);

    Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->call('aprovar', $device->id);

    $this->assertDatabaseHas('tenant_audit_logs', [
        'user_id' => $manager->id,
        'action' => 'mobile_device.approved',
    ]);
});

// ---------------------------------------------------------------------------
// Recusar
// ---------------------------------------------------------------------------

test('recusar muda status para revoked', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = manager_setup();

    $device = MobileDevice::factory()->pending()->create(['tenant_id' => $tenant->id]);

    set_tenant_context($tenant, $tu);

    Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->call('recusar', $device->id);

    expect($device->refresh()->status)->toBe(MobileDeviceStatus::Revoked);
});

// ---------------------------------------------------------------------------
// Bloquear
// ---------------------------------------------------------------------------

test('bloquear device aprovado muda para revoked', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = manager_setup();

    $device = MobileDevice::factory()->approved()->create(['tenant_id' => $tenant->id]);

    set_tenant_context($tenant, $tu);

    Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->call('bloquear', $device->id);

    expect($device->refresh()->status)->toBe(MobileDeviceStatus::Revoked);
});

// ---------------------------------------------------------------------------
// Reativar
// ---------------------------------------------------------------------------

test('reativar device revoked volta para pending', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = manager_setup();

    $device = MobileDevice::factory()->revoked()->create(['tenant_id' => $tenant->id]);

    set_tenant_context($tenant, $tu);

    Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->call('reativar', $device->id);

    $device->refresh();

    expect($device->status)->toBe(MobileDeviceStatus::Pending);
    expect($device->approved_at)->toBeNull();
    expect($device->revoked_at)->toBeNull();
});
