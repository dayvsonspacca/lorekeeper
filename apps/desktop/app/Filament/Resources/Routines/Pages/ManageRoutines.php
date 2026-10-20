<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\Routines\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Desktop\Filament\Resources\Routines\RoutineResource;
use Override;

final class ManageRoutines extends ManageRecords
{
    protected static string $resource = RoutineResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New routine')
                ->icon(Heroicon::OutlinedPlus)
                ->modalHeading('New routine')
                ->modalIcon(Heroicon::OutlinedQueueList)
                ->modalSubmitActionLabel('Save')
                ->createAnother(false),
        ];
    }
}
