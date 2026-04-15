<?php

declare(strict_types=1);

use App\Livewire\Pages\Settings\PlansPage;
use App\Livewire\Pages\Settings\UsersPage;
use App\Models\PlanUpgradeRequest;
use App\Models\TenantPlanMetric;
use App\Support\Settings\PlanSummaryService;
use App\Support\Settings\PlanUpgradeRequestService;
use App\Support\Settings\UserDeactivationService;
use App\Support\Settings\UserInvitationService;
use App\Support\Settings\UserRoleService;
use App\Support\Settings\UsersDirectoryQuery;
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
    expect($summary['usage']['users'])->toBe(1);
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

test('AC-013: busca por e-mail de outro tenant nao retorna usuarios do tenant atual', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    slice009_create_tenant_member($context, [
        'role' => 'tecnico',
        'user_name' => 'Usuario Atual Visivel',
    ]);
    $external = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);

    $results = app(UsersDirectoryQuery::class)->forTenant(
        (int) $context['tenant']->id,
        $external['user']->email,
    );

    expect($results)->toHaveCount(0);
})->group('slice-009', 'ac-013');

test('AC-013: pedido de upgrade ignora plano e limite de outro tenant e grava somente no tenant atual', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $external = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $featureCode = 'modulo-isolado-'.Str::lower(Str::random(8));
    slice009_seed_plan_fixture($context['tenant'], [
        'feature_code' => $featureCode,
        'users_limit' => 10,
        'monthly_os_limit' => 100,
    ]);
    slice009_seed_plan_fixture($external['tenant'], [
        'feature_code' => $featureCode,
        'users_limit' => 999,
        'monthly_os_limit' => 999,
    ]);
    $featureId = DB::table('features')->where('code', $featureCode)->value('id');
    slice009_insert_filtered('tenant_entitlements', [
        'tenant_id' => $external['tenant']->id,
        'feature_id' => $featureId,
        'feature_code' => $featureCode,
        'limit_value' => 999,
        'enabled' => true,
    ], ['tenant_id']);

    Livewire::actingAs($context['user'])
        ->test(PlansPage::class)
        ->call('requestUpgrade', $featureCode, 'Solicitar modulo do tenant atual.');

    expect(PlanUpgradeRequest::query()
        ->where('tenant_id', $context['tenant']->id)
        ->where('feature_code', $featureCode)
        ->count())->toBe(1);
    expect(PlanUpgradeRequest::query()
        ->where('tenant_id', $external['tenant']->id)
        ->where('feature_code', $featureCode)
        ->count())->toBe(0);
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

    // SEC-002: justificativa com HTML/JS e sanitizada por strip_tags e salva sem tags executaveis
    $upgradeRequest = app(PlanUpgradeRequestService::class)->requestUpgrade(
        $context['user'],
        $context['tenant_user'],
        'fiscal',
        (string) $payload['justification'],
    );
    expect($upgradeRequest->justification)->not->toContain('<img');
    expect($upgradeRequest->justification)->not->toContain('<script');
    expect($upgradeRequest->justification)->not->toContain('onerror');
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
        'Precisamos avaliar o modulo fiscal com token interno convite-privado-123.',
    );

    slice009_assert_audit_does_not_leak($context['tenant']->id, [
        'SenhaSegura123!',
        'NovaSenhaSegura123!',
        'invitation-token',
        'convite-privado-123',
        'justification',
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

test('AC-SEC-003: modelos de plano com tenant_id respeitam escopo do tenant atual', function (): void {
    $tenantA = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $tenantB = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);

    PlanUpgradeRequest::factory()->create(['tenant_id' => $tenantA['tenant']->id]);
    PlanUpgradeRequest::factory()->create(['tenant_id' => $tenantB['tenant']->id]);
    TenantPlanMetric::factory()->create(['tenant_id' => $tenantA['tenant']->id]);
    TenantPlanMetric::factory()->create(['tenant_id' => $tenantB['tenant']->id]);

    request()->attributes->set('current_tenant', $tenantA['tenant']);

    expect(PlanUpgradeRequest::query()->pluck('tenant_id')->unique()->values()->all())
        ->toBe([(int) $tenantA['tenant']->id]);
    expect(TenantPlanMetric::query()->pluck('tenant_id')->unique()->values()->all())
        ->toBe([(int) $tenantA['tenant']->id]);
})->group('slice-009', 'ac-sec-003', 'security');
