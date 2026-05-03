<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantScopeBypass;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve o tenant para rotas mobile autenticadas via Sanctum.
 *
 * Após auth:sanctum validar o token, este middleware extrai o tenant_id
 * do nome do token (padrão 'mobile:tenant:{id}'), carrega o Tenant e
 * seta nos request->attributes e TenantContext (para jobs/queue).
 *
 * Deve rodar DEPOIS de auth:sanctum.
 */
final class SetMobileTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // Se o tenant já foi setado (ex: em testes via TenantContext), respeitar.
        if (TenantContext::getTenantId() !== null) {
            $tenantId = TenantContext::getTenantId();
            $tenant = TenantScopeBypass::run(fn () => Tenant::find($tenantId));
            if ($tenant instanceof Tenant) {
                $request->attributes->set('current_tenant', $tenant);
            }

            return $next($request);
        }

        $bearerToken = $request->bearerToken();

        if ($bearerToken === null || $bearerToken === '') {
            return response()->json(['erro' => 'Sessão inválida. Entre de novo.'], 401);
        }

        $accessToken = PersonalAccessToken::findToken($bearerToken);

        if (! $accessToken instanceof PersonalAccessToken) {
            return response()->json(['erro' => 'Sessão inválida. Entre de novo.'], 401);
        }

        $tenantId = $this->resolveTenantId($accessToken);

        if ($tenantId === null) {
            return response()->json(['erro' => 'Sessão inválida. Entre de novo.'], 401);
        }

        $tenant = TenantScopeBypass::run(fn () => Tenant::find($tenantId));

        if (! $tenant instanceof Tenant) {
            return response()->json(['erro' => 'Laboratório não encontrado.'], 401);
        }

        $request->attributes->set('current_tenant', $tenant);
        TenantContext::setTenantId($tenantId);

        return $next($request);
    }

    private function resolveTenantId(PersonalAccessToken $token): ?int
    {
        $name = (string) $token->name;

        if (preg_match('/^mobile:tenant:(\d+)$/', $name, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
