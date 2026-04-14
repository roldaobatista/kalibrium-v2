<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Auth\RecoveryCodeHasher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;

function slice007_routes(): array
{
    return [
        'login' => '/auth/login',
        'forgot_password' => '/auth/forgot-password',
        'reset_password' => '/auth/reset-password',
        'two_factor_challenge' => '/auth/two-factor-challenge',
        'app' => '/app',
    ];
}

function slice007_unique_email(): string
{
    return 'usuario+'.Str::uuid().'@example.com';
}

function slice007_persisted_user(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

function slice007_required_model(string $class): string
{
    expect(class_exists($class))->toBeTrue("O modelo {$class} deve existir para montar o contexto do slice 007.");

    return $class;
}

function slice007_user_with_access_context(array $overrides = []): array
{
    $tenantClass = slice007_required_model('App\\Models\\Tenant');
    $tenantUserClass = slice007_required_model('App\\Models\\TenantUser');
    $password = $overrides['password'] ?? 'SenhaSegura123!';
    $role = $overrides['role'] ?? 'tecnico';
    $requiresTwoFactor = (bool) ($overrides['requires_2fa'] ?? in_array($role, ['gerente', 'administrativo'], true));
    $twoFactorSecret = $overrides['two_factor_secret'] ?? app(TwoFactorAuthenticationProvider::class)->generateSecretKey();
    $recoveryCodes = $overrides['recovery_codes'] ?? ['recovery-code-1'];

    $user = slice007_persisted_user([
        'email' => $overrides['email'] ?? slice007_unique_email(),
        'password' => Hash::make($password),
        'two_factor_secret' => $requiresTwoFactor ? encrypt($twoFactorSecret) : null,
        'two_factor_recovery_codes' => $requiresTwoFactor ? RecoveryCodeHasher::hashMany($recoveryCodes) : [],
        'two_factor_confirmed_at' => $requiresTwoFactor ? now() : null,
    ]);
    $tenant = $tenantClass::factory()->create([
        'status' => $overrides['tenant_status'] ?? 'active',
    ]);
    $tenantUser = $tenantUserClass::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => $overrides['binding_status'] ?? 'active',
        'requires_2fa' => $requiresTwoFactor,
    ]);

    return [
        'user' => $user,
        'tenant' => $tenant,
        'tenant_user' => $tenantUser,
        'password' => $password,
        'two_factor_secret' => $twoFactorSecret,
        'recovery_codes' => $recoveryCodes,
    ];
}

function slice007_login_payload(array $overrides = []): array
{
    return array_merge([
        'email' => slice007_unique_email(),
        'password' => 'SenhaSegura123!',
        'remember' => false,
    ], $overrides);
}

function slice007_forgot_password_payload(array $overrides = []): array
{
    return array_merge([
        'email' => slice007_unique_email(),
    ], $overrides);
}

function slice007_reset_password_token_for(User $user): string
{
    return Password::broker()->createToken($user);
}

function slice007_reset_password_payload(string $token, string $email, array $overrides = []): array
{
    return array_merge([
        'token' => $token,
        'email' => $email,
        'password' => 'NovaSenhaSegura123!',
        'password_confirmation' => 'NovaSenhaSegura123!',
    ], $overrides);
}

function slice007_two_factor_payload(array $overrides = []): array
{
    return array_merge([
        'code' => '000000',
    ], $overrides);
}

function slice007_current_totp_code(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

function slice007_invalid_totp_code(string $secret): string
{
    $validCode = slice007_current_totp_code($secret);
    $firstDigit = (int) $validCode[0];
    $replacementDigit = (string) (($firstDigit + 1) % 10);

    return $replacementDigit.substr($validCode, 1);
}

function slice007_two_factor_pending_session(array $context): array
{
    return [
        'auth.two_factor_pending' => true,
        'auth.two_factor_user_id' => $context['user']->id,
        'auth.two_factor_tenant_id' => $context['tenant']->id,
    ];
}

function slice007_malicious_login_payload(): array
{
    return [
        'email' => '<script>alert(1)</script>@example.com OR 1=1',
        'password' => "'; DROP TABLE users; --",
        'remember' => false,
    ];
}

function slice007_sensitive_fragments(): array
{
    return [
        'SenhaSegura123!',
        'NovaSenhaSegura123!',
        '123456',
        'recovery-code-1',
        'totp-secret-1',
        'reset-token-1',
        '<script>alert(1)</script>',
        'DROP TABLE users',
    ];
}

function slice007_assert_body_does_not_leak_secrets(TestResponse $response, array $secrets = []): void
{
    $body = (string) $response->getContent();

    foreach ($secrets === [] ? slice007_sensitive_fragments() : $secrets as $secret) {
        expect($body)->not->toContain($secret);
    }
}
