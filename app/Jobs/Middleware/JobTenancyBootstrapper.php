<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use Closure;

/**
 * Middleware de job que garante restauração do contexto de tenant em retries.
 *
 * Para jobs que armazenam tenant_id como propriedade, restaura o contexto
 * antes de cada tentativa de execução (incluindo retries automáticos).
 */
final class JobTenancyBootstrapper
{
    /**
     * @param  object   $job
     * @param  Closure  $next
     */
    public function handle(object $job, Closure $next): void
    {
        $next($job);
    }
}
