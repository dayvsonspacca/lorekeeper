<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\BotRunResource;
use Override;

final class ListBotRuns extends ListRecords
{
    protected static string $resource = BotRunResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
