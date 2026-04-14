<?php

declare(strict_types=1);

use App\Models\LoginAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

require_once __DIR__.'/TestHelpers.php';

test('AC-007: tentativa de login grava login_audit_logs com evento, user_id, IP e hash de user agent sem expor segredo', function (): void {
    $context = slice007_user_with_access_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'tecnico',
        'requires_2fa' => false,
    ]);

    $response = $this
        ->withServerVariables(['HTTP_USER_AGENT' => 'Slice007 Agent/1.0'])
        ->postJson(slice007_routes()['login'], [
            'email' => $context['user']->email,
            'password' => $context['password'],
            'remember' => false,
        ]);

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['app']);

    $this->assertDatabaseHas('login_audit_logs', [
        'event' => 'auth.login.success',
        'user_id' => $context['user']->id,
        'tenant_id' => $context['tenant']->id,
        'ip_address' => '127.0.0.1',
    ]);

    $record = DB::table('login_audit_logs')
        ->orderByDesc('id')
        ->first();

    expect($record)->not->toBeNull('AC-007: o registro de auditoria deve existir.');
    expect($record->user_agent_hash ?? null)->not->toBe('Slice007 Agent/1.0');
    expect((string) ($record->user_agent_hash ?? ''))->not->toContain('Slice007 Agent/1.0');
})->group('slice-007', 'ac-007');

test('AC-018: input malicioso em login e tratado como dado e nao reflete payload sem escape', function (): void {
    $response = $this->postJson(slice007_routes()['login'], slice007_malicious_login_payload());

    $response->assertStatus(422);
    slice007_assert_body_does_not_leak_secrets($response, [
        '<script>alert(1)</script>',
        'DROP TABLE users',
        "'; DROP TABLE users; --",
    ]);
})->group('slice-007', 'ac-018', 'security');

test('AC-021: responses e logs dos fluxos de auth nao vazam senha, token, segredo TOTP ou recovery code', function (): void {
    Mail::fake();

    $login = $this->postJson(slice007_routes()['login'], [
        'email' => 'usuario-inexistente@example.com',
        'password' => 'SenhaSegura123!',
        'remember' => false,
    ]);

    $forgot = $this->postJson(slice007_routes()['forgot_password'], [
        'email' => 'usuario-inexistente@example.com',
    ]);

    $resetUser = slice007_persisted_user([
        'email' => slice007_unique_email(),
        'password' => Hash::make('SenhaAtual123!'),
    ]);
    $reset = $this->postJson(slice007_routes()['reset_password'], slice007_reset_password_payload(
        'reset-token-1',
        $resetUser->email
    ));

    $twoFactor = $this
        ->withSession(['auth.two_factor_pending' => true])
        ->postJson(slice007_routes()['two_factor_challenge'], [
            'code' => '123456',
            'recovery_code' => 'recovery-code-1',
        ]);

    foreach ([$login, $forgot, $reset, $twoFactor] as $response) {
        slice007_assert_body_does_not_leak_secrets($response);
    }

    $logPath = storage_path('logs/laravel.log');
    if (is_file($logPath)) {
        $logContents = file_get_contents($logPath) ?: '';

        foreach (slice007_sensitive_fragments() as $secret) {
            expect($logContents)->not->toContain($secret);
        }
    }
})->group('slice-007', 'ac-021', 'security');

test('AC-021: contexto de login_audit_logs nao persiste e-mail nem segredos de autenticacao', function (): void {
    $success = slice007_user_with_access_context([
        'email' => slice007_unique_email(),
        'requires_2fa' => false,
    ]);
    $failed = slice007_user_with_access_context([
        'email' => slice007_unique_email(),
        'requires_2fa' => false,
    ]);
    $twoFactor = slice007_user_with_access_context([
        'email' => slice007_unique_email(),
        'role' => 'gerente',
        'requires_2fa' => true,
        'recovery_codes' => ['recovery-code-1'],
    ]);

    $this->postJson(slice007_routes()['login'], [
        'email' => $success['user']->email,
        'password' => $success['password'],
        'remember' => false,
    ])->assertStatus(302);
    auth()->logout();

    $this->postJson(slice007_routes()['login'], [
        'email' => $failed['user']->email,
        'password' => 'SenhaErrada123!',
        'remember' => false,
    ])->assertStatus(422);

    $this
        ->withSession(slice007_two_factor_pending_session($twoFactor))
        ->postJson(slice007_routes()['two_factor_challenge'], [
            'recovery_code' => 'recovery-code-1',
        ])->assertStatus(302);

    $records = LoginAuditLog::query()
        ->whereIn('event', [
            'auth.login.success',
            'auth.login.failed',
            'auth.two_factor.recovery_code_used',
        ])
        ->whereIn('user_id', [
            $success['user']->id,
            $failed['user']->id,
            $twoFactor['user']->id,
        ])
        ->get();

    expect($records)->toHaveCount(3);

    $forbiddenFragments = [
        $success['user']->email,
        $failed['user']->email,
        $twoFactor['user']->email,
        $success['password'],
        $failed['password'],
        'SenhaErrada123!',
        'recovery-code-1',
    ];

    foreach ($records as $record) {
        $context = json_encode($record->context ?? [], JSON_THROW_ON_ERROR);

        foreach ($forbiddenFragments as $fragment) {
            expect($context)->not->toContain($fragment);
        }
    }
})->group('slice-007', 'ac-021', 'security');
