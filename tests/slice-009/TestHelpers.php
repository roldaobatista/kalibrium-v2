<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\TwoFactorAuthenticationProvider;

function slice009_routes(): array
{
    return [
        'users' => '/settings/users',
        'plans' => '/settings/plans',
        'invitation' => static fn (string $token): string => '/auth/invitations/'.$token,
    ];
}

function slice009_users_component(): string
{
    return 'App\\Livewire\\Pages\\Settings\\UsersPage';
}

function slice009_plans_component(): string
{
    return 'App\\Livewire\\Pages\\Settings\\PlansPage';
}

function slice009_unique_email(): string
{
    return 'slice009+'.Str::uuid().'@example.com';
}

function slice009_user_with_tenant_context(array $overrides = []): array
{
    $password = (string) ($overrides['password'] ?? 'SenhaSegura123!');
    $role = (string) ($overrides['role'] ?? 'gerente');
    $tenantStatus = (string) ($overrides['tenant_status'] ?? 'active');
    $bindingStatus = (string) ($overrides['binding_status'] ?? 'active');
    $requiresTwoFactor = (bool) ($overrides['requires_2fa'] ?? in_array($role, ['gerente', 'administrativo'], true));
    $twoFactorConfirmed = (bool) ($overrides['two_factor_confirmed'] ?? $requiresTwoFactor);
    $twoFactorSecret = $requiresTwoFactor
        ? app(TwoFactorAuthenticationProvider::class)->generateSecretKey()
        : null;

    $user = User::factory()->create([
        'name' => $overrides['user_name'] ?? 'Usuario '.Str::uuid(),
        'email' => $overrides['email'] ?? slice009_unique_email(),
        'password' => Hash::make($password),
        'two_factor_secret' => $twoFactorSecret === null ? null : encrypt($twoFactorSecret),
        'two_factor_recovery_codes' => $requiresTwoFactor ? [Hash::make('recovery-code-1')] : [],
        'two_factor_confirmed_at' => $twoFactorConfirmed ? now() : null,
    ]);

    $tenant = Tenant::factory()->create([
        'name' => $overrides['tenant_name'] ?? 'Laboratorio '.Str::uuid(),
        'status' => $tenantStatus,
    ]);

    $company = Company::factory()->create([
        'tenant_id' => $tenant->id,
        'legal_name' => $overrides['company_name'] ?? 'Empresa '.Str::uuid(),
        'is_root' => true,
    ]);

    $branch = Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'company_id' => $company->id,
        'name' => $overrides['branch_name'] ?? 'Filial '.Str::uuid(),
        'is_root' => true,
    ]);

    $tenantUser = TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => $bindingStatus,
        'requires_2fa' => $requiresTwoFactor,
    ]);

    slice009_update_tenant_user_optional_columns($tenantUser->id, [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
    ]);

    return [
        'user' => $user,
        'tenant' => $tenant,
        'tenant_user' => $tenantUser->fresh(),
        'company' => $company,
        'branch' => $branch,
        'password' => $password,
        'two_factor_secret' => $twoFactorSecret,
    ];
}

function slice009_create_tenant_member(array $context, array $overrides = []): array
{
    $role = (string) ($overrides['role'] ?? 'tecnico');
    $requiresTwoFactor = (bool) ($overrides['requires_2fa'] ?? in_array($role, ['gerente', 'administrativo'], true));
    $user = User::factory()->create([
        'name' => $overrides['user_name'] ?? 'Membro '.Str::uuid(),
        'email' => $overrides['email'] ?? slice009_unique_email(),
        'password' => Hash::make((string) ($overrides['password'] ?? 'SenhaSegura123!')),
        'two_factor_secret' => $requiresTwoFactor
            ? encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey())
            : null,
        'two_factor_recovery_codes' => $requiresTwoFactor ? [Hash::make('recovery-code-1')] : [],
        'two_factor_confirmed_at' => $requiresTwoFactor ? now() : null,
    ]);

    $tenantUser = TenantUser::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => $overrides['status'] ?? 'active',
        'requires_2fa' => $requiresTwoFactor,
    ]);

    slice009_update_tenant_user_optional_columns($tenantUser->id, [
        'company_id' => $context['company']->id,
        'branch_id' => $context['branch']->id,
        'invited_at' => $overrides['invited_at'] ?? null,
        'accepted_at' => $overrides['accepted_at'] ?? null,
    ]);

    return [
        'user' => $user,
        'tenant_user' => $tenantUser->fresh(),
    ];
}

