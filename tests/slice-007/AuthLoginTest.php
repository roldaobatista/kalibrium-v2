<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureReadOnlyTenantMode;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/TestHelpers.php';

test('AC-001: POST /auth/login autentica usuario ativo e redireciona para /app', function (): void {
    $context = slice007_user_with_access_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'tecnico',
        'requires_2fa' => false,
    ]);

    $response = $this->postJson(slice007_routes()['login'], [
        'email' => $context['user']->email,
        'password' => $context['password'],
        'remember' => false,
    ]);

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['app']);
    $this->assertAuthenticatedAs($context['user']);
})->group('slice-007', 'ac-001');

test('AC-002: POST /auth/login com 2FA exigido redireciona para /auth/two-factor-challenge', function (): void {
    $context = slice007_user_with_access_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
        'requires_2fa' => true,
    ]);

    $response = $this->postJson(slice007_routes()['login'], [
        'email' => $context['user']->email,
        'password' => $context['password'],
        'remember' => false,
    ]);

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['two_factor_challenge']);
    $response->assertSessionHas('auth.two_factor_pending', true);
    $this->assertGuest();
})->group('slice-007', 'ac-002');

test('AC-002: POST /auth/login com 2FA exigido renova a sessao antes do desafio pendente', function (): void {
    $context = slice007_user_with_access_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
        'requires_2fa' => true,
    ]);
    $this->startSession();
    $previousSessionId = $this->app['session.store']->getId();

    $response = $this->postJson(slice007_routes()['login'], [
        'email' => $context['user']->email,
        'password' => $context['password'],
        'remember' => false,
    ]);

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['two_factor_challenge']);
    $response->assertSessionHas('auth.two_factor_pending', true);
    expect($this->app['session.store']->getId())->not->toBe($previousSessionId);
})->group('slice-007', 'ac-002', 'security');

test('AC-008: POST /auth/login com credenciais incorretas retorna 422 neutro sem enumerar usuario', function (): void {
    $context = slice007_user_with_access_context([
        'email' => slice007_unique_email(),
    ]);

    $response = $this->postJson(slice007_routes()['login'], [
        'email' => $context['user']->email,
        'password' => 'SenhaErrada123!',
        'remember' => false,
    ]);

    $response->assertStatus(422);
    slice007_assert_body_does_not_leak_secrets($response, [
        'SenhaErrada123!',
    ]);
    $this->assertDatabaseHas('login_audit_logs', [
        'event' => 'auth.login.failed',
        'user_id' => $context['user']->id,
        'tenant_id' => $context['tenant']->id,
    ]);
})->group('slice-007', 'ac-008');

test('AC-009: POST /auth/login aplica rate limit e retorna 429 quando excede tentativas', function (): void {
    $payload = slice007_login_payload([
        'email' => slice007_unique_email(),
    ]);

    $response = null;
    for ($attempt = 1; $attempt <= 6; $attempt++) {
        $response = $this->postJson(slice007_routes()['login'], $payload);
    }

    expect($response)->not->toBeNull();
    $response->assertStatus(429);
})->group('slice-007', 'ac-009');

test('AC-011: POST /auth/login com tenant suspended autentica em modo somente leitura e redireciona para /app', function (): void {
    $context = slice007_user_with_access_context([
        'tenant_status' => 'suspended',
        'binding_status' => 'active',
        'role' => 'tecnico',
        'requires_2fa' => false,
    ]);

    $response = $this->postJson(slice007_routes()['login'], [
        'email' => $context['user']->email,
        'password' => $context['password'],
        'remember' => false,
    ]);

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['app']);
    $response->assertSessionHas('tenant.access_mode', 'read-only');
    $this->assertAuthenticatedAs($context['user']);
    $this->assertDatabaseHas('login_audit_logs', [
        'event' => 'auth.login.read_only_access',
        'user_id' => $context['user']->id,
        'tenant_id' => $context['tenant']->id,
    ]);
})->group('slice-007', 'ac-011');

