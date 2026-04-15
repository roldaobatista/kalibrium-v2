<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RevocationToken;

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
     * Comparação ocorre via WHERE no banco (constant-time pelo índice).
     * hash_equals seria defesa em profundidade se a comparação fosse string-a-string em PHP.
     */
    public function findValidToken(string $rawToken): ?RevocationToken
    {
        $hash = hash('sha256', $rawToken);

        return RevocationToken::withoutGlobalScopes()
            ->where('token_hash', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
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
     * Gera novo token (para AC-004a: token expirado).
     * Retorna o novo raw token.
     */
    public function regenerate(int $tenantId, int $subjectId, string $channel): string
    {
        return $this->generate($tenantId, $subjectId, $channel);
    }
}
