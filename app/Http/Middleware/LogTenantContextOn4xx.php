<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Emite log de auditoria com tenant_context em respostas 4xx.
 *
 * Garante rastreabilidade de tentativas suspeitas (SQL injection, cross-tenant)
 * sem vazar dados de outros tenants. Usado nos testes AC-016.
 */
final class LogTenantContextOn4xx
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500) {
            /** @var Tenant|null $tenant */
            $tenant = $request->attributes->get('current_tenant');

            Log::warning('tenant_context_4xx', [
                'tenant_context' => $tenant?->id,
                'status' => $response->getStatusCode(),
                'path' => $request->path(),
            ]);
        }

        return $response;
    }
}
