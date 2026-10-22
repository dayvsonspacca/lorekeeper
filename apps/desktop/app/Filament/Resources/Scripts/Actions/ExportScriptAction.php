<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\Scripts\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Desktop\Models\Script;
use Lorekeeper\Apps\Desktop\Native\Downloads;
use Lorekeeper\Apps\Desktop\Scripts\FlowFile;
use Override;

final class ExportScriptAction extends Action
{
    #[Override]
    public static function getDefaultName(): string
    {
        return 'export';
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Export')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->action(static function (Script $record): void {
                $path = Downloads::save($record->name, $record->export(), FlowFile::EXTENSION);

                Notification::make()
                    ->success()
                    ->title("Exported {$record->name}")
                    ->body("Saved to {$path}")
                    ->send();
            });
    }
}
