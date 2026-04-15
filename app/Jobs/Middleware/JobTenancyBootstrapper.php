<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use Closure;

/**
 * Middleware de job que garante restauração do contexto de tenant em retries.
 *
 * Recebe tenantId via constructor para ser seguro em queue workers, onde
 * app(Request::class) não é singleton confiável. Injeta o tenant_id em
 * request()->attributes usando a chave current_tenant_id, que é a mesma
 * consumida por ScopesToCurrentTenant::currentTenantIdForGlobalScope().
 */
final class JobTenancyBootstrapper
{
    public function __construct(private readonly ?int $tenantId = null) {}

    public function handle(object $job, Closure $next): void
    {
        $request = request();

        $previousTenantId = $request->attributes->get('current_tenant_id');
        $previousTenant = $request->attributes->get('current_tenant');

        if ($this->tenantId !== null) {
            $request->attributes->set('current_tenant_id', $this->tenantId);
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
