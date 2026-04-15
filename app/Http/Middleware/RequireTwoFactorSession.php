<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que o usuário com 2FA configurado completou o challenge nesta sessão.
 * Usado em rotas sensíveis (ex: /settings/privacy) além do middleware padrão de 2FA.
 */
final class RequireTwoFactorSession
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->two_factor_confirmed_at !== null) {
            if (! $request->session()->get('auth.two_factor_confirmed')) {
                return redirect('/auth/two-factor-challenge');
            }
        }

        return $next($request);
    }
}
