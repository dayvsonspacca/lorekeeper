<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Lorekeeper\Apps\Desktop\Native\Heartbeat;

final class KeepAlive extends Component
{
    public function mount(): void
    {
        Heartbeat::beat();
    }

    public function beat(): void
    {
        Heartbeat::beat();
    }

    public function render(): View
    {
        return view('livewire.keep-alive');
    }
}
