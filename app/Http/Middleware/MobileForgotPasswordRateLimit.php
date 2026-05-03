<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Throttle de 6 tentativas por hora por IP+email no endpoint de forgot password mobile.
 */
final readonly class MobileForgotPasswordRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $email = mb_strtolower((string) $request->input('email', 'guest'));
        $key = 'forgot-password:'.$request->ip().'|'.$email;

        if (RateLimiter::tooManyAttempts($key, 6)) {
            return response()->json([
                'mensagem' => 'Muitas tentativas. Aguarde alguns minutos e tente de novo.',
            ], 429);
        }

        RateLimiter::hit($key, 3600); // janela de 1 hora

        return $next($request);
    }
}
