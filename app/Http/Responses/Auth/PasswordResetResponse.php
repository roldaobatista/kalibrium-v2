<?php

declare(strict_types=1);

namespace App\Http\Responses\Auth;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;

final class PasswordResetResponse implements Responsable
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()
            ->to('/auth/login')
            ->with('status', 'Senha redefinida com sucesso.');
    }
}
