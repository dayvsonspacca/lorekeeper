<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Models\BotTask;

final class RunScene extends Component
{
    public BotRun $run;

    public ?string $cell = null;

    public function show(string $cell): void
    {
        $this->cell = $cell;
    }

    public function follow(): void
    {
        $this->cell = null;
    }

    #[On('run-changed')]
    public function refreshScene(): void
    {
        $this->run->refresh();
    }

    public function render(): View
    {
        $this->run->refresh();

        $task = $this->run->currentTask() ?? $this->run
            ->tasks()
            ->whereNotNull('started_at')
            ->latest('id')
            ->first();

        return view('livewire.run-scene', [
            'scene' => $this->run->scene(),
            'task' => $task instanceof BotTask ? $task : null,
            'now' => now()->getTimestamp(),
        ]);
    }
}
