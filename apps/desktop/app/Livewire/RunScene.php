<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Lorekeeper\Apps\Desktop\Models\BotRun;

final class RunScene extends Component
{
    public BotRun $run;

    #[On('run-changed')]
    public function refreshScene(): void
    {
        $this->run->refresh();
    }

    public function render(): View
    {
        $this->run->refresh();

        return view('livewire.run-scene', ['scene' => $this->run->scene(), 'now' => now()->getTimestamp()]);
    }
}
