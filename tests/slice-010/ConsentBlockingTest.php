<?php

declare(strict_types=1);

use App\Services\ConsentRecordService;

require_once __DIR__.'/TestHelpers.php';
require_once __DIR__.'/../slice-009/TestHelpers.php';

uses()->group('slice-010');

// ---------------------------------------------------------------------------
// AC-002: Tenant sem base legal bloqueia criação de consent_subject/record
// ---------------------------------------------------------------------------
test('AC-002: tenant sem base legal registrada bloqueia criacao de consent_subject com HTTP 422', function (): void {
    $ctx = slice010_manager_context();
    $user = $ctx['user'];
    $tenant = $ctx['tenant'];

    // Garante que NÃO existe nenhuma base legal para este tenant
    // (tenant recém-criado — lgpd_categories vazio)

    $this->actingAs($user)
         ->withSession(['current_tenant_id' => $tenant->id]);

    // Tenta criar consent_subject via rota/controller/service — deve retornar 422
    $response = $this->post('/settings/privacy/consentimentos', [
        'subject_type' => 'external_user',
        'email'        => slice010_unique_email(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'message' => 'Registre a base legal LGPD em Configuracoes > LGPD antes de capturar consentimentos',
    ]);
});

// ---------------------------------------------------------------------------
// AC-002: Mesmo bloqueio via ConsentRecordService diretamente
// ---------------------------------------------------------------------------
test('AC-002: ConsentRecordService lanca excecao quando tenant nao tem base legal', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];

    // ConsentRecordService ainda não existe → ClassNotFoundException (RED garantido)
    $service = app(ConsentRecordService::class);

    expect(fn () => $service->createForSubject($tenant->id, [
        'subject_type' => 'external_user',
        'email'        => slice010_unique_email(),
        'channel'      => 'email',
    ]))->toThrow(\App\Exceptions\LgpdBaseLegalAusenteException::class);
});

// ---------------------------------------------------------------------------
// AC-002a: Tenant suspenso — erro de suspensão precede erro LGPD
// ---------------------------------------------------------------------------
test('AC-002a: tenant suspenso bloqueia criacao de consent_subject antes da validacao LGPD', function (): void {
    $ctx = slice010_manager_context(['tenant_status' => 'suspended']);
    $user = $ctx['user'];
    $tenant = $ctx['tenant'];

    $this->actingAs($user)
         ->withSession(['current_tenant_id' => $tenant->id]);

    $response = $this->post('/settings/privacy/consentimentos', [
        'subject_type' => 'external_user',
        'email'        => slice010_unique_email(),
    ]);

    // Deve retornar mensagem de suspensão, NÃO a mensagem de base legal ausente
    $response->assertStatus(422);
    $body = (string) $response->getContent();
    expect($body)->toContain('suspenso');
    expect($body)->not->toContain('Registre a base legal LGPD');
});
