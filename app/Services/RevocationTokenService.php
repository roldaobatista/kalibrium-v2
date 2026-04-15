<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\RevocationConfirmationMail;
use App\Mail\RevocationLinkMail;
use App\Models\ConsentRecord;
use App\Models\ConsentSubject;
use App\Models\RevocationToken;
use Illuminate\Support\Facades\Mail;

final class RevocationTokenService
{
    /**
     * Gera token raw e persiste apenas o hash SHA-256.
     * Retorna o valor raw (para URL no e-mail — nunca persistido).
     */
    public function generate(int $tenantId, int $subjectId, string $channel): string
    {
        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $now = now();

        RevocationToken::create([
            'tenant_id' => $tenantId,
            'consent_subject_id' => $subjectId,
            'channel' => $channel,
            'token_hash' => $hash,
            'expires_at' => $now->copy()->addDays(30),
            'granted_at' => $now,
            'used_at' => null,
        ]);

        return $raw;
    }

    /**
     * Busca token válido pelo raw token.
     * Após o lookup, valida via hash_equals para garantir comparação constant-time
     * em profundidade (AC-SEC-005), mesmo que WHERE do banco já seja constant-time pelo índice.
     */
    public function findValidToken(string $rawToken): ?RevocationToken
    {
        $hash = hash('sha256', $rawToken);

        $token = RevocationToken::withoutGlobalScopes()
            ->where('token_hash', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($token === null) {
            return null;
        }

        return hash_equals($token->token_hash, $hash) ? $token : null;
    }

    /**
     * Busca token pelo hash (incluindo expirados, para detectar tokens expirados não usados).
     */
    public function findByRaw(string $rawToken): ?RevocationToken
    {
        $hash = hash('sha256', $rawToken);

        return RevocationToken::withoutGlobalScopes()
            ->where('token_hash', $hash)
            ->first();
    }

    /**
     * Marca token como usado.
     */
    public function consume(RevocationToken $token): void
    {
        $token->update(['used_at' => now()]);
    }

    /**
     * Lida com token expirado não usado: gera novo token e retorna o raw token.
     * O envio de e-mail fica a cargo do caller (contextos GET/POST distintos).
     *
     * @return array{rawToken: string, subject: ConsentSubject}|null
     *                                                               Retorna null se o token não tem ConsentSubject associado.
     */
    public function handleExpiredToken(RevocationToken $expiredToken): ?array
    {
        $subject = $expiredToken->consentSubject;
        if ($subject === null) {
            return null;
        }

        $rawToken = $this->generate(
            (int) $expiredToken->tenant_id,
            (int) $expiredToken->consent_subject_id,
            $expiredToken->channel
        );

        return ['rawToken' => $rawToken, 'subject' => $subject];
    }

    /**
     * Processa tentativa de revogação: retorna token válido, ou (se expirado) dispara renovação.
     * Encapsula a sequência findValidToken → findByRaw → handleExpiredToken usada nos
     * handlers GET (Livewire mount) e POST (rota).
     *
     * @return array{status: 'valid', token: RevocationToken}|array{status: 'renewed', rawToken: string, subject: ConsentSubject, channel: string}|array{status: 'not_found'}
     */
    public function processRevocationAttempt(string $rawToken): array
    {
        $valid = $this->findValidToken($rawToken);
        if ($valid !== null) {
            return ['status' => 'valid', 'token' => $valid];
        }

        $any = $this->findByRaw($rawToken);
        if ($any !== null && $any->used_at === null && $any->expires_at !== null && $any->expires_at->isPast()) {
            $renewed = $this->handleExpiredToken($any);
            if ($renewed !== null) {
                return [
                    'status' => 'renewed',
                    'rawToken' => $renewed['rawToken'],
                    'subject' => $renewed['subject'],
                    'channel' => $any->channel,
                ];
            }
        }

        return ['status' => 'not_found'];
    }

    /**
     * Envia o RevocationLinkMail correspondente a um outcome de renewed.
     * Encapsula a instanciacao + envio para eliminar duplicacao entre
     * callers (Livewire mount + controller POST).
     *
     * @param  array{status: 'renewed', rawToken: string, subject: ConsentSubject, channel: string}  $outcome
     */
    public function dispatchRenewalLink(array $outcome): void
    {
        Mail::send(new RevocationLinkMail(
            $outcome['subject'],
            $outcome['channel'],
            $outcome['rawToken']
        ));
    }

    /**
     * Finaliza a revogacao confirmada: grava o registro, consome o token
     * e envia email de confirmacao. Encapsula a sequencia usada pelos
     * dois callers (Livewire confirm + controller POST). Retorna null
     * se nao havia consentimento ativo para o canal.
     *
     * @param  array{ip_address: ?string, user_agent: string}  $request
     */
    public function finalizeRevocation(
        ConsentRecordService $consentService,
        RevocationToken $token,
        string $reason,
        array $request,
    ): ?ConsentRecord {
        $record = $consentService->revokeConsent(
            (int) $token->tenant_id,
            (int) $token->consent_subject_id,
            $token->channel,
            $reason,
            $request,
        );

        if ($record === null) {
            return null;
        }

        $this->consume($token);

        $subject = $token->consentSubject;
        if ($subject !== null && $subject->email !== null && $subject->email !== '') {
            Mail::send(new RevocationConfirmationMail($subject, $token->channel, now()));
        }

        return $record;
    }
}
