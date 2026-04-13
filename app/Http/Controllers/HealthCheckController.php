<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

final class HealthCheckController
{
    private const DETAIL_TRUSTED_IPS = ['127.0.0.1', '::1'];

    public function __invoke(Request $request): JsonResponse
    {
        $db = 'disconnected';
        $redis = 'disconnected';

        try {
            DB::select('SELECT 1');
            $db = 'connected';
        } catch (\Exception) {
            // falha capturada
        }

        try {
            Redis::ping();
            $redis = 'connected';
        } catch (\Exception) {
            // falha capturada
        }

        $status = ($db === 'connected' && $redis === 'connected') ? 'ok' : 'degraded';
        $httpCode = $status === 'ok' ? 200 : 503;

        $payload = [
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($this->shouldExposeDetails($request)) {
            $payload['db'] = $db;
            $payload['redis'] = $redis;
        }

        return response()->json($payload, $httpCode);
    }

    private function shouldExposeDetails(Request $request): bool
    {
        $ip = $request->ip();

        return $ip !== null && in_array($ip, self::DETAIL_TRUSTED_IPS, true);
    }
}
