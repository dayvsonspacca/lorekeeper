<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Lorekeeper\Apps\Desktop\Bots\TaskStats;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Models\BotTask;

final class RunProgress extends Component
{
    public BotRun $run;

    public function render(): View
    {
        $task = $this->run->currentTask() ?? $this->run
            ->tasks()
            ->whereNotNull('started_at')
            ->latest('id')
            ->first();

        return view('livewire.run-progress', [
            'task' => $task instanceof BotTask ? $task : null,
            'stats' => $task instanceof BotTask
                ? new TaskStats($task, $this->run->account()->value('level'), now())
                : null,
        ]);
    }
}
