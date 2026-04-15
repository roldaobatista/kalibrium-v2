<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

require_once __DIR__.'/TestHelpers.php';
require_once __DIR__.'/../slice-009/TestHelpers.php';

uses()->group('slice-010');

// ---------------------------------------------------------------------------
// AC-008: UPDATE em consent_records é recusado pelo trigger PostgreSQL
// ---------------------------------------------------------------------------
test('AC-008: UPDATE em consent_records e recusado pelo trigger append-only', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user   = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code'        => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant);
    $recordId  = slice010_seed_consent_record($tenant, $subjectId, [
        'channel' => 'email',
        'status'  => 'ativo',
    ]);

    expect(fn () => DB::table('consent_records')
        ->where('id', $recordId)
        ->update(['status' => 'revogado'])
    )->toThrow(\Illuminate\Database\QueryException::class, 'audit append-only');
});

// ---------------------------------------------------------------------------
// AC-008: DELETE em consent_records é recusado pelo trigger PostgreSQL
// ---------------------------------------------------------------------------
test('AC-008: DELETE em consent_records e recusado pelo trigger append-only', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user   = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code'        => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant);
    $recordId  = slice010_seed_consent_record($tenant, $subjectId, [
        'channel' => 'email',
        'status'  => 'ativo',
    ]);

    expect(fn () => DB::table('consent_records')
        ->where('id', $recordId)
        ->delete()
    )->toThrow(\Illuminate\Database\QueryException::class, 'audit append-only');
});

// ---------------------------------------------------------------------------
// AC-008a: TRUNCATE em consent_records é recusado pelo trigger PostgreSQL
// ---------------------------------------------------------------------------
test('AC-008a: TRUNCATE em consent_records e recusado pelo trigger append-only', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user   = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code'        => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant);
    slice010_seed_consent_record($tenant, $subjectId, [
        'channel' => 'email',
        'status'  => 'ativo',
    ]);

    expect(fn () => DB::statement('TRUNCATE TABLE consent_records'))
        ->toThrow(\Illuminate\Database\QueryException::class, 'audit append-only');
});

// ---------------------------------------------------------------------------
// AC-008: Após falha do trigger, nenhum dado foi alterado (integridade)
// ---------------------------------------------------------------------------
test('AC-008: apos rejeicao do trigger o registro original permanece intacto', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user   = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code'        => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant);
    $recordId  = slice010_seed_consent_record($tenant, $subjectId, [
        'channel' => 'email',
        'status'  => 'ativo',
    ]);

    // Tenta UPDATE — vai falhar
    try {
        DB::table('consent_records')
            ->where('id', $recordId)
            ->update(['status' => 'revogado']);
    } catch (\Illuminate\Database\QueryException) {
        // Esperado — agora verifica que o registro não foi alterado
    }

    $record = DB::table('consent_records')->where('id', $recordId)->first();
    expect($record->status)->toBe('ativo');
});
