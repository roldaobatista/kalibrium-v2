<?php

declare(strict_types=1);

use App\Livewire\Pages\Settings\UsersPage;
use App\Support\Settings\PlanSummaryService;
use App\Support\Settings\PlanUpgradeRequestService;
use App\Support\Settings\UserDeactivationService;
use App\Support\Settings\UserInvitationService;
use App\Support\Settings\UserRoleService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

require_once __DIR__.'/TestHelpers.php';

test('AC-013: parametros de outro tenant em convite, usuario ou plano sao rejeitados sem revelar dados externos', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
        'tenant_name' => 'Laboratorio Atual '.Str::uuid(),
    ]);
    $external = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
        'tenant_name' => 'Laboratorio Externo '.Str::uuid(),
    ]);
    $externalMember = slice009_create_tenant_member($external, ['role' => 'tecnico']);
    $currentPlanName = 'Plano Atual '.Str::uuid();
    $externalPlanName = 'Plano Externo '.Str::uuid();
    slice009_seed_plan_fixture($context['tenant'], [
        'plan_name' => $currentPlanName,
        'users_limit' => 10,
        'users_used' => 2,
        'monthly_os_limit' => 100,
        'monthly_os_used' => 20,
    ]);
    slice009_seed_plan_fixture($external['tenant'], [
        'plan_name' => $externalPlanName,
        'users_limit' => 999,
        'users_used' => 999,
        'monthly_os_limit' => 777,
        'monthly_os_used' => 777,
    ]);

    expect(fn () => app(UserInvitationService::class)->invite(
        $context['user'],
        $context['tenant_user'],
        slice009_invite_payload($context, [
            'company_id' => $external['company']->id,
            'branch_id' => $external['branch']->id,
        ]),
    ))->toThrow(ValidationException::class);

    $upgradeRequest = app(PlanUpgradeRequestService::class)->requestUpgrade(
        $context['user'],
        $context['tenant_user'],
        'fiscal',
        'Solicitar modulo fiscal para este laboratorio.',
    );
    expect($upgradeRequest->tenant_id)->toBe($context['tenant']->id);

    $response = $this
        ->actingAs($context['user'])
        ->get(slice009_routes()['users'].'?search='.urlencode($external['user']->email));

    expect(in_array($response->status(), [200, 403, 404], true))->toBeTrue();
    slice009_assert_body_does_not_leak($response, [
        $external['tenant']->name,
        $external['user']->email,
        (string) $external['tenant']->id,
    ]);

    $plansResponse = $this
        ->actingAs($context['user'])
        ->get(slice009_routes()['plans']);

    $plansResponse->assertStatus(200);
    $plansResponse->assertSee($currentPlanName);
    $plansResponse->assertSee('2 de 10');
    $plansResponse->assertSee('20 de 100');
    $plansResponse->assertDontSee($externalPlanName);

    $summary = app(PlanSummaryService::class)->summaryFor($context['tenant']);
    expect($summary['limits']['users'])->toBe(10);
    expect($summary['limits']['monthly_os'])->toBe(100);
    expect($summary['usage']['users'])->toBe(2);
    expect($summary['usage']['monthly_os'])->toBe(20);
})->group('slice-009', 'ac-013');

test('AC-013: tela de usuarios nao diferencia id inexistente de usuario existente em outro tenant', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $external = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $externalMember = slice009_create_tenant_member($external, ['role' => 'tecnico']);
    $unknownTenantUserId = (int) $externalMember['tenant_user']->id + 100000;

    expect(fn () => Livewire::actingAs($context['user'])
        ->test(UsersPage::class)
        ->call('updateRole', $externalMember['tenant_user']->id, 'gerente'))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($context['user'])
        ->test(UsersPage::class)
        ->call('updateRole', $unknownTenantUserId, 'gerente'))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($context['user'])
        ->test(UsersPage::class)
        ->call('deactivateUser', $externalMember['tenant_user']->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($context['user'])
        ->test(UsersPage::class)
        ->call('deactivateUser', $unknownTenantUserId))
        ->toThrow(ModelNotFoundException::class);

    $externalTenantUser = $externalMember['tenant_user']->fresh();
    expect($externalTenantUser->role)->toBe('tecnico');
    expect($externalTenantUser->status)->toBe('active');
})->group('slice-009', 'ac-013');

