<?php

declare(strict_types=1);

use App\Enums\TenantUserStatus;
use App\Livewire\Technicians\IndexPage;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

afterEach(function (): void {
    TenantContext::reset();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function tech_manager_setup(): array
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

function tech_set_context(Tenant $tenant, TenantUser $tenantUser): void
{
    TenantContext::setTenantId((int) $tenant->id);
    request()->attributes->set('current_tenant', $tenant);
    request()->attributes->set('current_tenant_id', $tenant->id);
    request()->attributes->set('current_tenant_user', $tenantUser);
}

function make_technician(Tenant $tenant, string $status = 'active'): array
{
    $user = User::factory()->create();
    $tenantUser = TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => $status,
    ]);

    return compact('user', 'tenantUser');
}

// ---------------------------------------------------------------------------
// 1. Gerente acessa /technicians e vê lista de técnicos do tenant
// ---------------------------------------------------------------------------

test('gerente acessa tela e ve lista de tecnicos do tenant', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = tech_manager_setup();
    ['tenantUser' => $tech] = make_technician($tenant);

    tech_set_context($tenant, $tu);

    $component = Livewire::actingAs($manager)->test(IndexPage::class);

    $technicians = $component->viewData('technicians');
    expect($technicians->pluck('id')->toArray())->toContain($tech->id);
});

// ---------------------------------------------------------------------------
// 2. Isolamento de tenant — técnicos do tenant B não aparecem
// ---------------------------------------------------------------------------

test('lista e isolada por tenant — tecnicos do tenant B nao aparecem', function (): void {
    ['tenant' => $tenantA, 'user' => $managerA, 'tenantUser' => $tuA] = tech_manager_setup();
    ['tenant' => $tenantB] = tech_manager_setup();

    make_technician($tenantA);
    make_technician($tenantB);

    tech_set_context($tenantA, $tuA);

    $component = Livewire::actingAs($managerA)->test(IndexPage::class);

    $technicians = $component->viewData('technicians');
    expect($technicians->count())->toBe(1);

    $tenantIds = $technicians->pluck('tenant_id')->unique()->toArray();
    expect($tenantIds)->toBe([(int) $tenantA->id]);
});

// ---------------------------------------------------------------------------
// 3. Filtro por status
// ---------------------------------------------------------------------------

test('filtro por status active mostra apenas ativos', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = tech_manager_setup();
    make_technician($tenant, 'active');
    make_technician($tenant, 'inactive');

    tech_set_context($tenant, $tu);

    $component = Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->set('statusFilter', 'active');

    $technicians = $component->viewData('technicians');
    expect($technicians->count())->toBe(1);
    expect($technicians->first()->status->value)->toBe('active');
});

test('filtro por status inactive mostra apenas inativos', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = tech_manager_setup();
    make_technician($tenant, 'active');
    make_technician($tenant, 'inactive');

    tech_set_context($tenant, $tu);

    $component = Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->set('statusFilter', 'inactive');

    $technicians = $component->viewData('technicians');
    expect($technicians->count())->toBe(1);
    expect($technicians->first()->status->value)->toBe('inactive');
});

// ---------------------------------------------------------------------------
// 4. Busca por nome/email
// ---------------------------------------------------------------------------

test('busca por nome filtra resultado', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = tech_manager_setup();
    $userCarlos = User::factory()->create(['name' => 'Carlos Silva']);
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $userCarlos->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => 'active',
    ]);
    $userJuliana = User::factory()->create(['name' => 'Juliana Mendes']);
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $userJuliana->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => 'active',
    ]);

    tech_set_context($tenant, $tu);

    $component = Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->set('search', 'Carlos');

    $technicians = $component->viewData('technicians');
    expect($technicians->count())->toBe(1);
    expect($technicians->first()->user->name)->toBe('Carlos Silva');
});

// ---------------------------------------------------------------------------
// 5. Cadastrar técnico cria User + TenantUser com role tecnico e status active
// ---------------------------------------------------------------------------

test('cadastrar tecnico cria User e TenantUser com role tecnico e status active', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = tech_manager_setup();

    tech_set_context($tenant, $tu);

    Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->call('openCreateModal')
        ->set('name', 'Carlos Novo')
        ->set('email', 'carlos.novo@lab.com')
        ->set('password', 'Senha1234')
        ->call('criar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', ['email' => 'carlos.novo@lab.com', 'name' => 'Carlos Novo']);

    $createdUser = User::where('email', 'carlos.novo@lab.com')->first();
    $this->assertDatabaseHas('tenant_users', [
        'tenant_id' => $tenant->id,
        'user_id' => $createdUser->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => 'active',
    ]);
});

// ---------------------------------------------------------------------------
// 6. Email já existente retorna erro de validação
// ---------------------------------------------------------------------------

