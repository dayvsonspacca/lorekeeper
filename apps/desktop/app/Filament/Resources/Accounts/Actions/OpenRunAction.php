<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\BotRunResource;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotRun;

final class OpenRunAction
{
    public static function make(): Action
    {
        return Action::make('openRun')
            ->label('Open bot')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->visible(static fn(Account $record): bool => $record->activeRun() !== null)
            ->url(static fn(Account $record): ?string => (
                ($run = $record->activeRun()) instanceof BotRun
                    ? BotRunResource::getUrl('view', ['record' => $run])
                    : null
            ));
    }
}
