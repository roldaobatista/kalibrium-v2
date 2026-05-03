<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantScopeBypass;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve o tenant para requisições mobile a partir do campo tenant_id no body.
 *
 * Requisições mobile não passam por sessão web nem por subdomain, então o app
 * envia o tenant_id explicitamente. O middleware verifica existência, seta o
 * tenant no request->attributes (mesmo mecanismo de ScopesToCurrentTenant) e
 * rejeita com 422 se ausente ou inválido.
 */
final readonly class ResolveMobileTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->input('tenant_id');

        if (! is_numeric($tenantId)) {
            return response()->json(['erro' => 'Campo tenant_id é obrigatório.'], 422);
        }

        $tenant = TenantScopeBypass::run(
            fn () => Tenant::find((int) $tenantId),
        );

        if (! $tenant instanceof Tenant) {
            return response()->json(['erro' => 'Laboratório não encontrado.'], 422);
        }

        $request->attributes->set('current_tenant', $tenant);

        return $next($request);
    }
}
