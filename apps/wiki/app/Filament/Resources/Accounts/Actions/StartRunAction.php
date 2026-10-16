<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\Accounts\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\BotRunResource;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Schemas\RunForm;
use Lorekeeper\Apps\Wiki\Models\Account;

final class StartRunAction
{
    public static function make(): Action
    {
        return Action::make('startRun')
            ->label('Start run')
            ->icon(Heroicon::OutlinedPlay)
            ->color('success')
            ->modalIcon(Heroicon::OutlinedPlay)
            ->modalHeading(static fn(Account $record): string => "Start a run for {$record->name}")
            ->modalSubmitActionLabel('Start')
            ->schema(RunForm::forAccount())
            ->action(static function (Account $record, array $data, Action $action): void {
                $run = $record
                    ->runs()
                    ->create([
                        'script' => $data['script'],
                        'parameters' => $data['parameters'] ?? [],
                    ]);

                $action->redirect(BotRunResource::getUrl('view', ['record' => $run]));
            });
    }
}
