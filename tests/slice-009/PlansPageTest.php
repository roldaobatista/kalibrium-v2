<?php

declare(strict_types=1);

use App\Support\Settings\PlanSummaryService;
use App\Support\Settings\PlanUpgradeRequestService;
use App\Support\Settings\TenantPlanMetricsReader;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once __DIR__.'/TestHelpers.php';

test('AC-006: gerente com 2FA concluido acessa /settings/plans e ve plano, status, uso, limites e modulos', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $fixture = slice009_seed_plan_fixture($context['tenant'], [
        'plan_name' => 'Starter',
        'users_used' => 8,
        'users_limit' => 10,
        'monthly_os_used' => 40,
        'monthly_os_limit' => 100,
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->get(slice009_routes()['plans']);

    $response->assertStatus(200);
    $response->assertSee($fixture['plan_name']);
    $response->assertSee('Status');
    $response->assertSee('Usuarios');
    $response->assertSee('OS no mes');
    $response->assertSee('Armazenamento');
    $response->assertSee('80%');
    $response->assertSee('Modulo Fiscal');
})->group('slice-009', 'ac-006');

test('AC-006: uso de usuarios em /settings/plans reflete vinculos ativos sem gravar metricas', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    slice009_create_tenant_member($context, [
        'role' => 'tecnico',
        'status' => 'active',
    ]);
    slice009_seed_plan_fixture($context['tenant'], [
        'users_used' => 99,
        'users_limit' => 10,
        'monthly_os_used' => 20,
        'monthly_os_limit' => 100,
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->get(slice009_routes()['plans']);

    $response->assertStatus(200);
    $response->assertSee('2 de 10');
    $summary = app(PlanSummaryService::class)->summaryFor($context['tenant']);
    expect($summary['usage']['users'])->toBe(2);
    $metric = DB::table('tenant_plan_metrics')->where('tenant_id', $context['tenant']->id)->first();
    expect((int) $metric->users_used)->toBe(99);
})->group('slice-009', 'ac-006');

test('AC-006: snapshot de metricas calculadas nao deixa modelo persistido marcado como alterado', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    slice009_create_tenant_member($context, [
        'role' => 'tecnico',
        'status' => 'active',
    ]);
    slice009_seed_plan_fixture($context['tenant'], [
        'users_used' => 99,
        'users_limit' => 10,
    ]);

    $metric = app(TenantPlanMetricsReader::class)->snapshotForTenant($context['tenant']);

    expect($metric->users_used)->toBe(2);
    expect($metric->isDirty())->toBeFalse();
    expect((int) DB::table('tenant_plan_metrics')
        ->where('tenant_id', $context['tenant']->id)
        ->value('users_used'))->toBe(99);
})->group('slice-009', 'ac-006');

test('AC-006: resumo de plano considera liberacoes especificas do tenant', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $fixture = slice009_seed_plan_fixture($context['tenant'], [
        'users_limit' => 10,
        'feature_code' => 'fiscal',
    ]);

    $featureId = DB::table('features')->where('code', $fixture['feature_code'])->value('id');
    slice009_insert_filtered('tenant_entitlements', [
        'tenant_id' => $context['tenant']->id,
        'feature_id' => $featureId,
        'feature_code' => $fixture['feature_code'],
        'enabled' => true,
    ]);
    slice009_insert_filtered('tenant_entitlements', [
        'tenant_id' => $context['tenant']->id,
        'feature_code' => 'users',
        'limit_value' => 12,
        'enabled' => true,
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->get(slice009_routes()['plans']);

    $response->assertStatus(200);
    $response->assertSee('1 de 12');
    $summary = app(PlanSummaryService::class)->summaryFor($context['tenant']);
    expect($summary['limits']['users'])->toBe(12);
    expect($summary['modules'][0]['enabled'])->toBeTrue();
})->group('slice-009', 'ac-006');

test('AC-006: resumo de modulos consulta liberacoes em lote para varias features', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $fixture = slice009_seed_plan_fixture($context['tenant'], [
        'feature_code' => 'fiscal',
    ]);
    $planId = DB::table('subscriptions')->where('tenant_id', $context['tenant']->id)->value('plan_id');

    foreach (['estoque', 'financeiro', 'relatorios'] as $code) {
        $featureId = slice009_insert_filtered('features', [
            'code' => $code,
            'name' => 'Modulo '.$code,
            'status' => 'active',
        ], ['code']);

        slice009_insert_filtered('plan_entitlements', [
            'plan_id' => $planId,
            'feature_id' => $featureId,
            'feature_code' => $code,
            'enabled' => $code !== 'relatorios',
        ], ['plan_id']);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    $summary = app(PlanSummaryService::class)->summaryFor($context['tenant']);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $entitlementQueries = collect($queries)
        ->filter(static function (array $query): bool {
            $sql = strtolower((string) ($query['query'] ?? ''));

            return preg_match('/\bfrom\s+["`]?tenant_entitlements["`]?/i', $sql) === 1
                || preg_match('/\bfrom\s+["`]?plan_entitlements["`]?/i', $sql) === 1;
        })
        ->count();

    $enabledByCode = [];
    foreach ($summary['modules'] as $module) {
        $enabledByCode[(string) $module['code']] = (bool) $module['enabled'];
    }

    expect($summary['modules'])->toHaveCount(count($enabledByCode));
    expect(count($enabledByCode))->toBeGreaterThanOrEqual(4);
    expect($enabledByCode[$fixture['feature_code']])->toBeTrue();
    expect($enabledByCode['estoque'])->toBeTrue();
    expect($enabledByCode['financeiro'])->toBeTrue();
    expect($enabledByCode['relatorios'])->toBeFalse();
    expect($entitlementQueries)->toBeLessThanOrEqual(8);
})->group('slice-009', 'ac-006');

test('AC-SEC-001: codigo de modulo com aspas e serializado com seguranca no botao de upgrade', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    slice009_seed_plan_fixture($context['tenant'], [
        'feature_code' => "fiscal');alert(1);//",
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->get(slice009_routes()['plans']);

    $response->assertStatus(200);
    $response->assertDontSee("requestUpgrade('fiscal');alert", false);
})->group('slice-009', 'ac-sec-001');

test('AC-014: tenant suspended pode ler /settings/plans, mas pedido de upgrade e bloqueado em modo somente leitura', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'suspended',
        'role' => 'gerente',
    ]);
    slice009_seed_plan_fixture($context['tenant']);
    $before = Schema::hasTable('plan_upgrade_requests')
        ? DB::table('plan_upgrade_requests')->where('tenant_id', $context['tenant']->id)->count()
        : 0;

    $response = $this
        ->actingAs($context['user'])
        ->withSession(['tenant.access_mode' => 'read-only'])
        ->get(slice009_routes()['plans']);

    $response->assertStatus(200);

    expect(fn () => app(PlanUpgradeRequestService::class)->requestUpgrade(
        $context['user'],
        $context['tenant_user'],
        'fiscal',
        'Preciso avaliar o modulo fiscal.',
    ))->toThrow(AuthorizationException::class);

    if (Schema::hasTable('plan_upgrade_requests')) {
        expect(DB::table('plan_upgrade_requests')->where('tenant_id', $context['tenant']->id)->count())->toBe($before);
    }
})->group('slice-009', 'ac-014');

