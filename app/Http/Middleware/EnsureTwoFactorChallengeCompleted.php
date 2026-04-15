<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia o prosseguimento da request quando há um challenge de 2FA iniciado
 * mas não concluído (flag de sessão `auth.two_factor_pending`).
 * Complementa RequireTwoFactorSession: este middleware trata o caso do usuário
 * que já disparou o challenge mas ainda não confirmou; o outro trata o caso do
 * usuário com 2FA habilitado que precisa confirmar para acessar rotas sensíveis.
 */
final class EnsureTwoFactorChallengeCompleted
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('auth.two_factor_pending') === true) {
            return redirect('/auth/two-factor-challenge');
        }

        return $next($request);
    }
}
