<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\TenantUserStatus;
use App\Http\Controllers\Controller;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class WebLoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha incorretos.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        // Verifica se o usuário tem algum vínculo ativo com algum tenant.
        // Técnico inativo não tem TenantUser com status active.
        $hasActiveBinding = TenantUser::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('status', TenantUserStatus::Active)
            ->exists();

        if (! $hasActiveBinding) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Sua conta foi desativada. Procure o gerente.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
