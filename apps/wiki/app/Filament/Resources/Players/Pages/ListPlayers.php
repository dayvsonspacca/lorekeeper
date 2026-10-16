<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\Players\Pages;

use Filament\Resources\Pages\ListRecords;
use Lorekeeper\Apps\Wiki\Filament\Resources\Players\PlayerResource;
use Lorekeeper\Apps\Wiki\Filament\Resources\Players\Widgets\PlayerStats;
use Override;

final class ListPlayers extends ListRecords
{
    protected static string $resource = PlayerResource::class;

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [PlayerStats::class];
    }
}
