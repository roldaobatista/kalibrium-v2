<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;

require_once __DIR__.'/TestHelpers.php';

test('AC-003: POST /auth/two-factor-challenge com codigo TOTP valido conclui autenticao e redireciona para /app', function (): void {
    $context = slice007_user_with_access_context([
        'role' => 'gerente',
        'requires_2fa' => true,
    ]);

    $response = $this
        ->withSession(slice007_two_factor_pending_session($context))
        ->postJson(slice007_routes()['two_factor_challenge'], slice007_two_factor_payload([
            'code' => slice007_current_totp_code($context['two_factor_secret']),
        ]));

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['app']);
    $response->assertSessionMissing('auth.two_factor_pending');
    $this->assertAuthenticatedAs($context['user']);
})->group('slice-007', 'ac-003');

test('AC-003: POST /auth/two-factor-challenge revalida modo read-only antes de criar sessao', function (): void {
    $context = slice007_user_with_access_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
        'requires_2fa' => true,
    ]);
    $context['tenant']->forceFill([
        'status' => 'suspended',
    ])->save();

    $response = $this
        ->withSession(slice007_two_factor_pending_session($context))
        ->postJson(slice007_routes()['two_factor_challenge'], slice007_two_factor_payload([
            'code' => slice007_current_totp_code($context['two_factor_secret']),
        ]));

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['app']);
    $response->assertSessionHas('tenant.access_mode', 'read-only');
    $response->assertSessionMissing('auth.two_factor_pending');
    $this->assertAuthenticatedAs($context['user']);
})->group('slice-007', 'ac-003', 'security');

test('AC-004: POST /auth/two-factor-challenge com recovery_code valido conclui autenticao, invalida o codigo e registra auditoria', function (): void {
    $context = slice007_user_with_access_context([
        'role' => 'gerente',
        'requires_2fa' => true,
        'recovery_codes' => ['recovery-code-1'],
    ]);
    $storedRecoveryCode = $context['user']->fresh()->two_factor_recovery_codes[0] ?? '';

    expect($storedRecoveryCode)->not->toBe('recovery-code-1');
    expect(Hash::check('recovery-code-1', $storedRecoveryCode))->toBeTrue();

    $response = $this
        ->withSession(slice007_two_factor_pending_session($context))
        ->postJson(slice007_routes()['two_factor_challenge'], [
            'recovery_code' => 'recovery-code-1',
        ]);

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['app']);
    $response->assertSessionMissing('auth.two_factor_pending');
    $this->assertDatabaseHas('login_audit_logs', [
        'event' => 'auth.two_factor.recovery_code_used',
        'user_id' => $context['user']->id,
        'tenant_id' => $context['tenant']->id,
    ]);
})->group('slice-007', 'ac-004');

test('AC-014: POST /auth/two-factor-challenge bloqueia se o acesso mudou antes do codigo valido', function (string $target, string $status, string $event): void {
    $context = slice007_user_with_access_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
        'requires_2fa' => true,
    ]);
    $context[$target]->forceFill([
        'status' => $status,
    ])->save();

    $response = $this
        ->withSession(slice007_two_factor_pending_session($context))
        ->postJson(slice007_routes()['two_factor_challenge'], slice007_two_factor_payload([
            'code' => slice007_current_totp_code($context['two_factor_secret']),
        ]));

    $response->assertStatus(403);
    $response->assertSessionMissing('auth.two_factor_pending');
    $this->assertGuest();
    $this->assertDatabaseHas('login_audit_logs', [
        'event' => $event,
        'user_id' => $context['user']->id,
        'tenant_id' => $context['tenant']->id,
    ]);
})->with([
    ['tenant', 'cancelled', 'auth.login.blocked_tenant_status'],
    ['tenant_user', 'removed', 'auth.login.blocked_binding_status'],
])->group('slice-007', 'ac-014', 'security');

test('AC-014: POST /auth/two-factor-challenge com codigo TOTP invalido retorna 422 e mantem o desafio pendente', function (): void {
    $context = slice007_user_with_access_context([
        'role' => 'gerente',
        'requires_2fa' => true,
    ]);

    $response = $this
        ->withSession(slice007_two_factor_pending_session($context))
        ->postJson(slice007_routes()['two_factor_challenge'], [
            'code' => slice007_invalid_totp_code($context['two_factor_secret']),
        ]);

    $response->assertStatus(422);
    $response->assertSessionHas('auth.two_factor_pending', true);
    $this->assertDatabaseHas('login_audit_logs', [
        'event' => 'auth.two_factor.failed',
        'user_id' => $context['user']->id,
        'tenant_id' => $context['tenant']->id,
    ]);
})->group('slice-007', 'ac-014');

test('AC-014: formulario HTML de 2FA invalido redireciona com erro e mantem desafio pendente', function (): void {
    $context = slice007_user_with_access_context([
        'role' => 'gerente',
        'requires_2fa' => true,
    ]);

    $response = $this
        ->from(slice007_routes()['two_factor_challenge'])
        ->withSession(slice007_two_factor_pending_session($context))
        ->post(slice007_routes()['two_factor_challenge'], [
            'code' => slice007_invalid_totp_code($context['two_factor_secret']),
        ]);

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['two_factor_challenge']);
    $response->assertSessionHas('auth.two_factor_pending', true);
    $response->assertSessionHasErrors('code');
})->group('slice-007', 'ac-014');

test('AC-015: POST /auth/two-factor-challenge com recovery_code usado ou inexistente retorna 422 e nao cria sessao', function (): void {
    $context = slice007_user_with_access_context([
        'role' => 'gerente',
        'requires_2fa' => true,
    ]);

    $response = $this
        ->withSession(slice007_two_factor_pending_session($context))
        ->postJson(slice007_routes()['two_factor_challenge'], [
            'recovery_code' => 'recovery-code-usado',
        ]);

    $response->assertStatus(422);
    $response->assertSessionHas('auth.two_factor_pending', true);
    $this->assertGuest();
    $this->assertDatabaseHas('login_audit_logs', [
        'event' => 'auth.two_factor.failed',
        'user_id' => $context['user']->id,
        'tenant_id' => $context['tenant']->id,
    ]);
})->group('slice-007', 'ac-015');

test('AC-020: GET /app com 2FA pendente bloqueia a rota e devolve para /auth/two-factor-challenge', function (): void {
    $context = slice007_user_with_access_context([
        'role' => 'gerente',
        'requires_2fa' => true,
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->withSession(slice007_two_factor_pending_session($context))
        ->get(slice007_routes()['app']);

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['two_factor_challenge']);
})->group('slice-007', 'ac-020');
