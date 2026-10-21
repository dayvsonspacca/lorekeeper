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
use Livewire\Component;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Models\BotRunLog;
use Lorekeeper\Apps\Desktop\Models\BotTask;

final class RunBoard extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public const array TABS = ['map', 'inventory'];

    private const int LOG_LINES = 300;

    public BotRun $run;

    public string $tab = 'map';

    public ?string $cell = null;

    public string $seen = '';

    public function mount(): void
    {
        $this->seen = $this->signature();
    }

    public function open(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, strict: true) ? $tab : 'map';
    }

    public function show(string $cell): void
    {
        $this->cell = $cell;
    }

    public function follow(): void
    {
        $this->cell = null;
    }

    public function cancelAction(): Action
    {
        return Action::make('cancel')
            ->icon(Heroicon::XMark)
            ->iconButton()
            ->color('gray')
            ->size(Size::Small)
            ->tooltip('Cancel this task')
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

                $found->cancel();
            });
    }

    public function render(): View
    {
        $this->run->refresh();
        $signature = $this->signature();
        if ($signature !== $this->seen) {
            $this->seen = $signature;
            $this->dispatch('run-changed');
        }

        $tasks = $this->run
            ->tasks()
            ->orderBy('id')
            ->get();
        $current = $this->run->currentTask() ?? $tasks
            ->filter(static fn(BotTask $task): bool => $task->started_at !== null)
            ->last();

        return view('livewire.run-board', [
            'scene' => $this->run->scene(),
            'tasks' => $tasks,
            'task' => $current instanceof BotTask ? $current : null,
            'logs' => $this->run
                ->logs()
                ->latest('id')
                ->limit(self::LOG_LINES)
                ->get()
                ->reverse()
                ->map(static fn(BotRunLog $log): array => [
                    'at' => $log->created_at->format('H:i:s'),
                    'message' => $log->message,
                    'level' => $log->level->value,
                    'task' => $log->bot_task_id,
                ])
                ->values()
                ->all(),
            'now' => now()->getTimestamp(),
        ]);
    }

    private function signature(): string
    {
        return implode('|', [
            $this->run->status->value,
            $this->run->started_at?->toIso8601String(),
            $this->run->finished_at?->toIso8601String(),
            $this->run->result,
        ]);
    }
}
