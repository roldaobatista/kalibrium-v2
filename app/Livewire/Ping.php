<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Component;

final class Ping extends Component
{
    public int $counter = 0;

    public function increment(): void
    {
        $this->counter++;
    }

    public function render(): View
    {
        return view('livewire.ping')
            ->layout('layouts.app');
    }
}
