<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\BotRunResource;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Widgets\RunStats;
use Override;

final class ListBotRuns extends ListRecords
{
    protected static string $resource = BotRunResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Start run')->icon(Heroicon::OutlinedPlay)];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [RunStats::class];
    }
}
