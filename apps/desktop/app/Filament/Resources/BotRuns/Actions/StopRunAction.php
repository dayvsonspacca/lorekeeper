<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Models\BotRun;

final class StopRunAction
{
    public static function make(): Action
    {
        return Action::make('stop')
            ->icon(Heroicon::OutlinedStop)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Stop this run?')
            ->modalDescription('The bot logs out and the run ends as stopped.')
            ->visible(static fn(BotRun $record): bool => $record->status->isActive())
            ->action(static function (BotRun $record, Launcher $launcher): void {
                $record->stop($launcher);
            })
            ->successNotificationTitle('Stopping');
    }
}
