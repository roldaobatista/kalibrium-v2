<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\LgpdBaseLegalAusenteException;
use App\Models\ConsentRecord;
use App\Models\ConsentSubject;
use App\Models\LgpdCategory;
use App\Models\Tenant;

final class ConsentRecordService
{
    /**
     * Cria um consent_subject e grava opt-in no canal especificado.
     *
     * @param  array<string, mixed>  $data
     * @throws LgpdBaseLegalAusenteException
     * @throws \RuntimeException
     */
    public function createForSubject(int $tenantId, array $data): ConsentSubject
    {
        $tenant = Tenant::withoutGlobalScopes()->findOrFail($tenantId);
        $this->guardTenantAndLgpd($tenant);

        $subject = ConsentSubject::create([
            'tenant_id'    => $tenantId,
            'subject_type' => $data['subject_type'] ?? 'external_user',
            'subject_id'   => $data['subject_id'] ?? null,
            'email'        => isset($data['email']) ? strip_tags((string) $data['email']) : null,
            'phone'        => isset($data['phone']) ? strip_tags((string) $data['phone']) : null,
        ]);

        $channel = (string) ($data['channel'] ?? 'email');
        $this->grantConsent($tenantId, $subject->id, [
            'channel'    => $channel,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? '',
        ]);

        return $subject;
    }

    /**
     * Grava opt-in explícito para subject/canal.
     *
     * @param  array<string, mixed>  $data
     * @throws LgpdBaseLegalAusenteException
     */
    public function grantConsent(int $tenantId, int $subjectId, array $data): ConsentRecord
    {
        $tenant = Tenant::withoutGlobalScopes()->findOrFail($tenantId);
        $this->guardTenantAndLgpd($tenant);

        $channel = (string) ($data['channel'] ?? 'email');
        $rawUserAgent = (string) ($data['user_agent'] ?? '');
        $ipAddress = isset($data['ip_address']) ? (string) $data['ip_address'] : null;

        return ConsentRecord::create([
            'tenant_id'          => $tenantId,
            'consent_subject_id' => $subjectId,
            'lgpd_category_id'   => $data['lgpd_category_id'] ?? null,
            'channel'            => $channel,
            'status'             => 'ativo',
            'granted_at'         => now(),
            'revoked_at'         => null,
            'ip_address'         => $ipAddress,
            'user_agent_hash'    => hash('sha256', $rawUserAgent),
            'revocation_reason'  => null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    /**
     * Trata opt-in: se opted_in=false, não grava nada.
     *
     * @param  array<string, mixed>  $data
     */
    public function handleOptIn(int $tenantId, int $subjectId, array $data): ?ConsentRecord
    {
        $optedIn = (bool) ($data['opted_in'] ?? false);

        if (! $optedIn) {
            return null;
        }

        return $this->grantConsent($tenantId, $subjectId, $data);
    }

    /**
     * Grava revogação de consentimento.
     *
     * @param  array<string, mixed>  $data
     */
    public function revokeConsent(int $tenantId, int $subjectId, string $channel, string $reason, array $data = []): ?ConsentRecord
    {
        // Verifica se existe consentimento ativo
        $active = ConsentRecord::withoutGlobalScopes()
            ->where('consent_subject_id', $subjectId)
            ->where('channel', $channel)
            ->where('status', 'ativo')
            ->orderByDesc('created_at')
            ->first();

        if ($active === null) {
            return null; // AC-004b: sem registro ativo
        }

        $rawUserAgent = (string) ($data['user_agent'] ?? '');
        $ipAddress = isset($data['ip_address']) ? (string) $data['ip_address'] : null;

        return ConsentRecord::create([
            'tenant_id'          => $tenantId,
            'consent_subject_id' => $subjectId,
            'lgpd_category_id'   => null,
            'channel'            => $channel,
            'status'             => 'revogado',
            'granted_at'         => null,
            'revoked_at'         => now(),
            'ip_address'         => $ipAddress,
            'user_agent_hash'    => hash('sha256', $rawUserAgent),
            'revocation_reason'  => $reason,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    /**
     * Guard: verifica suspend e LGPD.
     *
     * @throws \RuntimeException
     * @throws LgpdBaseLegalAusenteException
     */
    private function guardTenantAndLgpd(Tenant $tenant): void
    {
        if ($tenant->status === 'suspended') {
            throw new \RuntimeException('Tenant suspenso. Operacao nao permitida.');
        }

        $hasLgpd = LgpdCategory::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->exists();

        if (! $hasLgpd) {
            throw new LgpdBaseLegalAusenteException;
        }
    }
}
