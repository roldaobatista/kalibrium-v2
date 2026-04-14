<?php

declare(strict_types=1);

use App\Support\Settings\PlanUpgradeRequestService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once __DIR__.'/TestHelpers.php';

test('AC-007: gerente solicita upgrade de modulo fora do plano e o sistema registra pedido sem executar cobranca real', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $fixture = slice009_seed_plan_fixture($context['tenant'], [
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
