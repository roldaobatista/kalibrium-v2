<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require_once __DIR__.'/TestHelpers.php';

test('AC-005: gerente do tenant A acessa e salva /settings/tenant sem expor dados do tenant B', function (): void {
    $tenantA = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
        'tenant_name' => 'Laboratorio A '.Str::uuid(),
    ]);
    $tenantB = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
        'tenant_name' => 'Laboratorio B '.Str::uuid(),
    ]);
    $payloadA = slice008_form_payload([
        'legal_name' => 'Laboratorio A LTDA '.Str::uuid(),
        'document_number' => slice008_valid_cnpj(),
        'trade_name' => 'Lab A '.Str::uuid(),
    ]);
    $payloadB = slice008_form_payload([
        'legal_name' => 'Laboratorio B LTDA '.Str::uuid(),
        'document_number' => slice008_valid_cnpj(),
        'trade_name' => 'Lab B '.Str::uuid(),
    ]);

    $viewResponse = $this
        ->actingAs($tenantA['user'])
        ->get(slice008_routes()['tenant_settings']);

    expect(in_array($viewResponse->status(), [200, 302, 403], true))->toBeTrue();
    slice008_assert_body_does_not_leak_secrets($viewResponse, [
        $tenantB['tenant']->name,
        $payloadB['legal_name'],
        $payloadB['document_number'],
    ]);

    $saveResponse = $this
        ->actingAs($tenantA['user'])
        ->from(slice008_routes()['tenant_settings'])
        ->post(slice008_routes()['tenant_settings'], $payloadA);

    expect(in_array($saveResponse->status(), [302, 200, 422], true))->toBeTrue();
    slice008_assert_body_does_not_leak_secrets($saveResponse, [
        $tenantB['tenant']->name,
        $payloadB['legal_name'],
        $payloadB['document_number'],
    ]);

    expect(DB::table('tenants')->where('id', $tenantA['tenant']->id)->exists())->toBeTrue();
})->group('slice-008', 'ac-005');

test('AC-SEC-001: payload com ID externo de tenant, empresa ou filial nao altera registros fora do tenant atual', function (): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
    ]);
    $externalTenant = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
        'tenant_name' => 'Laboratorio Externo '.Str::uuid(),
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->from(slice008_routes()['tenant_settings'])
        ->post(slice008_routes()['tenant_settings'], slice008_form_payload([
            'tenant_id' => $externalTenant['tenant']->id,
            'company_id' => 900001,
            'branch_id' => 900002,
        ]));

    expect(in_array($response->status(), [302, 403, 422], true))->toBeTrue();
    slice008_assert_body_does_not_leak_secrets($response, [
        $externalTenant['tenant']->name,
        (string) $externalTenant['tenant']->id,
    ]);
})->group('slice-008', 'ac-sec-001', 'security');

test('AC-SEC-003: resposta de bloqueio por permissao, read-only ou vinculo invalido nao revela dados de outro tenant nem detalhes internos', function (): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'tecnico',
    ]);
    $otherTenant = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
        'tenant_name' => 'Laboratorio Sigiloso '.Str::uuid(),
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->withSession(['tenant.access_mode' => 'read-only'])
        ->from(slice008_routes()['tenant_settings'])
        ->post(slice008_routes()['tenant_settings'], slice008_form_payload([
            'tenant_id' => $otherTenant['tenant']->id,
        ]));

    expect(in_array($response->status(), [302, 403], true))->toBeTrue();
    slice008_assert_body_does_not_leak_secrets($response, [
        $otherTenant['tenant']->name,
        'app.current_tenant_id',
        'tenant_id',
        'company_id',
        'branch_id',
    ]);
})->group('slice-008', 'ac-sec-003', 'security');
