<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejeita requests em runtime se o ambiente não for local/testing.
 * Substitui guard de boot-time em routes/web.php (SEC-002).
 */
final class RestrictToLocalEnv
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('local', 'testing')) {
            abort(404);
        }

        return $next($request);
    }
}
