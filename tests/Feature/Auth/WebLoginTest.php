<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;

uses(DatabaseTransactions::class);

// Garante limpeza do TenantContext estático após cada teste.
afterEach(function (): void {
    TenantContext::reset();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function wl_user(?string $email = null, ?string $password = null): User
{
    return User::factory()->create([
        'email' => $email ?? fake()->unique()->safeEmail(),
        'password' => Hash::make($password ?? 'SenhaSegura123!'),
    ]);
}

function wl_tenant(): Tenant
{
    return Tenant::factory()->create();
}

function wl_associate(User $user, Tenant $tenant, string $status = 'active'): TenantUser
{
    return TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => $status,
    ]);
}

// ---------------------------------------------------------------------------
// 1. GET /auth/login retorna 200 com os textos esperados
// ---------------------------------------------------------------------------

test('get login retorna 200 com texto Entrar e link esqueci minha senha', function (): void {
    $response = $this->get('/auth/login');

    $response->assertStatus(200);
    $response->assertSee('Entrar');
    $response->assertSee('Esqueci minha senha');
});

// ---------------------------------------------------------------------------
// 2. POST /auth/login com credenciais corretas redireciona para /mobile-devices
// ---------------------------------------------------------------------------

test('post login com credenciais corretas redireciona para dashboard', function (): void {
    $tenant = wl_tenant();
    $user = wl_user(password: 'SenhaSegura123!');
    wl_associate($user, $tenant, 'active');

    $response = $this->post('/auth/login', [
        'email' => $user->email,
        'password' => 'SenhaSegura123!',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

// ---------------------------------------------------------------------------
// 3. POST /auth/login com credenciais erradas mantém na tela com erro
// ---------------------------------------------------------------------------

test('post login com credenciais erradas retorna 422 com erro de validacao', function (): void {
    $user = wl_user(password: 'SenhaSegura123!');

    $response = $this->post('/auth/login', [
        'email' => $user->email,
        'password' => 'senha-errada',
    ]);

    $response->assertStatus(302); // redireciona de volta com erros
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

// ---------------------------------------------------------------------------
// 4. POST /auth/logout desautentica e redireciona para /auth/login
// ---------------------------------------------------------------------------

test('post logout desautentica e redireciona para pagina de login', function (): void {
    $user = wl_user();

    $response = $this->actingAs($user)->post('/auth/logout');

    $response->assertRedirect('/auth/login');
    $this->assertGuest();
});

// ---------------------------------------------------------------------------
// 5. Login é tenant-aware: usuário sem vínculo ativo com nenhum tenant
//    não consegue acessar rotas protegidas por tenant.context
// ---------------------------------------------------------------------------

test('usuario sem vinculo de tenant nao acessa rota protegida por tenant context', function (): void {
    // Usuário autenticado mas sem TenantUser — deve ser barrado com 403
    $user = wl_user();

    $response = $this->actingAs($user)->get('/mobile-devices');

    $response->assertStatus(403);
});

// ---------------------------------------------------------------------------
// 6. Usuário com TenantUser ativo acessa /mobile-devices normalmente
// ---------------------------------------------------------------------------

test('usuario gerente com vinculo ativo de tenant acessa rota protegida normalmente', function (): void {
    $tenant = wl_tenant();
    $user = wl_user();
    // /mobile-devices exige role=gerente (MobileDevicePolicy::viewAny)
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => 'active',
        'role' => TenantRole::MANAGER,
    ]);

    $response = $this->actingAs($user)->get('/mobile-devices');

    $response->assertStatus(200);
});

// ---------------------------------------------------------------------------
// 7. Brute force: 6ª tentativa de login web retorna 429
// ---------------------------------------------------------------------------

test('sexta tentativa de login web retorna 429 por throttle', function (): void {
    $user = wl_user(password: 'SenhaSegura123!');

    // Limpa qualquer rate limit residual antes do teste
    RateLimiter::clear('web|login|127.0.0.1');

    // 5 tentativas com senha errada — dentro do limite (throttle:5,1)
    for ($i = 0; $i < 5; $i++) {
        $this->post('/auth/login', [
            'email' => $user->email,
            'password' => 'senha-errada',
        ]);
    }

    // 6ª tentativa — deve ser bloqueada por throttle
    $response = $this->post('/auth/login', [
        'email' => $user->email,
        'password' => 'senha-errada',
    ]);

    $response->assertStatus(429);

    // Limpeza para não contaminar outros testes
    RateLimiter::clear('web|login|127.0.0.1');
});
