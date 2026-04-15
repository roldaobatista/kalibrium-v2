<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require_once __DIR__.'/TestHelpers.php';

test('AC-006: alteracao bem-sucedida em /settings/tenant grava auditoria com usuario, tenant, acao e campos alterados sem expor segredos', function (): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
    ]);
    $payload = slice008_form_payload([
        'legal_name' => 'Laboratorio Auditado '.Str::uuid(),
        'document_number' => slice008_valid_cnpj(),
        'trade_name' => 'Lab Auditado '.Str::uuid(),
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->from(slice008_routes()['tenant_settings'])
        ->post(slice008_routes()['tenant_settings'], $payload);

    expect(in_array($response->status(), [302, 200], true))->toBeTrue();

    $audit = DB::table('tenant_audit_logs')
        ->where('tenant_id', $context['tenant']->id)
        ->orderByDesc('id')
        ->first();

    expect($audit)->not->toBeNull('AC-006: auditoria deve ser registrada depois do salvamento bem-sucedido.');
    expect((int) $audit->user_id)->toBe($context['user']->id);
    expect((int) $audit->tenant_id)->toBe($context['tenant']->id);
    expect((string) $audit->action)->not->toBe('');
    expect((string) $audit->changed_fields)->not->toBe('');

    $serialized = json_encode($audit, JSON_THROW_ON_ERROR);
    slice008_assert_body_does_not_leak_secrets($response, [
        $context['password'],
        'reset',
        'totp',
        'recovery',
    ]);
    expect($serialized)->not->toContain($context['password']);
})->group('slice-008', 'ac-006');
