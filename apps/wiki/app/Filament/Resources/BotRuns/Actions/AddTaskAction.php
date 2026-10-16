<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Schemas\TaskForm;
use Lorekeeper\Apps\Wiki\Models\BotRun;

final class AddTaskAction
{
    public static function make(): Action
    {
        return Action::make('addTask')
            ->label('Add task')
            ->icon(Heroicon::OutlinedPlus)
            ->modalIcon(Heroicon::OutlinedQueueList)
            ->modalHeading('Add a task')
            ->modalDescription('The bot plays its tasks one after the other and waits where it is in between.')
            ->modalSubmitActionLabel('Add')
            ->schema(TaskForm::components())
            ->fillForm(TaskForm::blank())
            ->visible(static fn(BotRun $record): bool => $record->status->isActive())
            ->action(static function (BotRun $record, array $data): void {
                $record->tasks()->create(['script' => $data['script'], 'parameters' => $data['parameters'] ?? []]);
            })
            ->successNotificationTitle('Task added');
    }
}
