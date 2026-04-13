<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class HealthCheckRateLimit
{
    private const MAX_REQUESTS = 60;

    private const WINDOW_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip() ?? 'unknown';
        $key = 'healthcheck_rate_limit:'.$ip;

        $storeName = (string) config('cache.default', 'redis');

        try {
            $store = Cache::store($storeName);
            $hits = (int) ($store->get($key, 0));

            if ($hits >= self::MAX_REQUESTS) {
                return response()->json([
                    'error' => 'Too Many Requests',
                    'message' => 'Rate limit exceeded: max '.self::MAX_REQUESTS.' requests per minute.',
                ], 429);
            }

            $store->put($key, $hits + 1, self::WINDOW_SECONDS);
        } catch (\Throwable) {
            // Healthcheck must still report dependency status if the cache store is down.
        }

        return $next($request);
    }
}
