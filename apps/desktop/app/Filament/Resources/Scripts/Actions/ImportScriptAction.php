<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\Scripts\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Lorekeeper\Apps\Desktop\Models\Script;
use Lorekeeper\Apps\Desktop\Scripts\FlowFile;
use Lorekeeper\Apps\Desktop\Scripts\InvalidFlowFile;
use Override;

final class ImportScriptAction extends Action
{
    #[Override]
    public static function getDefaultName(): string
    {
        return 'import';
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Import')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->modalHeading('Import a script')
            ->modalDescription('Pick a .lorebot file someone shared with you.')
            ->modalSubmitActionLabel('Import')
            ->schema([
                FileUpload::make('file')
                    ->label('Script file')
                    ->storeFiles(false)
                    ->maxSize(512)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $file = $data['file'] ?? null;
                $contents = $file instanceof TemporaryUploadedFile ? (string) $file->get() : '';

                try {
                    $script = Script::fromFlow(FlowFile::parse($contents));
                } catch (InvalidFlowFile $invalid) {
                    Notification::make()
                        ->danger()
                        ->title('This file is not a script Lorekeeper can read')
                        ->body(implode("\n", array_slice($invalid->problems, offset: 0, length: 5)))
                        ->persistent()
                        ->send();
                    $this->halt();

                    return;
                }

                $script->save();
                Notification::make()
                    ->success()
                    ->title("Imported {$script->name}")
                    ->send();
            });
    }
}