test('AC-011: modo somente leitura bloqueia metodos mutaveis no backend', function (): void {
    $this->startSession();
    $request = Request::create('/app', 'POST');
    $request->setLaravelSession($this->app['session.store']);
    $request->session()->put('tenant.access_mode', 'read-only');

    $response = app(EnsureReadOnlyTenantMode::class)
        ->handle($request, fn (): Response => new Response('mutated', 200));

    expect($response->getStatusCode())->toBe(403);
    expect($request->attributes->get('tenant_read_only'))->toBeTrue();
})->group('slice-007', 'ac-011');

test('AC-011: login falha fechado quando usuario tem mais de um vinculo ativo sem tenant explicito', function (): void {
    $context = slice007_user_with_access_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'tecnico',
        'requires_2fa' => false,
    ]);

    $tenantClass = slice007_required_model('App\\Models\\Tenant');
    $tenantUserClass = slice007_required_model('App\\Models\\TenantUser');
    $secondTenant = $tenantClass::factory()->create([
        'status' => 'suspended',
    ]);
    $tenantUserClass::factory()->create([
        'tenant_id' => $secondTenant->id,
        'user_id' => $context['user']->id,
        'role' => 'tecnico',
        'status' => 'active',
        'requires_2fa' => false,
    ]);

    $response = $this->postJson(slice007_routes()['login'], [
        'email' => $context['user']->email,
        'password' => $context['password'],
        'remember' => false,
    ]);

    $response->assertStatus(403);
    $response->assertHeaderMissing('Location');
    $this->assertGuest();
    $this->assertDatabaseHas('login_audit_logs', [
        'event' => 'auth.login.blocked_ambiguous_tenant',
        'user_id' => $context['user']->id,
        'tenant_id' => null,
    ]);
})->group('slice-007', 'ac-011');

test('AC-012: POST /auth/login bloqueia tenant cancelled com 403 sem redirecionar para /app', function (): void {
    $context = slice007_user_with_access_context([
        'tenant_status' => 'cancelled',
        'binding_status' => 'active',
        'role' => 'tecnico',
        'requires_2fa' => false,
    ]);

    $response = $this->postJson(slice007_routes()['login'], [
        'email' => $context['user']->email,
        'password' => $context['password'],
        'remember' => false,
    ]);

    $response->assertStatus(403);
    $response->assertHeaderMissing('Location');
    $this->assertGuest();
    $this->assertDatabaseHas('login_audit_logs', [
        'event' => 'auth.login.blocked_tenant_status',
        'user_id' => $context['user']->id,
        'tenant_id' => $context['tenant']->id,
    ]);
})->group('slice-007', 'ac-012');

test('AC-012: formulario HTML de login bloqueado redireciona com erro de sessao', function (): void {
    $context = slice007_user_with_access_context([
        'tenant_status' => 'cancelled',
        'binding_status' => 'active',
        'role' => 'tecnico',
        'requires_2fa' => false,
    ]);

    $response = $this
        ->from(slice007_routes()['login'])
        ->post(slice007_routes()['login'], [
            'email' => $context['user']->email,
            'password' => $context['password'],
            'remember' => false,
        ]);

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['login']);
    $response->assertSessionHasErrors('email');
    expect((string) $response->headers->get('content-type'))->not->toContain('application/json');
})->group('slice-007', 'ac-012');

test('AC-013: POST /auth/login bloqueia vínculo suspended, invited ou removed com 403', function (string $bindingStatus): void {
    $context = slice007_user_with_access_context([
        'tenant_status' => 'active',
        'binding_status' => $bindingStatus,
        'role' => 'tecnico',
        'requires_2fa' => false,
    ]);

    $response = $this->postJson(slice007_routes()['login'], [
        'email' => $context['user']->email,
        'password' => $context['password'],
        'remember' => false,
    ]);

    $response->assertStatus(403);
    $response->assertHeaderMissing('Location');
    $this->assertGuest();
    $this->assertDatabaseHas('login_audit_logs', [
        'event' => 'auth.login.blocked_binding_status',
        'user_id' => $context['user']->id,
        'tenant_id' => $context['tenant']->id,
    ]);
})->with(['suspended', 'invited', 'removed'])->group('slice-007', 'ac-013');

test('AC-019: GET /app sem autenticacao redireciona para /auth/login', function (): void {
    $response = $this->get(slice007_routes()['app']);

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['login']);
})->group('slice-007', 'ac-019');
