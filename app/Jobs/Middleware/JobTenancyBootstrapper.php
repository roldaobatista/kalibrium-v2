<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware de job que garante restauração do contexto de tenant em retries.
 *
 * Para jobs que armazenam tenant_id como propriedade, restaura o contexto
 * antes de cada tentativa de execução (incluindo retries automáticos).
 *
 * Lê tenant_id da propriedade $job->tenantId (convenção dos jobs tenant-aware)
 * e o injeta em request()->attributes para que o contexto de tenant seja
 * idêntico ao do request HTTP original — mesmo padrão do SetCurrentTenantContext.
 */
final class JobTenancyBootstrapper
{
    public function handle(object $job, Closure $next): void
    {
        /** @var string|int|null $tenantId */
        $tenantId = property_exists($job, 'tenantId') ? $job->tenantId : null;

        /** @var Request $request */
        $request = app(Request::class);

        $previous = $request->attributes->get('tenant_id');

        if ($tenantId !== null) {
            $request->attributes->set('tenant_id', $tenantId);
        }

        try {
            $next($job);
        } finally {
            // Restaura o valor anterior (ou remove se não havia) após execução/retry
            if ($previous !== null) {
                $request->attributes->set('tenant_id', $previous);
            } else {
                $request->attributes->remove('tenant_id');
            }
        }
    }
}
