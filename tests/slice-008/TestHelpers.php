<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

function slice008_valid_cnpj(): string
{
    $base = str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT).'0001';
    $firstDigit = slice008_cnpj_digit($base, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
    $secondDigit = slice008_cnpj_digit($base.$firstDigit, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

    return $base.$firstDigit.$secondDigit;
}

/**
 * @param  array<int, int>  $weights
 */
function slice008_cnpj_digit(string $base, array $weights): int
{
    $sum = 0;

    foreach ($weights as $index => $weight) {
        $sum += (int) $base[$index] * $weight;
    }

    $remainder = $sum % 11;

    return $remainder < 2 ? 0 : 11 - $remainder;
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
        'document_number' => slice008_valid_cnpj(),
        'trade_name' => 'Laboratorio Alfa '.Str::uuid(),
        'main_email' => 'contato+'.Str::uuid().'@example.com',
        'phone' => '(65) 99999-0000',
        'operational_profile' => 'basic',
        'emits_metrological_certificate' => true,
    ], $overrides);
}

function slice008_normalize_document_number(string $value): string
{
    return (string) preg_replace('/\D+/', '', $value);
}

function slice008_assert_root_records_match_payload(Tenant $tenant, array $payload): void
{
    $tenant->refresh();
    $documentNumber = slice008_normalize_document_number((string) $payload['document_number']);
    $expectedTradeName = $payload['trade_name'] === ''
        ? null
        : (string) $payload['trade_name'];
    $expectedBranchName = $expectedTradeName ?? (string) $payload['legal_name'];

    expect($tenant->legal_name)->toBe($payload['legal_name']);
    expect($tenant->document_number)->toBe($documentNumber);
    expect($tenant->trade_name)->toBe($expectedTradeName);
    expect($tenant->main_email)->toBe(mb_strtolower((string) $payload['main_email']));
    expect($tenant->phone)->toBe($payload['phone']);
    expect($tenant->operational_profile)->toBe($payload['operational_profile']);
    expect($tenant->emits_metrological_certificate)->toBe((bool) $payload['emits_metrological_certificate']);

    $company = DB::table('companies')
        ->where('tenant_id', $tenant->id)
        ->where('is_root', true)
        ->first();

    expect($company)->not->toBeNull();
    expect($company->tenant_id)->toBe($tenant->id);
    expect($company->legal_name)->toBe($payload['legal_name']);
    expect($company->document_number)->toBe($documentNumber);
    expect($company->trade_name)->toBe($expectedTradeName);
    expect((bool) $company->is_root)->toBeTrue();

    $branch = DB::table('branches')
        ->where('tenant_id', $tenant->id)
        ->where('is_root', true)
        ->first();

    expect($branch)->not->toBeNull();
    expect($branch->tenant_id)->toBe($tenant->id);
    expect($branch->company_id)->toBe($company->id);
    expect($branch->name)->toBe($expectedBranchName);
    expect($branch->document_number)->toBe($documentNumber);
    expect((bool) $branch->is_root)->toBeTrue();
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
        $secret = (string) $secret;

        if ($secret === '') {
            continue;
        }

        expect($body)->not->toContain($secret);
    }
}
