<?php

declare(strict_types=1);

namespace App\Http\Responses\Auth;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;

final class PasswordResetLinkSentResponse implements Responsable
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()
            ->to('/auth/forgot-password')
            ->with('status', 'Se o e-mail existir, enviaremos um link.');
    }
}
