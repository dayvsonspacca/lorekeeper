<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Pages;

use Filament\Resources\Pages\ListRecords;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\BotRunResource;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Widgets\RunStats;
use Override;

final class ListBotRuns extends ListRecords
{
    protected static string $resource = BotRunResource::class;

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [RunStats::class];
    }
}
