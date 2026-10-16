<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Lorekeeper\Apps\Wiki\Bots\TaskStatus;
use Lorekeeper\Apps\Wiki\Models\BotRun;

final class RunTasks extends Component
{
    public BotRun $run;

    public function cancel(int $task): void
    {
        $found = $this->run->tasks()->find($task);
        if ($found === null || !$found->cancellable()) {
            return;
        }

        $found->status === TaskStatus::Pending
            ? $found->finish(TaskStatus::Cancelled, 'cancelled')
            : $found->update(['status' => TaskStatus::Cancelling]);
    }

    #[On('run-changed')]
    public function refreshTasks(): void
    {
        $this->run->refresh();
    }

    public function render(): View
    {
        return view('livewire.run-tasks', ['tasks' => $this->run
            ->tasks()
            ->orderBy('id')
            ->get()]);
    }
}
