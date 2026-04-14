<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Auth;

use App\Support\Settings\UserInvitationService;
use Illuminate\View\View;
use Livewire\Component;

final class AcceptInvitationPage extends Component
{
    public string $token = '';

    public function mount(string $token, UserInvitationService $service): void
    {
        if ($service->tenantUserForToken($token) === null) {
            abort(404, 'Convite invalido. Solicite novo convite.');
        }

        $this->token = $token;
    }

    public function render(): View
    {
        return view('livewire.pages.auth.accept-invitation-page')
            ->layout('layouts.app');
    }
}
