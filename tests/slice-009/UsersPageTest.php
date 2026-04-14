<?php

declare(strict_types=1);

use App\Models\TenantUser;
use App\Support\Settings\UserDeactivationService;
use App\Support\Settings\UserInvitationService;
use App\Support\Settings\UserRoleService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

require_once __DIR__.'/TestHelpers.php';

test('AC-001: gerente com 2FA concluido acessa /settings/users e ve usuarios, papeis, status, 2FA e filtros do tenant atual', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
        'two_factor_confirmed' => true,
        'tenant_name' => 'Laboratorio Alfa '.Str::uuid(),
    ]);
    $member = slice009_create_tenant_member($context, [
        'role' => 'tecnico',
        'user_name' => 'Tecnico Tenant Atual',
    ]);
    $otherTenant = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
        'tenant_name' => 'Laboratorio Sigiloso '.Str::uuid(),
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->get(slice009_routes()['users']);

    $response->assertStatus(200);
    $response->assertSee($context['user']->email);
    $response->assertSee($member['user']->email);
    $response->assertSee('Papel');
    $response->assertSee('Status');
    $response->assertSee('2FA');
    $response->assertSee('Buscar');
    $response->assertSee('Alterar papel');
    $response->assertSee('Remover acesso');
    $response->assertSee('gerente');
    $response->assertSee('tecnico');
    slice009_assert_body_does_not_leak($response, [
        $otherTenant['tenant']->name,
        $otherTenant['user']->email,
    ]);
})->group('slice-009', 'ac-001');

test('AC-008: usuario sem papel gerente nao acessa dados administrativos nem consegue convidar usuario', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'tecnico',
    ]);
    $member = slice009_create_tenant_member($context, [
        'role' => 'visualizador',
        'status' => 'active',
    ]);
    $initialTenantUserCount = TenantUser::query()->where('tenant_id', $context['tenant']->id)->count();

    expect(Gate::forUser($context['user'])->denies('tenant-users.manage', $context['tenant_user']))->toBeTrue();

    $response = $this
        ->actingAs($context['user'])
        ->get(slice009_routes()['users']);

    expect(in_array($response->status(), [302, 403], true))->toBeTrue();
    slice009_assert_body_does_not_leak($response, [
        'Convidar usuario',
        'Alterar papel',
        $context['tenant']->name,
    ]);

    expect(fn () => app(UserInvitationService::class)
        ->invite($context['user'], $context['tenant_user'], slice009_invite_payload($context)))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(UserRoleService::class)
        ->updateRole($context['user'], $context['tenant_user'], $member['tenant_user'], 'gerente'))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(UserDeactivationService::class)
        ->deactivate($context['user'], $context['tenant_user'], $member['tenant_user']))
        ->toThrow(AuthorizationException::class);

    $memberTenantUser = $member['tenant_user']->fresh();
    expect($memberTenantUser->role)->toBe('visualizador');
    expect($memberTenantUser->status)->toBe('active');
    expect(TenantUser::query()->where('tenant_id', $context['tenant']->id)->count())->toBe($initialTenantUserCount);
})->group('slice-009', 'ac-008');

test('AC-009: gerente com 2FA pendente e redirecionado antes de convidar, alterar papel ou desativar usuario', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
        'requires_2fa' => true,
        'two_factor_confirmed' => false,
    ]);
    $member = slice009_create_tenant_member($context, ['role' => 'tecnico']);

    $response = $this
        ->actingAs($context['user'])
        ->withSession(['auth.two_factor_pending' => true])
        ->get(slice009_routes()['users']);

    $response->assertRedirect('/auth/two-factor-challenge');

    expect(fn () => app(UserInvitationService::class)
        ->invite($context['user'], $context['tenant_user'], slice009_invite_payload($context)))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(UserRoleService::class)
        ->updateRole($context['user'], $context['tenant_user'], $member['tenant_user'], 'gerente'))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(UserDeactivationService::class)
        ->deactivate($context['user'], $context['tenant_user'], $member['tenant_user']))
        ->toThrow(AuthorizationException::class);
})->group('slice-009', 'ac-009');

test('AC-009: papel gerente exige 2FA mesmo se o vinculo estiver inconsistente', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
        'requires_2fa' => false,
        'two_factor_confirmed' => false,
    ]);

    expect(fn () => app(UserInvitationService::class)
        ->invite($context['user'], $context['tenant_user'], slice009_invite_payload($context)))
        ->toThrow(AuthorizationException::class);
})->group('slice-009', 'ac-009');

test('AC-014: tenant suspended pode ler /settings/users em modo somente leitura, mas acoes mutaveis ficam bloqueadas', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'suspended',
        'role' => 'gerente',
    ]);
    $member = slice009_create_tenant_member($context, ['role' => 'tecnico']);
    $before = TenantUser::query()->where('tenant_id', $context['tenant']->id)->count();

    $response = $this
        ->actingAs($context['user'])
        ->withSession(['tenant.access_mode' => 'read-only'])
        ->get(slice009_routes()['users']);

    $response->assertStatus(200);

    expect(fn () => app(UserInvitationService::class)
        ->invite($context['user'], $context['tenant_user'], slice009_invite_payload($context)))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(UserRoleService::class)
        ->updateRole($context['user'], $context['tenant_user'], $member['tenant_user'], 'administrativo'))
        ->toThrow(AuthorizationException::class);

    expect(TenantUser::query()->where('tenant_id', $context['tenant']->id)->count())->toBe($before);
})->group('slice-009', 'ac-014');