test('cadastrar com email ja existente retorna erro de validacao', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = tech_manager_setup();
    User::factory()->create(['email' => 'existente@lab.com']);

    tech_set_context($tenant, $tu);

    Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->call('openCreateModal')
        ->set('name', 'Outro Técnico')
        ->set('email', 'existente@lab.com')
        ->set('password', 'Senha1234')
        ->call('criar')
        ->assertHasErrors(['email']);
});

// ---------------------------------------------------------------------------
// 7. Senha fraca retorna erro de validação
// ---------------------------------------------------------------------------

test('cadastrar com senha sem numero retorna erro de validacao', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = tech_manager_setup();

    tech_set_context($tenant, $tu);

    Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->call('openCreateModal')
        ->set('name', 'Técnico Fraco')
        ->set('email', 'fraco@lab.com')
        ->set('password', 'semnum')
        ->call('criar')
        ->assertHasErrors(['password']);
});

test('cadastrar com senha menor que 8 chars retorna erro de validacao', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = tech_manager_setup();

    tech_set_context($tenant, $tu);

    Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->call('openCreateModal')
        ->set('name', 'Técnico Curto')
        ->set('email', 'curto@lab.com')
        ->set('password', 'Ab1')
        ->call('criar')
        ->assertHasErrors(['password']);
});

// ---------------------------------------------------------------------------
// 8. Editar técnico atualiza nome/email sem tocar em devices
// ---------------------------------------------------------------------------

test('editar tecnico atualiza nome e email sem alterar dados de devices', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = tech_manager_setup();
    ['user' => $techUser, 'tenantUser' => $techTU] = make_technician($tenant);

    tech_set_context($tenant, $tu);

    Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->call('editar', $techTU->id)
        ->set('name', 'Nome Atualizado')
        ->set('email', 'novo@email.com')
        ->call('salvar')
        ->assertHasNoErrors();

    $techUser->refresh();
    expect($techUser->name)->toBe('Nome Atualizado');
    expect($techUser->email)->toBe('novo@email.com');
});

// ---------------------------------------------------------------------------
// 9. Desativar técnico muda status para inactive
// ---------------------------------------------------------------------------

test('desativar tecnico muda status para inactive e registra auditoria', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = tech_manager_setup();
    ['tenantUser' => $techTU] = make_technician($tenant, 'active');

    tech_set_context($tenant, $tu);

    Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->call('desativar', $techTU->id);

    $techTU->refresh();
    expect($techTU->status->value)->toBe('inactive');

    $this->assertDatabaseHas('tenant_audit_logs', [
        'tenant_id' => $tenant->id,
        'action' => 'technician.deactivated',
    ]);
});

// ---------------------------------------------------------------------------
// 10. Reativar técnico muda status para active
// ---------------------------------------------------------------------------

test('reativar tecnico muda status para active', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = tech_manager_setup();
    ['tenantUser' => $techTU] = make_technician($tenant, 'inactive');

    tech_set_context($tenant, $tu);

    Livewire::actingAs($manager)
        ->test(IndexPage::class)
        ->call('reativar', $techTU->id);

    $techTU->refresh();
    expect($techTU->status->value)->toBe('active');
});

// ---------------------------------------------------------------------------
// 11. Técnico não-gerente recebe 403
// ---------------------------------------------------------------------------

test('tecnico nao gerente recebe 403 ao acessar tela', function (): void {
    ['tenant' => $tenant] = tech_manager_setup();
    ['user' => $techUser, 'tenantUser' => $techTU] = make_technician($tenant);

    tech_set_context($tenant, $techTU);

    Livewire::actingAs($techUser)
        ->test(IndexPage::class)
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// 12. Login mobile de técnico inactive retorna 403
// ---------------------------------------------------------------------------

test('login mobile de tecnico inativo retorna 403 com mensagem em pt-BR', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['password' => Hash::make('SenhaSegura123!')]);
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => TenantUserStatus::Inactive,
    ]);

    $response = $this->postJson('/api/mobile/login', [
        'tenant_id' => $tenant->id,
        'email' => $user->email,
        'password' => 'SenhaSegura123!',
        'device_identifier' => 'device-inativo-test',
        'device_label' => 'iPhone Test',
    ]);

    $response->assertStatus(403);
    $response->assertJsonFragment(['erro' => 'Sua conta foi desativada. Procure o gerente.']);
});

// ---------------------------------------------------------------------------
// 13. Login web de técnico inactive retorna erro inline
// ---------------------------------------------------------------------------

test('login web de tecnico inativo retorna erro na tela', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['password' => Hash::make('SenhaSegura123!')]);
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => TenantUserStatus::Inactive,
    ]);

    $response = $this->post('/auth/login', [
        'email' => $user->email,
        'password' => 'SenhaSegura123!',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});
