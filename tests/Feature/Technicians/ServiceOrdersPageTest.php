<?php

declare(strict_types=1);

use App\Livewire\Technicians\ServiceOrdersPage;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

afterEach(function (): void {
    TenantContext::reset();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function so_page_manager_setup(): array
{
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create();
    $managerTenantUser = TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $manager->id,
        'role' => TenantRole::MANAGER,
        'status' => 'active',
    ]);

    $technician = User::factory()->create();
    $technicianTenantUser = TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $technician->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => 'active',
    ]);

    return compact('tenant', 'manager', 'managerTenantUser', 'technician', 'technicianTenantUser');
}

function so_page_set_context(Tenant $tenant, TenantUser $tenantUser): void
{
    TenantContext::setTenantId((int) $tenant->id);
    request()->attributes->set('current_tenant', $tenant);
    request()->attributes->set('current_tenant_user', $tenantUser);
}

function so_create(Tenant $tenant, User $technician, array $overrides = []): ServiceOrder
{
    return ServiceOrder::withoutGlobalScope('current_tenant')->create(array_merge([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $technician->id,
        'client_name' => 'Cliente Teste',
        'instrument_description' => 'Instrumento Teste',
        'status' => 'received',
        'version' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

// ---------------------------------------------------------------------------
// 1. Gerente vê OS do técnico na sua tela
// ---------------------------------------------------------------------------

test('gerente ve OS do tecnico na pagina de ordens de servico', function (): void {
    $data = so_page_manager_setup();
    ['tenant' => $tenant, 'manager' => $manager, 'managerTenantUser' => $managerTu, 'technician' => $technician] = $data;

    so_create($tenant, $technician, ['client_name' => 'Empresa Alpha', 'status' => 'in_calibration']);

    so_page_set_context($tenant, $managerTu);

    $component = Livewire::actingAs($manager)
        ->test(ServiceOrdersPage::class, ['technicianUserId' => $technician->id]);

    $component->assertSee('Empresa Alpha');
    $component->assertSee('Em calibração');
});

// ---------------------------------------------------------------------------
// 2. OS de outro técnico do mesmo tenant NÃO aparece filtrada por user
// ---------------------------------------------------------------------------

test('OS de outro tecnico do mesmo tenant nao aparece na pagina do tecnico correto', function (): void {
    $data = so_page_manager_setup();
    ['tenant' => $tenant, 'manager' => $manager, 'managerTenantUser' => $managerTu, 'technician' => $technician] = $data;

    $outroTecnico = User::factory()->create();
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $outroTecnico->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => 'active',
    ]);

    so_create($tenant, $technician, ['client_name' => 'OS do Técnico Correto']);
    so_create($tenant, $outroTecnico, ['client_name' => 'OS de Outro Técnico']);

    so_page_set_context($tenant, $managerTu);

    $component = Livewire::actingAs($manager)
        ->test(ServiceOrdersPage::class, ['technicianUserId' => $technician->id]);

    $component->assertSee('OS do Técnico Correto');
    $component->assertDontSee('OS de Outro Técnico');
});

// ---------------------------------------------------------------------------
// 3. OS de tenant diferente não aparece (multi-tenant)
// ---------------------------------------------------------------------------

test('OS de outro tenant nao aparece na pagina do tecnico', function (): void {
    $data = so_page_manager_setup();
    ['tenant' => $tenant, 'manager' => $manager, 'managerTenantUser' => $managerTu, 'technician' => $technician] = $data;

    $outerTenant = Tenant::factory()->create();
    $outerTech = User::factory()->create();

    so_create($tenant, $technician, ['client_name' => 'OS do Tenant Correto']);
    so_create($outerTenant, $outerTech, ['client_name' => 'OS do Outro Tenant']);

    so_page_set_context($tenant, $managerTu);

    $component = Livewire::actingAs($manager)
        ->test(ServiceOrdersPage::class, ['technicianUserId' => $technician->id]);

    $component->assertSee('OS do Tenant Correto');
    $component->assertDontSee('OS do Outro Tenant');
});

// ---------------------------------------------------------------------------
// 4. Técnico não tem acesso à página (somente gerente)
// ---------------------------------------------------------------------------

test('tecnico nao tem acesso a pagina de OS do gerente', function (): void {
    $data = so_page_manager_setup();
    ['tenant' => $tenant, 'technician' => $technician, 'technicianTenantUser' => $techTu] = $data;

    so_page_set_context($tenant, $techTu);

    Livewire::actingAs($technician)
        ->test(ServiceOrdersPage::class, ['technicianUserId' => $technician->id])
        ->assertForbidden();
});
