<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Auth;

use Illuminate\View\View;
use Livewire\Component;

final class TwoFactorChallengePage extends Component
{
    public function render(): View
    {
        return view('livewire.pages.auth.two-factor-challenge-page')
            ->layout('layouts.app');
    }
}
