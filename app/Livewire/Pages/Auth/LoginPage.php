<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Auth;

use Illuminate\View\View;
use Livewire\Component;

final class LoginPage extends Component
{
    public function render(): View
    {
        return view('livewire.pages.auth.login-page')
            ->layout('layouts.app');
    }
}
