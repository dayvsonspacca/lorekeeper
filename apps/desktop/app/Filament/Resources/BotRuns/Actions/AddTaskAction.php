<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Schemas\TaskForm;
use Lorekeeper\Apps\Desktop\Models\BotRun;

final class AddTaskAction
{
    public static function make(): Action
    {
        return Action::make('addTask')
            ->label('Add task')
            ->icon(Heroicon::OutlinedPlus)
            ->modalIcon(Heroicon::OutlinedQueueList)
            ->modalHeading('Add tasks')
            ->modalDescription(
                'The bot plays its tasks one after the other, in this order, and waits where it is in between.',
            )
            ->modalSubmitActionLabel('Add')
            ->schema([TaskForm::routine(), TaskForm::scrolledQueue()])
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->fillForm(TaskForm::blankQueue())
            ->visible(static fn(BotRun $record): bool => $record->status->isActive())
            ->action(static function (BotRun $record, array $data): void {
                TaskForm::addTo($record, $data);
            })
            ->successNotificationTitle('Tasks added');
    }
}
