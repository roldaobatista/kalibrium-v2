<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Support\Tenancy\TenantContext;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword;

/**
 * Repositório de tokens de reset de senha ciente de tenant.
 *
 * A tabela password_reset_tokens usa (tenant_id, email) como chave primária.
 * Este repositório injeta o tenant_id corrente em todas as operações,
 * evitando colisão entre tenants que possuem usuários com o mesmo e-mail.
 *
 * tenant_id = 0 é o sentinel para tokens criados fora de contexto de tenant
 * (ex: reset via Fortify no web). O PostgreSQL não permite NULL em colunas de PK,
 * portanto usamos 0 em vez de null.
 */
final class TenantAwarePasswordTokenRepository extends DatabaseTokenRepository
{
    /**
     * Retorna o tenant_id corrente do contexto estático.
     * Retorna 0 (sentinel) quando chamado fora de contexto de tenant.
     */
    private function currentTenantId(): int
    {
        return TenantContext::getTenantId() ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function create(CanResetPassword $user): string
    {
        $email = $user->getEmailForPasswordReset();
        $tenantId = $this->currentTenantId();

        $this->deleteExisting($user);

        $token = $this->createNewToken();

        $this->getTable()->insert($this->payloadWithTenant($email, $token, $tenantId));

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(CanResetPassword $user, mixed $token): bool
    {
        $email = $user->getEmailForPasswordReset();
        $tenantId = $this->currentTenantId();

        $record = (array) $this->getTable()
            ->where('email', $email)
            ->where('tenant_id', $tenantId)
            ->first();

        return $record &&
            ! $this->tokenExpired($record['created_at']) &&
            $this->hasher->check((string) $token, (string) $record['token']);
    }

    /**
     * {@inheritdoc}
     */
    public function recentlyCreatedToken(CanResetPassword $user): bool
    {
        $email = $user->getEmailForPasswordReset();
        $tenantId = $this->currentTenantId();

        $record = (array) $this->getTable()
            ->where('email', $email)
            ->where('tenant_id', $tenantId)
            ->first();

        return $record && $this->tokenRecentlyCreated($record['created_at']);
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteExisting(CanResetPassword $user): int
    {
        $tenantId = $this->currentTenantId();

        return $this->getTable()
            ->where('email', $user->getEmailForPasswordReset())
            ->where('tenant_id', $tenantId)
            ->delete();
    }

    /**
     * Monta o payload incluindo tenant_id.
     *
     * @return array<string, mixed>
     */
    private function payloadWithTenant(string $email, string $token, int $tenantId): array
    {
        return [
            'email' => $email,
            'tenant_id' => $tenantId,
            'token' => $this->hasher->make($token),
            'created_at' => now(),
        ];
    }
}