test('AC-SEC-001: HTML, JavaScript e SQL em nome, e-mail, busca ou justificativa sao tratados como dado e nao refletem sem escape', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $payload = slice009_malicious_payload($context);

    expect(fn () => app(UserInvitationService::class)
        ->invite($context['user'], $context['tenant_user'], $payload))->toThrow(ValidationException::class);

    $response = $this
        ->actingAs($context['user'])
        ->get(slice009_routes()['users'].'?search='.urlencode((string) $payload['search']));

    expect(in_array($response->status(), [200, 403, 404], true))->toBeTrue();
    slice009_assert_body_does_not_leak($response);

    expect(fn () => app(PlanUpgradeRequestService::class)->requestUpgrade(
        $context['user'],
        $context['tenant_user'],
        'fiscal',
        (string) $payload['justification'],
    ))->toThrow(ValidationException::class);
})->group('slice-009', 'ac-sec-001', 'security');

test('AC-SEC-002: auditorias de convite, aceite, papel, desativacao e upgrade nao gravam senha, token nem segredos', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $member = slice009_create_tenant_member($context, [
        'role' => 'tecnico',
        'status' => 'active',
    ]);
    $payload = slice009_invite_payload($context, [
        'role' => 'administrativo',
        'email' => slice009_unique_email(),
    ]);

    app(UserInvitationService::class)->invite($context['user'], $context['tenant_user'], $payload);
    app(UserRoleService::class)->updateRole($context['user'], $context['tenant_user'], $member['tenant_user'], 'administrativo');
    app(UserDeactivationService::class)->deactivate($context['user'], $context['tenant_user'], $member['tenant_user']);
    app(PlanUpgradeRequestService::class)->requestUpgrade(
        $context['user'],
        $context['tenant_user'],
        'fiscal',
        'Precisamos avaliar o modulo fiscal.',
    );

    slice009_assert_audit_does_not_leak($context['tenant']->id, [
        'SenhaSegura123!',
        'NovaSenhaSegura123!',
        'invitation-token',
        'two_factor_secret',
        'recovery-code-1',
    ]);
})->group('slice-009', 'ac-sec-002', 'security');

test('AC-SEC-003: usuarios e planos de dois tenants ficam isolados nas telas de usuarios e planos', function (): void {
    $tenantA = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
        'tenant_name' => 'Laboratorio A '.Str::uuid(),
    ]);
    $tenantB = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
        'tenant_name' => 'Laboratorio B '.Str::uuid(),
    ]);
    $memberB = slice009_create_tenant_member($tenantB, [
        'role' => 'tecnico',
        'user_name' => 'Usuario Sigiloso B',
    ]);
    slice009_seed_plan_fixture($tenantA['tenant'], ['plan_name' => 'Starter A']);
    slice009_seed_plan_fixture($tenantB['tenant'], ['plan_name' => 'Enterprise B']);

    $usersResponse = $this
        ->actingAs($tenantA['user'])
        ->get(slice009_routes()['users']);
    $plansResponse = $this
        ->actingAs($tenantA['user'])
        ->get(slice009_routes()['plans']);

    $usersResponse->assertStatus(200);
    $plansResponse->assertStatus(200);
    slice009_assert_body_does_not_leak($usersResponse, [
        $tenantB['tenant']->name,
        $tenantB['user']->email,
        $memberB['user']->email,
    ]);
    slice009_assert_body_does_not_leak($plansResponse, [
        $tenantB['tenant']->name,
        'Enterprise B',
    ]);
})->group('slice-009', 'ac-sec-003', 'security');
