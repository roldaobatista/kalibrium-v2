<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

final class HealthCheckController
{
    public function __invoke(): JsonResponse
    {
        $db    = 'disconnected';
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

        $status   = ($db === 'connected' && $redis === 'connected') ? 'ok' : 'degraded';
        $httpCode = $status === 'ok' ? 200 : 503;

        return response()->json([
            'status'    => $status,
            'db'        => $db,
            'redis'     => $redis,
            'timestamp' => now()->toIso8601String(),
        ], $httpCode);
    }
}
