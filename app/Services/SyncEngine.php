<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SyncChange;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Str;

final class SyncEngine
{
    /**
     * Registra uma mudança na tabela sync_changes.
     *
     * @param  array<string, mixed>|null  $payloadBefore
     * @param  array<string, mixed>|null  $payloadAfter
     */
    public function recordChange(
        string $entityType,
        string $entityId,
        string $action,
        ?array $payloadBefore,
        ?array $payloadAfter,
        string $deviceId,
        ?int $userId,
    ): SyncChange {
        $tenantId = $this->resolveTenantId();

        return SyncChange::create([
            'ulid' => (string) Str::ulid(),
            'tenant_id' => $tenantId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'payload_before' => $payloadBefore,
            'payload_after' => $payloadAfter,
            'source_device_id' => $deviceId,
            'source_user_id' => $userId,
            'applied_at' => now(),
        ]);
    }

    private function resolveTenantId(): int
    {
        // Tenta via stancl/tenancy
        if (function_exists('tenancy') && tenancy()->initialized) {
            $t = tenant();
            if ($t !== null) {
                return (int) $t->id;
            }
        }

        // Tenta via contexto estático (jobs/queue)
        $id = TenantContext::getTenantId();
        if ($id !== null) {
            return $id;
        }

        // Tenta via request (web/API)
        if (app()->bound('request')) {
            $tenantId = request()->attributes->get('tenant_id');
            if ($tenantId !== null) {
                return (int) $tenantId;
            }
        }

        throw new \RuntimeException('SyncEngine: tenant_id não disponível no contexto atual.');
    }
}
