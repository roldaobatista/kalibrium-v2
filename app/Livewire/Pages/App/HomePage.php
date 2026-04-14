<?php

declare(strict_types=1);

namespace App\Livewire\Pages\App;

use Illuminate\View\View;
use Livewire\Component;

final class HomePage extends Component
{
    public function render(): View
    {
        return view('livewire.pages.app.home-page')
            ->layout('layouts.app');
    }
}
