<?php

declare(strict_types=1);

use App\Support\Settings\PlanUpgradeRequestService;
use Illuminate\Auth\Access\AuthorizationException;
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
