<?php

declare(strict_types=1);

use App\Livewire\Dashboard\IndexPage;
use App\Models\MobileDevice;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

afterEach(function (): void {
    TenantContext::reset();
    Carbon::setTestNow(null);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function dash_manager_setup(): array
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

function dash_set_tenant_context(Tenant $tenant, TenantUser $tenantUser): void
{
    TenantContext::setTenantId((int) $tenant->id);
    request()->attributes->set('current_tenant', $tenant);
    request()->attributes->set('current_tenant_id', $tenant->id);
    request()->attributes->set('current_tenant_user', $tenantUser);
}

// ---------------------------------------------------------------------------
// 1. Contadores corretos por status
// ---------------------------------------------------------------------------

test('gerente ve contadores corretos para cada status', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = dash_manager_setup();

    MobileDevice::factory()->count(3)->pending()->create(['tenant_id' => $tenant->id]);
    MobileDevice::factory()->count(2)->approved()->create(['tenant_id' => $tenant->id]);
    MobileDevice::factory()->count(1)->revoked()->create(['tenant_id' => $tenant->id]);

    dash_set_tenant_context($tenant, $tu);

    $component = Livewire::actingAs($manager)->test(IndexPage::class);

    expect($component->viewData('pendingCount'))->toBe(3);
    expect($component->viewData('approvedCount'))->toBe(2);
    expect($component->viewData('revokedCount'))->toBe(1);
});

test('card bloqueados soma revoked e wiped_and_revoked', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = dash_manager_setup();

    MobileDevice::factory()->revoked()->create(['tenant_id' => $tenant->id]);
    MobileDevice::factory()->wipedAndRevoked()->create(['tenant_id' => $tenant->id]);

    dash_set_tenant_context($tenant, $tu);

    $component = Livewire::actingAs($manager)->test(IndexPage::class);

    expect($component->viewData('revokedCount'))->toBe(2);
});

// ---------------------------------------------------------------------------
// 2. Isolamento tenant — contadores não vazam entre tenants
// ---------------------------------------------------------------------------

test('contadores sao isolados por tenant', function (): void {
    ['tenant' => $tenantA, 'user' => $managerA, 'tenantUser' => $tuA] = dash_manager_setup();
    ['tenant' => $tenantB] = dash_manager_setup();

    // 5 pendentes no tenant B — não devem aparecer no A
    MobileDevice::factory()->count(5)->pending()->create(['tenant_id' => $tenantB->id]);

    // 1 pendente no tenant A
    MobileDevice::factory()->pending()->create(['tenant_id' => $tenantA->id]);

    dash_set_tenant_context($tenantA, $tuA);

    $component = Livewire::actingAs($managerA)->test(IndexPage::class);

    expect($component->viewData('pendingCount'))->toBe(1);
});

// ---------------------------------------------------------------------------
// 3. Saudação varia por hora
// ---------------------------------------------------------------------------

test('saudacao bom dia quando hora e menor que 12', function (): void {
    Carbon::setTestNow(Carbon::today()->setHour(9));

    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = dash_manager_setup();
    dash_set_tenant_context($tenant, $tu);

    $component = Livewire::actingAs($manager)->test(IndexPage::class);

    expect($component->instance()->saudacao())->toBe('Bom dia');
});

test('saudacao bom dia na hora 11 ultima hora antes do meio-dia', function (): void {
    Carbon::setTestNow(Carbon::today()->setHour(11));

    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = dash_manager_setup();
    dash_set_tenant_context($tenant, $tu);

    $component = Livewire::actingAs($manager)->test(IndexPage::class);

    expect($component->instance()->saudacao())->toBe('Bom dia');
});

test('saudacao boa tarde na hora 12 primeira hora da tarde', function (): void {
    Carbon::setTestNow(Carbon::today()->setHour(12));

    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = dash_manager_setup();
    dash_set_tenant_context($tenant, $tu);

    $component = Livewire::actingAs($manager)->test(IndexPage::class);

    expect($component->instance()->saudacao())->toBe('Boa tarde');
});

test('saudacao boa tarde quando hora e entre 12 e 17', function (): void {
    Carbon::setTestNow(Carbon::today()->setHour(15));

    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = dash_manager_setup();
    dash_set_tenant_context($tenant, $tu);

    $component = Livewire::actingAs($manager)->test(IndexPage::class);

    expect($component->instance()->saudacao())->toBe('Boa tarde');
});

test('saudacao boa tarde na hora 17 ultima hora antes da noite', function (): void {
    Carbon::setTestNow(Carbon::today()->setHour(17));

    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = dash_manager_setup();
    dash_set_tenant_context($tenant, $tu);

    $component = Livewire::actingAs($manager)->test(IndexPage::class);

    expect($component->instance()->saudacao())->toBe('Boa tarde');
});

test('saudacao boa noite na hora 18 primeira hora da noite', function (): void {
    Carbon::setTestNow(Carbon::today()->setHour(18));

    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = dash_manager_setup();
    dash_set_tenant_context($tenant, $tu);

    $component = Livewire::actingAs($manager)->test(IndexPage::class);

    expect($component->instance()->saudacao())->toBe('Boa noite');
});

test('saudacao boa noite quando hora e 18 ou mais', function (): void {
    Carbon::setTestNow(Carbon::today()->setHour(20));

    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = dash_manager_setup();
    dash_set_tenant_context($tenant, $tu);

    $component = Livewire::actingAs($manager)->test(IndexPage::class);

    expect($component->instance()->saudacao())->toBe('Boa noite');
});

// ---------------------------------------------------------------------------
// 4. Estado vazio — mensagem "Tudo em dia"
// ---------------------------------------------------------------------------

test('quando nao ha pendentes a view mostra tudo em dia', function (): void {
    ['tenant' => $tenant, 'user' => $manager, 'tenantUser' => $tu] = dash_manager_setup();

    dash_set_tenant_context($tenant, $tu);

    $component = Livewire::actingAs($manager)->test(IndexPage::class);

    expect($component->viewData('pendingCount'))->toBe(0);
    $component->assertSee('Tudo em dia');
});

// ---------------------------------------------------------------------------
// 5. Técnico não-gerente não acessa (403)
// ---------------------------------------------------------------------------

test('tecnico nao gerente recebe 403 ao acessar dashboard', function (): void {
    ['tenant' => $tenant] = dash_manager_setup();

    $techUser = User::factory()->create();
    $techTU = TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $techUser->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => 'active',
    ]);

    dash_set_tenant_context($tenant, $techTU);

    Livewire::actingAs($techUser)
        ->test(IndexPage::class)
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// 6. GET /dashboard sem autenticação redireciona para /auth/login
// ---------------------------------------------------------------------------

test('acesso sem autenticacao ao dashboard redireciona para login', function (): void {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/auth/login');
});
