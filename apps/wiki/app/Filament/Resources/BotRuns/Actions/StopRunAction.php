<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Models\BotRun;

final class StopRunAction
{
    public static function make(): Action
    {
        return Action::make('stop')
            ->icon(Heroicon::OutlinedStop)
            ->color('danger')
            ->requiresConfirmation()
            ->visible(static fn(BotRun $record): bool => in_array(
                $record->status,
                [RunStatus::Pending, RunStatus::Running],
                strict: true,
            ))
            ->action(static function (BotRun $record): void {
                $record->update(['status' => RunStatus::Stopping]);
            });
    }
}
