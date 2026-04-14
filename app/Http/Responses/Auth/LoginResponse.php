<?php

declare(strict_types=1);

namespace App\Http\Responses\Auth;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;

final class LoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->to('/app');
    }
}
