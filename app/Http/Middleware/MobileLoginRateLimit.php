<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class MobileLoginRateLimit
{
    private const int MAX_ATTEMPTS = 5;

    private const int WINDOW_MINUTES = 15;

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip() ?? 'unknown';
        $key = 'mobile_login_rate_limit:'.$ip;

        $store = Cache::store((string) config('cache.default', 'redis'));
        $hits = (int) $store->get($key, 0);

        if ($hits >= self::MAX_ATTEMPTS) {
            return response()->json([
                'erro' => 'Muitas tentativas de login. Aguarde 15 minutos antes de tentar novamente.',
            ], 429);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 401) {
            $store->put($key, $hits + 1, self::WINDOW_MINUTES * 60);
        }

        return $response;
    }
}
