<?php

declare(strict_types=1);

use App\Support\Settings\PlanUpgradeRequestService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/TestHelpers.php';

test('AC-007: gerente solicita upgrade de modulo fora do plano e o sistema registra pedido sem executar cobranca real', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $fixture = slice009_seed_plan_fixture($context['tenant'], [
        'plan_name' => 'Starter Upgrade '.Str::uuid(),
        'feature_code' => 'fiscal',
    ]);

    app(PlanUpgradeRequestService::class)->requestUpgrade(
        $context['user'],
        $context['tenant_user'],
        $fixture['feature_code'],
        'Precisamos avaliar emissao fiscal no laboratorio.',
    );

    expect(Schema::hasTable('plan_upgrade_requests'))->toBeTrue('AC-007: pedidos de upgrade precisam ser persistidos.');
    $request = DB::table('plan_upgrade_requests')
        ->where('tenant_id', $context['tenant']->id)
        ->where('user_id', $context['user']->id)
        ->where('feature_code', $fixture['feature_code'])
        ->first();

    expect($request)->not->toBeNull();
    expect((string) $request->status)->toBe('requested');

    foreach (['invoices', 'payments', 'charges'] as $billingTable) {
        if (Schema::hasTable($billingTable)) {
            expect(DB::table($billingTable)->where('tenant_id', $context['tenant']->id)->count())->toBe(0);
        }
    }
})->group('slice-009', 'ac-007');

test('AC-007: justificativa legitima com texto tenant_id nao bloqueia pedido de upgrade', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $fixture = slice009_seed_plan_fixture($context['tenant'], [
        'plan_name' => 'Starter Upgrade '.Str::uuid(),
        'feature_code' => 'fiscal',
    ]);

    $request = app(PlanUpgradeRequestService::class)->requestUpgrade(
        $context['user'],
        $context['tenant_user'],
        $fixture['feature_code'],
        'Comparar tenant_id do sistema legado durante a migracao.',
    );

    expect($request->tenant_id)->toBe($context['tenant']->id);
    expect((string) $request->justification)->toContain('tenant_id do sistema legado');
})->group('slice-009', 'ac-007');

test('AC-007: pedido de upgrade aceita apenas modulo existente e fora do plano atual', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $fixture = slice009_seed_plan_fixture($context['tenant'], [
        'plan_name' => 'Starter Upgrade '.Str::uuid(),
        'feature_code' => 'fiscal',
    ]);

    expect(fn () => app(PlanUpgradeRequestService::class)->requestUpgrade(
        $context['user'],
        $context['tenant_user'],
        'modulo_inexistente',
        'Solicitar modulo inexistente.',
    ))->toThrow(ValidationException::class);

    $planId = DB::table('subscriptions')->where('tenant_id', $context['tenant']->id)->value('plan_id');
    $featureId = DB::table('features')->where('code', $fixture['feature_code'])->value('id');
    slice009_insert_filtered('plan_entitlements', [
        'plan_id' => $planId,
        'feature_id' => $featureId,
        'feature_code' => $fixture['feature_code'],
        'enabled' => true,
    ]);

    expect(fn () => app(PlanUpgradeRequestService::class)->requestUpgrade(
        $context['user'],
        $context['tenant_user'],
        $fixture['feature_code'],
        'Modulo ja contratado.',
    ))->toThrow(ValidationException::class);
})->group('slice-009', 'ac-007');
