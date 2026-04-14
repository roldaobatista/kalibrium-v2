<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureReadOnlyTenantMode
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('tenant.access_mode') === 'read-only') {
            $request->attributes->set('tenant_read_only', true);

            if (! $request->isMethodSafe()) {
                return response()->json([
                    'message' => 'Conta em modo somente leitura.',
                ], 403);
            }
        }

        return $next($request);
    }
}
