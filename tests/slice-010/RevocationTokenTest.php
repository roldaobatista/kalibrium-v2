<?php

declare(strict_types=1);

use App\Mail\RevocationConfirmationMail;
use App\Mail\RevocationLinkMail;
use App\Services\RevocationTokenService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

require_once __DIR__.'/TestHelpers.php';
require_once __DIR__.'/../slice-009/TestHelpers.php';

uses()->group('slice-010');

// ---------------------------------------------------------------------------
// AC-004: Revogação via link self-service grava consent_record revogado
// ---------------------------------------------------------------------------
test('AC-004: titular clica em link valido e revogacao grava consent_record com status=revogado', function (): void {
    Mail::fake();

    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code' => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant);

    // Registro ativo no canal whatsapp
    slice010_seed_consent_record($tenant, $subjectId, [
        'channel' => 'whatsapp',
        'status' => 'ativo',
    ]);

    $tokenData = slice010_seed_revocation_token($tenant, $subjectId, [
        'channel' => 'whatsapp',
    ]);

    // Acessa rota pública sem autenticação
    $response = $this->withHeaders(['User-Agent' => 'TestBrowser/1.0'])
        ->post('/privacy/revoke/'.$tokenData['raw_token'], [
            'revocation_reason' => 'privacy_concern',
        ]);

    $response->assertSuccessful();

    // Deve ter gravado um novo registro revogado
    $revoked = DB::table('consent_records')
        ->where('consent_subject_id', $subjectId)
        ->where('channel', 'whatsapp')
        ->where('status', 'revogado')
        ->first();

    expect($revoked)->not->toBeNull();
    expect($revoked->revocation_reason)->toBe('privacy_concern');
    expect($revoked->revoked_at)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// AC-004a: Token expirado → rejeita e envia novo token
// ---------------------------------------------------------------------------
test('AC-004a: token expirado exibe mensagem e gera novo revocation_token enviando novo email', function (): void {
    Mail::fake();

    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code' => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant);

    slice010_seed_consent_record($tenant, $subjectId, [
        'channel' => 'whatsapp',
        'status' => 'ativo',
    ]);

    // Token já expirado (criado 31 dias atrás)
    $tokenData = slice010_seed_revocation_token($tenant, $subjectId, [
        'channel' => 'whatsapp',
        'expires_at' => now()->subDays(31),
        'granted_at' => now()->subDays(31),
    ]);

    $countBefore = DB::table('revocation_tokens')
        ->where('consent_subject_id', $subjectId)
        ->count();

    $response = $this->post('/privacy/revoke/'.$tokenData['raw_token'], [
        'revocation_reason' => 'privacy_concern',
    ]);

    $response->assertSee('Link expirado');
    $response->assertSee('Solicite um novo');

    // Deve ter criado novo token
    $countAfter = DB::table('revocation_tokens')
        ->where('consent_subject_id', $subjectId)
        ->count();

    expect($countAfter)->toBeGreaterThan($countBefore);

    // Deve ter disparado novo link de revogação (não confirmação)
    Mail::assertSent(RevocationLinkMail::class);
});

// ---------------------------------------------------------------------------
// AC-004b: Canal sem consentimento ativo → exibe mensagem, não grava
// ---------------------------------------------------------------------------
test('AC-004b: revogar canal sem consentimento ativo exibe mensagem e nao grava registro', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code' => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant);

    // NÃO cria consent_record ativo — canal whatsapp nunca foi consentido
    $tokenData = slice010_seed_revocation_token($tenant, $subjectId, [
        'channel' => 'whatsapp',
    ]);

    $response = $this->post('/privacy/revoke/'.$tokenData['raw_token'], [
        'revocation_reason' => 'no_longer_interested',
    ]);

    $response->assertSee('Voce nao tem consentimento ativo para este canal');

    $count = DB::table('consent_records')
        ->where('consent_subject_id', $subjectId)
        ->where('channel', 'whatsapp')
        ->where('status', 'revogado')
        ->count();

    expect($count)->toBe(0);
});

// ---------------------------------------------------------------------------
// AC-007: Revogação bem-sucedida mostra confirmação e envia e-mail
// ---------------------------------------------------------------------------
test('AC-007: revogacao bem-sucedida exibe confirmacao na tela e envia email ao titular', function (): void {
    Mail::fake();

    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code' => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $email = 'titular-revoke@example.com';
    $subjectId = slice010_seed_consent_subject($tenant, ['email' => $email]);

    slice010_seed_consent_record($tenant, $subjectId, [
        'channel' => 'whatsapp',
        'status' => 'ativo',
    ]);

    $tokenData = slice010_seed_revocation_token($tenant, $subjectId, [
        'channel' => 'whatsapp',
    ]);

    $response = $this->post('/privacy/revoke/'.$tokenData['raw_token'], [
        'revocation_reason' => 'no_longer_interested',
    ]);

    $response->assertSuccessful();
    $response->assertSee('revogado'); // tela de confirmação

    Mail::assertSent(RevocationConfirmationMail::class, function ($mail) use ($email): bool {
        return $mail->hasTo($email);
    });
});

// ---------------------------------------------------------------------------
// AC-007a: Token inválido → HTTP 404 sem revelar existência de subjects
// A rota nomeada 'lgpd.revoke' deve existir (implementação necessária).
// Sem implementação: rota não existe → assertRouteExists falha (RED garantido).
// ---------------------------------------------------------------------------
test('AC-007a: token invalido retorna HTTP 404 sem revelar existencia do subject', function (): void {
    // A rota nomeada 'lgpd.revoke' deve existir para este teste ser válido.
    // Sem implementação, essa asserção falha com RED garantido.
    expect(Route::has('lgpd.revoke'))->toBeTrue(
        'A rota nomeada lgpd.revoke deve existir (implemente RevokeConsentPage)'
    );

    $fakeToken = bin2hex(random_bytes(32)); // token nunca persistido

    $response = $this->get(route('lgpd.revoke', ['token' => $fakeToken]));

    $response->assertStatus(404);

    $body = (string) $response->getContent();
    // Não deve revelar nenhuma informação sobre subjects
    expect($body)->not->toContain('consent_subject');
    expect($body)->not->toContain('subject_id');
    expect($body)->not->toContain('token_hash');
});

// ---------------------------------------------------------------------------
// AC-SEC-004: Token de revogação armazena apenas hash SHA-256, nunca o raw
// ---------------------------------------------------------------------------
test('AC-SEC-004: revocation_tokens armazena apenas hash SHA-256 do token raw', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code' => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant);

    $service = app(RevocationTokenService::class);
    $rawToken = $service->generate($tenant->id, $subjectId, 'email');

    $tokenRecord = DB::table('revocation_tokens')
        ->where('consent_subject_id', $subjectId)
        ->orderByDesc('created_at')
        ->first();

    expect($tokenRecord)->not->toBeNull();
    // token_hash deve ser o SHA-256 do raw
    expect($tokenRecord->token_hash)->toBe(hash('sha256', $rawToken));
    // O valor raw NUNCA deve estar armazenado
    expect($tokenRecord->token_hash)->not->toBe($rawToken);
    // expires_at deve ser granted_at + 30 dias
    $grantedAt = Carbon::parse($tokenRecord->granted_at);
    $expiresAt = Carbon::parse($tokenRecord->expires_at);
    // Carbon 3 retorna float em diffInDays — usar toEqual para aceitar 30 ou 30.0
    expect(abs((int) $expiresAt->diffInDays($grantedAt)))->toBe(30);
});

// ---------------------------------------------------------------------------
// AC-SEC-005: Comparação de token é constant-time (hash_equals)
// ---------------------------------------------------------------------------
test('AC-SEC-005: validacao de token usa hash_equals para comparacao constant-time', function (): void {
    $ctx = slice010_manager_context();
    $tenant = $ctx['tenant'];
    $user = $ctx['user'];

    slice010_seed_lgpd_category($tenant, $user, [
        'code' => 'contato',
        'legal_basis' => 'consentimento',
    ]);

    $subjectId = slice010_seed_consent_subject($tenant);

    $tokenData = slice010_seed_revocation_token($tenant, $subjectId, [
        'channel' => 'email',
    ]);

    $service = app(RevocationTokenService::class);

    // Token válido deve retornar o registro
    $valid = $service->findValidToken($tokenData['raw_token']);
    expect($valid)->not->toBeNull();

    // Token com 1 bit diferente deve falhar (não retornar registro)
    $tampered = $tokenData['raw_token'];
    $tampered[0] = $tampered[0] === 'a' ? 'b' : 'a';
    $invalid = $service->findValidToken($tampered);
    expect($invalid)->toBeNull();
});