function slice009_update_tenant_user_optional_columns(int $tenantUserId, array $values): void
{
    if (! Schema::hasTable('tenant_users')) {
        return;
    }

    $columns = array_flip(Schema::getColumnListing('tenant_users'));
    $updates = array_intersect_key($values, $columns);

    if ($updates === []) {
        return;
    }

    DB::table('tenant_users')->where('id', $tenantUserId)->update($updates);
}

function slice009_invite_payload(array $context, array $overrides = []): array
{
    return array_merge([
        'name' => 'Convidado '.Str::uuid(),
        'email' => slice009_unique_email(),
        'role' => 'tecnico',
        'company_id' => $context['company']->id,
        'branch_id' => $context['branch']->id,
    ], $overrides);
}

function slice009_invitation_context(array $overrides = []): array
{
    $context = slice009_user_with_tenant_context([
        'role' => 'gerente',
        'tenant_status' => $overrides['tenant_status'] ?? 'active',
    ]);
    $member = slice009_create_tenant_member($context, [
        'role' => $overrides['role'] ?? 'tecnico',
        'status' => $overrides['status'] ?? 'invited',
        'email' => $overrides['email'] ?? slice009_unique_email(),
    ]);
    $token = (string) ($overrides['token'] ?? Str::random(64));

    slice009_update_tenant_user_optional_columns($member['tenant_user']->id, [
        'invited_at' => $overrides['invited_at'] ?? now(),
        'accepted_at' => $overrides['accepted_at'] ?? null,
        'invitation_token_hash' => hash('sha256', $token),
        'invitation_expires_at' => $overrides['invitation_expires_at'] ?? now()->addDay(),
    ]);

    return array_merge($context, [
        'invited_user' => $member['user'],
        'invited_tenant_user' => $member['tenant_user']->fresh(),
        'token' => $token,
    ]);
}

function slice009_accept_payload(array $overrides = []): array
{
    return array_merge([
        'password' => 'NovaSenhaSegura123!',
        'password_confirmation' => 'NovaSenhaSegura123!',
    ], $overrides);
}

function slice009_malicious_payload(array $context, array $overrides = []): array
{
    return slice009_invite_payload($context, array_merge([
        'name' => '<script>alert(1)</script>',
        'email' => 'invalido<script>@example.com',
        'role' => 'gerente',
        'search' => "' OR 1=1 --",
        'justification' => '<img src=x onerror=alert(1)> DROP TABLE users;',
        'tenant_id' => 999999,
        'company_id' => 888888,
        'branch_id' => 777777,
    ], $overrides));
}

function slice009_sensitive_fragments(): array
{
    return [
        'SenhaSegura123!',
        'NovaSenhaSegura123!',
        'recovery-code-1',
        'totp-secret',
        'invitation-token',
        '<script>alert(1)</script>',
        '<img src=x onerror=alert(1)>',
        'DROP TABLE users',
        'app.current_tenant_id',
    ];
}

function slice009_assert_body_does_not_leak(TestResponse $response, array $secrets = []): void
{
    $body = (string) $response->getContent();

    foreach ($secrets === [] ? slice009_sensitive_fragments() : $secrets as $secret) {
        $secret = (string) $secret;

        if ($secret === '') {
            continue;
        }

        expect($body)->not->toContain($secret);
    }
}

function slice009_audit_payload(int $tenantId): string
{
    if (! Schema::hasTable('tenant_audit_logs')) {
        return '';
    }

    return json_encode(
        DB::table('tenant_audit_logs')->where('tenant_id', $tenantId)->get()->all(),
        JSON_THROW_ON_ERROR,
    );
}

function slice009_assert_audit_does_not_leak(int $tenantId, array $secrets = []): void
{
    $payload = slice009_audit_payload($tenantId);

    foreach ($secrets === [] ? slice009_sensitive_fragments() : $secrets as $secret) {
        $secret = (string) $secret;

        if ($secret === '') {
            continue;
        }

        expect($payload)->not->toContain($secret);
    }
}

