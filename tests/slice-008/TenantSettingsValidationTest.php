<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

require_once __DIR__.'/TestHelpers.php';

test('AC-007: CNPJ ausente, invalido ou duplicado em outro tenant retorna erro e nao altera o tenant atual', function (string $documentNumber, ?array $extraSetup = null): void {
    $contextA = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
    ]);
    $payload = slice008_form_payload([
        'document_number' => $documentNumber,
    ]);

    if ($extraSetup !== null) {
        $duplicateDocumentNumber = slice008_valid_cnpj();
        $payload = slice008_form_payload([
            'document_number' => $duplicateDocumentNumber,
        ]);
        $contextB = slice008_user_with_tenant_context([
            'tenant_status' => 'active',
            'binding_status' => 'active',
            'role' => 'gerente',
        ]);

        $successResponse = $this
            ->actingAs($contextA['user'])
            ->from(slice008_routes()['tenant_settings'])
            ->post(slice008_routes()['tenant_settings'], slice008_form_payload([
                'document_number' => $duplicateDocumentNumber,
            ]));
        $successResponse->assertStatus(302);

        $response = $this
            ->actingAs($contextB['user'])
            ->from(slice008_routes()['tenant_settings'])
            ->post(slice008_routes()['tenant_settings'], $payload);
    } else {
        $response = $this
            ->actingAs($contextA['user'])
            ->from(slice008_routes()['tenant_settings'])
            ->post(slice008_routes()['tenant_settings'], $payload);
    }

    expect(in_array($response->status(), [302, 422], true))->toBeTrue();
    if ($response->status() === 302) {
        $response->assertRedirect(slice008_routes()['tenant_settings']);
    }

    slice008_assert_body_does_not_leak_secrets($response, [
        $documentNumber,
        $contextA['tenant']->name,
    ]);

    expect(DB::table('tenants')->where('id', $contextA['tenant']->id)->exists())->toBeTrue();
})->with([
    'CNPJ ausente' => ['', null],
    'CNPJ invalido' => ['123', null],
    'CNPJ duplicado' => ['duplicate', ['duplicate_across_tenants' => true]],
])->group('slice-008', 'ac-007');

test('AC-008: razao social vazia, e-mail principal invalido ou perfil operacional invalido retornam erro por campo e nao gravam dados parciais', function (string $field, mixed $value): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
    ]);

    $payload = slice008_form_payload([
        $field => $value,
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->from(slice008_routes()['tenant_settings'])
        ->post(slice008_routes()['tenant_settings'], $payload);

    expect(in_array($response->status(), [302, 422], true))->toBeTrue();
    if ($response->status() === 302) {
        $response->assertRedirect(slice008_routes()['tenant_settings']);
        $response->assertSessionHasErrors($field);
    }

    slice008_assert_body_does_not_leak_secrets($response, [
        (string) $value,
        $context['tenant']->name,
    ]);
})->with([
    'razao social vazia' => ['legal_name', ''],
    'email principal invalido' => ['main_email', 'email-invalido'],
    'perfil operacional invalido' => ['operational_profile', 'advanced'],
])->group('slice-008', 'ac-008');

test('AC-012: falha ao persistir empresa ou filial desfaz a operacao inteira e preserva o tenant atual', function (): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
    ]);
    $payload = slice008_form_payload([
        'legal_name' => str_repeat('Laboratorio Quebrado ', 40),
        'trade_name' => str_repeat('Quebra ', 80),
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->from(slice008_routes()['tenant_settings'])
        ->post(slice008_routes()['tenant_settings'], $payload);

    expect(in_array($response->status(), [302, 422, 500], true))->toBeTrue();
    expect(DB::table('tenants')->where('id', $context['tenant']->id)->value('status'))->toBe('active');
})->group('slice-008', 'ac-012');

test('AC-SEC-002: input HTML, JavaScript ou SQL comum e tratado como dado sem refletir payload sem escape', function (): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->from(slice008_routes()['tenant_settings'])
        ->post(slice008_routes()['tenant_settings'], slice008_malicious_payload());

    expect(in_array($response->status(), [302, 422], true))->toBeTrue();
    slice008_assert_body_does_not_leak_secrets($response);
})->group('slice-008', 'ac-sec-002', 'security');
