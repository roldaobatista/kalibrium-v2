<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware de job que garante restauração do contexto de tenant em retries.
 *
 * Para jobs que armazenam tenantId como propriedade, restaura o contexto
 * antes de cada tentativa de execução (incluindo retries automáticos).
 *
 * Lê tenantId da propriedade $job->tenantId (convenção dos jobs tenant-aware)
 * e o injeta em request()->attributes usando a chave current_tenant_id, que é
 * a mesma consumida por ScopesToCurrentTenant::currentTenantIdForGlobalScope().
 */
final class JobTenancyBootstrapper
{
    public function handle(object $job, Closure $next): void
    {
        /** @var string|int|null $tenantId */
        $tenantId = property_exists($job, 'tenantId') ? $job->tenantId : null;

        /** @var Request $request */
        $request = app(Request::class);

        $previousTenantId = $request->attributes->get('current_tenant_id');
        $previousTenant = $request->attributes->get('current_tenant');

        if ($tenantId !== null) {
            $request->attributes->set('current_tenant_id', $tenantId);
        }

        try {
            $next($job);
        } finally {
            // Restaura o valor anterior (ou remove se não havia) após execução/retry
            if ($previousTenantId !== null) {
                $request->attributes->set('current_tenant_id', $previousTenantId);
            } else {
                $request->attributes->remove('current_tenant_id');
            }

            if ($previousTenant !== null) {
                $request->attributes->set('current_tenant', $previousTenant);
            } else {
                $request->attributes->remove('current_tenant');
            }
        }
    }
}