test('AC-014: leitura de /settings/plans nao atualiza metricas em tenant suspended somente leitura', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'suspended',
        'role' => 'gerente',
    ]);
    slice009_seed_plan_fixture($context['tenant']);
    $sampledAt = Carbon::parse('2026-04-14 10:00:00');

    DB::table('tenant_plan_metrics')
        ->where('tenant_id', $context['tenant']->id)
        ->update([
            'users_used' => 123,
            'monthly_os_used' => 45,
            'storage_used_bytes' => 67,
            'sampled_at' => $sampledAt,
        ]);

    $response = $this
        ->actingAs($context['user'])
        ->withSession(['tenant.access_mode' => 'read-only'])
        ->get(slice009_routes()['plans']);

    $response->assertStatus(200);
    $metric = DB::table('tenant_plan_metrics')->where('tenant_id', $context['tenant']->id)->first();
    expect((int) $metric->users_used)->toBe(123);
    expect((int) $metric->monthly_os_used)->toBe(45);
    expect((int) $metric->storage_used_bytes)->toBe(67);
    expect(Carbon::parse((string) $metric->sampled_at)->toDateTimeString())->toBe($sampledAt->toDateTimeString());
})->group('slice-009', 'ac-014');

test('AC-017: consumo acima de 80 e 95 por cento exibe alertas leve e forte em /settings/plans', function (array $fixtureOverrides, array $expectedTexts): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    slice009_seed_plan_fixture($context['tenant'], $fixtureOverrides);

    $response = $this
        ->actingAs($context['user'])
        ->get(slice009_routes()['plans']);

    $response->assertStatus(200);
    foreach ($expectedTexts as $text) {
        $response->assertSee($text);
    }
})->with([
    'alerta leve' => [
        ['users_used' => 8, 'users_limit' => 10, 'monthly_os_used' => 80, 'monthly_os_limit' => 100],
        ['80%', 'alerta leve'],
    ],
    'alerta forte' => [
        ['users_used' => 10, 'users_limit' => 10, 'monthly_os_used' => 95, 'monthly_os_limit' => 100],
        ['95%', 'alerta forte'],
    ],
])->group('slice-009', 'ac-017');

test('AC-018: usuario nao gerente autorizado ve informacoes basicas do plano sem botao de upgrade', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'administrativo',
    ]);
    slice009_seed_plan_fixture($context['tenant'], ['plan_name' => 'Starter']);

    $response = $this
        ->actingAs($context['user'])
        ->get(slice009_routes()['plans']);

    $response->assertStatus(200);
    $response->assertSee('Starter');
    $response->assertSee('Plano atual');
    $response->assertDontSee('Pedir upgrade');
    $response->assertDontSee('Solicitar upgrade');
})->group('slice-009', 'ac-018');
