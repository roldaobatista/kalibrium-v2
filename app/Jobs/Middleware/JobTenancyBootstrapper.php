<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;

/**
 * Middleware de job que garante restauração do contexto de tenant em retries.
 *
 * Recebe tenantId via constructor para ser seguro em queue workers.
 * Usa TenantContext (canal estático) como fonte primária — seguro em workers
 * onde request()->attributes não carrega o ciclo HTTP original.
 * Também mantém request()->attributes para compatibilidade com o path HTTP.
 */
final class JobTenancyBootstrapper
{
    public function __construct(private readonly ?int $tenantId = null) {}

    public function handle(object $job, Closure $next): void
    {
        $previousTenantId = TenantContext::getTenantId();

        if ($this->tenantId !== null) {
            TenantContext::setTenantId($this->tenantId);
        }

        try {
            $next($job);
        } finally {
            TenantContext::setTenantId($previousTenantId);
        }
    }
}
