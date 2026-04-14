<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Auth;

use Illuminate\View\View;
use Livewire\Component;

final class ForgotPasswordPage extends Component
{
    public function render(): View
    {
        return view('livewire.pages.auth.forgot-password-page')
            ->layout('layouts.app');
    }
}
