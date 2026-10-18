<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Lorekeeper\Apps\Desktop\Bots\TaskStatus;
use Lorekeeper\Apps\Desktop\Models\BotRun;

final class RunTasks extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public BotRun $run;

    public function cancelAction(): Action
    {
        return Action::make('cancel')
            ->icon(Heroicon::XMark)
            ->color('gray')
            ->size(Size::ExtraSmall)
            ->requiresConfirmation()
            ->modalHeading('Cancel this task?')
            ->modalDescription('The bot stops playing it and moves on to the next task.')
            ->modalSubmitActionLabel('Cancel task')
            ->modalCancelActionLabel('Keep it')
            ->action(function (array $arguments): void {
                $found = $this->run->tasks()->find($arguments['task'] ?? 0);
                if ($found === null || !$found->cancellable()) {
                    return;
                }

                $found->status === TaskStatus::Pending
                    ? $found->finish(TaskStatus::Cancelled, 'cancelled')
                    : $found->update(['status' => TaskStatus::Cancelling]);
            });
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