function slice009_insert_filtered(string $table, array $values, array $requiredColumns = []): ?int
{
    if (! Schema::hasTable($table)) {
        return null;
    }

    $columns = array_flip(Schema::getColumnListing($table));

    foreach ($requiredColumns as $column) {
        if (! array_key_exists($column, $columns)) {
            return null;
        }
    }

    $now = now();
    if (array_key_exists('created_at', $columns) && ! array_key_exists('created_at', $values)) {
        $values['created_at'] = $now;
    }
    if (array_key_exists('updated_at', $columns) && ! array_key_exists('updated_at', $values)) {
        $values['updated_at'] = $now;
    }

    $filtered = array_intersect_key($values, $columns);

    if ($filtered === []) {
        return null;
    }

    if (array_key_exists('code', $filtered)) {
        $existingId = DB::table($table)->where('code', $filtered['code'])->value('id');
        if ($existingId !== null) {
            return (int) $existingId;
        }
    }

    if ($table === 'tenant_plan_metrics' && array_key_exists('tenant_id', $filtered)) {
        $existingId = DB::table($table)->where('tenant_id', $filtered['tenant_id'])->value('id');
        if ($existingId !== null) {
            DB::table($table)->where('id', $existingId)->update($filtered);

            return (int) $existingId;
        }
    }

    return (int) DB::table($table)->insertGetId($filtered);
}

function slice009_seed_plan_fixture(Tenant $tenant, array $overrides = []): array
{
    $planName = (string) ($overrides['plan_name'] ?? 'Starter');
    $featureCode = (string) ($overrides['feature_code'] ?? 'fiscal');
    $usersLimit = (int) ($overrides['users_limit'] ?? 10);
    $usersUsed = (int) ($overrides['users_used'] ?? 8);
    $monthlyOsLimit = (int) ($overrides['monthly_os_limit'] ?? 100);
    $monthlyOsUsed = (int) ($overrides['monthly_os_used'] ?? 95);
    $storageLimitBytes = (int) ($overrides['storage_limit_bytes'] ?? 10737418240);
    $storageUsedBytes = (int) ($overrides['storage_used_bytes'] ?? 8589934592);

    $planId = slice009_insert_filtered('plans', [
        'name' => $planName,
        'code' => Str::slug($planName),
        'status' => 'active',
    ], ['name']);

    slice009_insert_filtered('subscriptions', [
        'tenant_id' => $tenant->id,
        'plan_id' => $planId,
        'status' => $overrides['subscription_status'] ?? 'active',
        'trial_ends_on' => Carbon::now()->addDays(10)->toDateString(),
        'current_period_ends_on' => Carbon::now()->addMonth()->toDateString(),
    ], ['tenant_id']);

    $featureId = slice009_insert_filtered('features', [
        'code' => $featureCode,
        'name' => 'Modulo Fiscal',
        'status' => 'inactive',
    ], ['code']);

    slice009_insert_filtered('plan_entitlements', [
        'plan_id' => $planId,
        'feature_id' => $featureId,
        'feature_code' => 'users',
        'limit_key' => 'users',
        'limit_value' => $usersLimit,
        'enabled' => true,
    ]);
    slice009_insert_filtered('plan_entitlements', [
        'plan_id' => $planId,
        'feature_id' => $featureId,
        'feature_code' => 'monthly_os',
        'limit_key' => 'monthly_os',
        'limit_value' => $monthlyOsLimit,
        'enabled' => true,
    ]);
    slice009_insert_filtered('tenant_plan_metrics', [
        'tenant_id' => $tenant->id,
        'users_used' => $usersUsed,
        'monthly_os_used' => $monthlyOsUsed,
        'storage_used_bytes' => $storageUsedBytes,
        'sampled_at' => now(),
    ], ['tenant_id']);

    return [
        'plan_name' => $planName,
        'feature_code' => $featureCode,
        'users_limit' => $usersLimit,
        'users_used' => $usersUsed,
        'monthly_os_limit' => $monthlyOsLimit,
        'monthly_os_used' => $monthlyOsUsed,
        'storage_limit_bytes' => $storageLimitBytes,
        'storage_used_bytes' => $storageUsedBytes,
    ];
}
