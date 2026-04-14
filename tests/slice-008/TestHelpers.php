<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

function slice008_routes(): array
{
    return [
        'tenant_settings' => '/settings/tenant',
    ];
}

function slice008_unique_email(): string
{
    return 'lab+'.Str::uuid().'@example.com';
}

function slice008_persisted_user(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

function slice008_user_with_tenant_context(array $overrides = []): array
{
    $password = (string) ($overrides['password'] ?? 'SenhaSegura123!');
    $role = (string) ($overrides['role'] ?? 'gerente');
    $tenantStatus = (string) ($overrides['tenant_status'] ?? 'active');
    $bindingStatus = (string) ($overrides['binding_status'] ?? 'active');
    $tenantName = (string) ($overrides['tenant_name'] ?? ('Laboratorio '.Str::uuid()));
    $requiresTwoFactor = (bool) ($overrides['requires_2fa'] ?? false);

    $user = slice008_persisted_user([
        'name' => $overrides['user_name'] ?? 'Marcelo '.Str::uuid(),
        'email' => $overrides['email'] ?? slice008_unique_email(),
        'password' => Hash::make($password),
    ]);

    $tenant = Tenant::factory()->create([
        'name' => $tenantName,
        'status' => $tenantStatus,
    ]);

    $tenantUser = TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => $bindingStatus,
        'requires_2fa' => $requiresTwoFactor,
    ]);

    return [
        'user' => $user,
        'tenant' => $tenant,
        'tenant_user' => $tenantUser,
        'password' => $password,
    ];
}

function slice008_form_payload(array $overrides = []): array
{
    return array_merge([
        'legal_name' => 'Laboratorio Alfa LTDA '.Str::uuid(),
        'document_number' => '12345678000190',
        'trade_name' => 'Laboratorio Alfa '.Str::uuid(),
        'main_email' => 'contato+'.Str::uuid().'@example.com',
        'phone' => '(65) 99999-0000',
        'operational_profile' => 'basic',
        'emits_metrological_certificate' => true,
    ], $overrides);
}

function slice008_malicious_payload(array $overrides = []): array
{
    return slice008_form_payload(array_merge([
        'legal_name' => '<script>alert(1)</script>',
        'document_number' => "11111111111111' OR 1=1 --",
        'trade_name' => '<img src=x onerror=alert(1)>',
        'main_email' => 'contato@example.com<script>',
        'phone' => 'DROP TABLE tenants; --',
        'operational_profile' => 'basic',
        'emits_metrological_certificate' => true,
        'tenant_id' => 999999,
        'company_id' => 888888,
        'branch_id' => 777777,
    ], $overrides));
}

function slice008_sensitive_fragments(): array
{
    return [
        '<script>alert(1)</script>',
        '<img src=x onerror=alert(1)>',
        'OR 1=1',
        'DROP TABLE tenants',
        'tenant_id',
        'company_id',
        'branch_id',
    ];
}

function slice008_assert_body_does_not_leak_secrets(TestResponse $response, array $secrets = []): void
{
    $body = (string) $response->getContent();

    foreach ($secrets === [] ? slice008_sensitive_fragments() : $secrets as $secret) {
        expect($body)->not->toContain((string) $secret);
    }
}
