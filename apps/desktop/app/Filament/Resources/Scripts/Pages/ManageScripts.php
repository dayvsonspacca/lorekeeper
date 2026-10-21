<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\Scripts\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Desktop\Filament\Resources\Scripts\Actions\ImportScriptAction;
use Lorekeeper\Apps\Desktop\Filament\Resources\Scripts\ScriptResource;
use Override;

final class ManageScripts extends ManageRecords
{
    protected static string $resource = ScriptResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ImportScriptAction::make(),
            CreateAction::make()
                ->label('New script')
                ->icon(Heroicon::OutlinedPlus)
                ->modalHeading('New script')
                ->modalSubmitActionLabel('Create')
                ->createAnother(false),
        ];
    }
}
