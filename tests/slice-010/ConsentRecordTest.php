<?php

declare(strict_types=1);

use App\Models\ConsentSubject;
use App\Services\ConsentRecordService;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/TestHelpers.php';
require_once __DIR__.'/../slice-009/TestHelpers.php';

uses()->group('slice-010');

// ---------------------------------------------------------------------------
// AC-003: Opt-in explícito grava consent_record com campos obrigatórios
// ---------------------------------------------------------------------------
test('AC-003: opt-in explicito grava consent_records com channel, status=ativo, ip e user_agent_hash SHA-256', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user   = $ctx['user'];

    // Pré-condição: base legal "consentimento" registrada
    slice010_seed_lgpd_category($tenant, $user, [
        'code'        => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant, [
        'email' => 'titular@example.com',
    ]);

    $rawUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
    $expectedHash = hash('sha256', $rawUserAgent);

    $service = app(ConsentRecordService::class);
    $service->grantConsent($tenant->id, $subjectId, [
        'channel'      => 'email',
        'ip_address'   => '192.168.1.1',
        'user_agent'   => $rawUserAgent,
    ]);

    $record = DB::table('consent_records')
        ->where('consent_subject_id', $subjectId)
        ->where('channel', 'email')
        ->where('status', 'ativo')
        ->first();

    expect($record)->not->toBeNull();
    expect($record->ip_address)->toBe('192.168.1.1');
    expect($record->user_agent_hash)->toBe($expectedHash);
    // Nunca armazena o user agent raw
    expect($record->user_agent_hash)->not->toContain('Mozilla');
    expect($record->granted_at)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// AC-003a: Checkbox não marcado → não grava consent_record
// ---------------------------------------------------------------------------
test('AC-003a: checkbox nao marcado nao grava consent_record e estado fica nao_informado', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user   = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code'        => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant);

    $service = app(ConsentRecordService::class);
    // opted_in = false → não deve gravar
    $service->handleOptIn($tenant->id, $subjectId, [
        'channel'  => 'email',
        'opted_in' => false,
    ]);

    $count = DB::table('consent_records')
        ->where('consent_subject_id', $subjectId)
        ->where('channel', 'email')
        ->count();

    expect($count)->toBe(0);

    // canReceiveOn deve retornar false / nao_informado
    $subject = ConsentSubject::withoutGlobalScopes()->find($subjectId);
    expect($subject->canReceiveOn('email'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// AC-003b: Novo opt-in para canal já ativo → append; canReceiveOn usa o mais recente
// ---------------------------------------------------------------------------
test('AC-003b: novo opt-in em canal ja ativo cria novo registro append-only e canReceiveOn usa o mais recente', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user   = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code'        => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant);

    // Grava primeiro registro ativo
    slice010_seed_consent_record($tenant, $subjectId, [
        'channel' => 'email',
        'status'  => 'ativo',
        'created_at' => now()->subMinutes(5),
    ]);

    // Grava segundo registro (append)
    $service = app(ConsentRecordService::class);
    $service->grantConsent($tenant->id, $subjectId, [
        'channel'    => 'email',
        'ip_address' => '10.0.0.1',
        'user_agent' => 'Agent/2.0',
    ]);

    $count = DB::table('consent_records')
        ->where('consent_subject_id', $subjectId)
        ->where('channel', 'email')
        ->count();

    expect($count)->toBe(2);

    // canReceiveOn deve retornar true (mais recente = ativo)
    $subject = ConsentSubject::withoutGlobalScopes()->find($subjectId);
    expect($subject->canReceiveOn('email'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// AC-005: canReceiveOn retorna true/false baseado no registro mais recente
// ---------------------------------------------------------------------------
test('AC-005: ConsentSubject::canReceiveOn retorna true quando registro mais recente e ativo', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user   = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code'        => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant);

    // Registro ativo mais recente
    slice010_seed_consent_record($tenant, $subjectId, [
        'channel'    => 'email',
        'status'     => 'ativo',
        'created_at' => now(),
    ]);

    $subject = ConsentSubject::withoutGlobalScopes()->find($subjectId);
    expect($subject->canReceiveOn('email'))->toBeTrue();
});

test('AC-005: ConsentSubject::canReceiveOn retorna false quando registro mais recente e revogado', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user   = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code'        => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant);

    // Histórico: ativo, depois revogado
    slice010_seed_consent_record($tenant, $subjectId, [
        'channel'    => 'email',
        'status'     => 'ativo',
        'created_at' => now()->subMinutes(10),
    ]);
    slice010_seed_consent_record($tenant, $subjectId, [
        'channel'    => 'email',
        'status'     => 'revogado',
        'created_at' => now(),
    ]);

    $subject = ConsentSubject::withoutGlobalScopes()->find($subjectId);
    expect($subject->canReceiveOn('email'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// AC-SEC-001: HTML/JS em comentário de base legal é sanitizado
// ---------------------------------------------------------------------------
test('AC-SEC-001: HTML e JS em comentario de base legal sao removidos antes de persistir', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user   = $ctx['user'];

    $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

    $maliciousComment = '<script>alert(1)</script>Comentario legítimo<img src=x onerror=alert(1)>';

    $response = $this->post('/settings/privacy/lgpd-categories', [
        'code'        => 'tecnico',
        'name'        => 'Dados Tecnicos',
        'legal_basis' => 'interesse_legitimo',
        'comment'     => $maliciousComment,
    ]);

    $response->assertRedirect();

    $record = DB::table('lgpd_categories')
        ->where('tenant_id', $tenant->id)
        ->where('code', 'tecnico')
        ->first();

    expect($record)->not->toBeNull();
    expect($record->comment)->not->toContain('<script>');
    expect($record->comment)->not->toContain('<img');
    expect($record->comment)->not->toContain('onerror');
    expect($record->comment)->toContain('Comentario legítimo');
});

// ---------------------------------------------------------------------------
// AC-SEC-003: consent_records não armazena PII raw
// ---------------------------------------------------------------------------
test('AC-SEC-003: payload de consent_records nao contem PII raw — apenas subject_id UUID e enums', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user   = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code'        => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $email     = 'titular-pii@example.com';
    $subjectId = slice010_seed_consent_subject($tenant, ['email' => $email]);

    $service = app(ConsentRecordService::class);
    $service->grantConsent($tenant->id, $subjectId, [
        'channel'    => 'email',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'TestAgent/1.0',
    ]);

    // Serializa todos os campos do registro para verificar ausência de PII
    $record = DB::table('consent_records')
        ->where('consent_subject_id', $subjectId)
        ->first();

    $serialized = json_encode($record);

    expect($serialized)->not->toContain($email);
    expect($serialized)->not->toContain('titular-pii');
    expect($serialized)->not->toContain('TestAgent/1.0'); // user agent raw não deve estar
    // Deve conter apenas o UUID opaco
    expect($serialized)->toContain($subjectId);
});
