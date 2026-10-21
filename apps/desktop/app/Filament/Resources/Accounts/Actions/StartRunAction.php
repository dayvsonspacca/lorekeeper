<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\BotRunResource;
use Lorekeeper\Apps\Desktop\Models\Account;

final class StartRunAction
{
    public static function make(): Action
    {
        return Action::make('startRun')
            ->label('Start run')
            ->icon(Heroicon::OutlinedPlay)
            ->color('success')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedPlay)
            ->modalHeading(static fn(Account $record): string => "Start a run for {$record->name}?")
            ->modalDescription('The bot logs in, enters the game and waits for tasks.')
            ->modalSubmitActionLabel('Start')
            ->visible(static fn(Account $record): bool => $record->activeRun() === null)
            ->action(static function (Account $record, Action $action): void {
                $action->redirect(BotRunResource::getUrl('view', ['record' => $record->start()]));
            });
    }
}
