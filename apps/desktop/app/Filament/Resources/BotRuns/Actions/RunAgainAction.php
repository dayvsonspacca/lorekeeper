<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\BotRunResource;
use Lorekeeper\Apps\Desktop\Models\BotRun;

final class RunAgainAction
{
    public static function make(): Action
    {
        return Action::make('runAgain')
            ->label('Start again')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->visible(static fn(BotRun $record): bool => !$record->status->isActive())
            ->action(static function (BotRun $record, Action $action): void {
                $action->redirect(BotRunResource::getUrl('view', ['record' => $record->account->runs()->create()]));
            });
    }
}
