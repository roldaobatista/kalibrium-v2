<?php

declare(strict_types=1);

use App\Models\TenantUser;
use App\Support\Tenancy\TenantSettingsUpdater;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require_once __DIR__.'/TestHelpers.php';

test('AC-001: GET /settings/tenant exibe os campos do formulario para gerente autenticado em tenant active', function (): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->get(slice008_routes()['tenant_settings']);

    $response->assertStatus(200);
    $response->assertSee('Razão social');
    $response->assertSee('CNPJ');
    $response->assertSee('Nome fantasia');
    $response->assertSee('E-mail principal');
    $response->assertSee('Telefone');
    $response->assertSee('Perfil operacional');
    $response->assertSee('Emissão de certificado metrológico');
})->group('slice-008', 'ac-001');

test('AC-002: POST /settings/tenant atualiza o tenant atual e cria empresa e filial raiz vinculadas ao mesmo tenant', function (): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
    ]);
    $payload = slice008_form_payload();

    $response = $this
        ->actingAs($context['user'])
        ->from(slice008_routes()['tenant_settings'])
        ->post(slice008_routes()['tenant_settings'], $payload);

    $response->assertStatus(302);
    $response->assertRedirect(slice008_routes()['tenant_settings']);

    expect((int) DB::table('companies')
        ->where('tenant_id', $context['tenant']->id)
        ->where('is_root', true)
        ->count())->toBe(1);
    expect((int) DB::table('branches')
        ->where('tenant_id', $context['tenant']->id)
        ->where('is_root', true)
        ->count())->toBe(1);
    slice008_assert_root_records_match_payload($context['tenant'], $payload);
})->group('slice-008', 'ac-002');

test('AC-003: POST /settings/tenant atualiza registros existentes sem criar empresa ou filial raiz duplicadas', function (): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
    ]);
    $initialPayload = slice008_form_payload([
        'legal_name' => 'Laboratorio Inicial '.Str::uuid(),
        'trade_name' => 'Lab Inicial '.Str::uuid(),
        'document_number' => slice008_valid_cnpj(),
    ]);
    $updatedPayload = slice008_form_payload([
        'legal_name' => 'Laboratorio Atualizado '.Str::uuid(),
        'trade_name' => 'Lab Atualizado '.Str::uuid(),
        'document_number' => slice008_valid_cnpj(),
    ]);

    $firstResponse = $this
        ->actingAs($context['user'])
        ->from(slice008_routes()['tenant_settings'])
        ->post(slice008_routes()['tenant_settings'], $initialPayload);
    $firstResponse->assertStatus(302);

    $secondResponse = $this
        ->actingAs($context['user'])
        ->from(slice008_routes()['tenant_settings'])
        ->post(slice008_routes()['tenant_settings'], $updatedPayload);

    $secondResponse->assertStatus(302);
    $secondResponse->assertRedirect(slice008_routes()['tenant_settings']);

    expect((int) DB::table('companies')
        ->where('tenant_id', $context['tenant']->id)
        ->where('is_root', true)
        ->count())->toBe(1);
    expect((int) DB::table('branches')
        ->where('tenant_id', $context['tenant']->id)
        ->where('is_root', true)
        ->count())->toBe(1);
    slice008_assert_root_records_match_payload($context['tenant'], $updatedPayload);
})->group('slice-008', 'ac-003');

test('AC-004: POST /settings/tenant permite configuracao inicial em tenant trial e preserva o tenant atual', function (): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'trial',
        'binding_status' => 'active',
        'role' => 'gerente',
    ]);
    $payload = slice008_form_payload();

    $response = $this
        ->actingAs($context['user'])
        ->from(slice008_routes()['tenant_settings'])
        ->post(slice008_routes()['tenant_settings'], $payload);

    $response->assertStatus(302);
    $response->assertRedirect(slice008_routes()['tenant_settings']);
    expect(DB::table('tenants')->where('id', $context['tenant']->id)->value('status'))->toBe('trial');
    slice008_assert_root_records_match_payload($context['tenant'], $payload);
})->group('slice-008', 'ac-004');

test('AC-009: usuario sem papel gerente nao ve o formulario editavel e recebe bloqueio seguro em /settings/tenant', function (): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'tecnico',
    ]);

    $response = $this
        ->actingAs($context['user'])
        ->get(slice008_routes()['tenant_settings']);

    expect(in_array($response->status(), [302, 403], true))->toBeTrue();
    slice008_assert_body_does_not_leak_secrets($response, [
        'Razão social',
        'CNPJ',
        $context['tenant']->name,
    ]);
})->group('slice-008', 'ac-009');

test('AC-010: tenant suspended com sessao read-only pode visualizar, mas o salvamento retorna bloqueio', function (): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'suspended',
        'binding_status' => 'active',
        'role' => 'gerente',
    ]);
    $payload = slice008_form_payload();

    $viewResponse = $this
        ->actingAs($context['user'])
        ->withSession(['tenant.access_mode' => 'read-only'])
        ->get(slice008_routes()['tenant_settings']);

    $viewResponse->assertStatus(200);

    $saveResponse = $this
        ->actingAs($context['user'])
        ->withSession(['tenant.access_mode' => 'read-only'])
        ->from(slice008_routes()['tenant_settings'])
        ->post(slice008_routes()['tenant_settings'], $payload);

    $saveResponse->assertStatus(403);
})->group('slice-008', 'ac-010');

test('AC-011: se o vinculo mudar antes do salvamento, o sistema revalida e bloqueia a alteracao', function (): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
    ]);
    $otherTenant = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
        'tenant_name' => 'Laboratorio Secundario '.Str::uuid(),
    ]);
    TenantUser::factory()->create([
        'tenant_id' => $otherTenant['tenant']->id,
        'user_id' => $context['user']->id,
        'role' => 'gerente',
        'status' => 'active',
        'requires_2fa' => false,
    ]);
    $payload = slice008_form_payload();

    $response = $this
        ->actingAs($context['user'])
        ->from(slice008_routes()['tenant_settings'])
        ->post(slice008_routes()['tenant_settings'], $payload);

    expect(in_array($response->status(), [302, 403], true))->toBeTrue();
    slice008_assert_body_does_not_leak_secrets($response, [
        $otherTenant['tenant']->name,
    ]);
})->group('slice-008', 'ac-011');

test('AC-011: se o papel do vinculo mudar apos a resolucao, a transacao revalida e bloqueia a gravacao', function (): void {
    $context = slice008_user_with_tenant_context([
        'tenant_status' => 'active',
        'binding_status' => 'active',
        'role' => 'gerente',
    ]);
    $payload = slice008_form_payload();
    $staleTenantUser = $context['tenant_user'];

    TenantUser::query()
        ->whereKey($staleTenantUser->id)
        ->update(['role' => 'tecnico']);

    $request = Request::create(slice008_routes()['tenant_settings'], 'POST', $payload);

    expect(fn () => app(TenantSettingsUpdater::class)->update(
        $context['user'],
        $staleTenantUser,
        $payload,
        $request,
    ))->toThrow(AuthorizationException::class);

    expect(DB::table('tenants')->where('id', $context['tenant']->id)->value('legal_name'))->toBeNull();
    expect(DB::table('companies')->where('tenant_id', $context['tenant']->id)->count())->toBe(0);
    expect(DB::table('branches')->where('tenant_id', $context['tenant']->id)->count())->toBe(0);
    expect(DB::table('tenant_audit_logs')->where('tenant_id', $context['tenant']->id)->count())->toBe(0);
})->group('slice-008', 'ac-011');
