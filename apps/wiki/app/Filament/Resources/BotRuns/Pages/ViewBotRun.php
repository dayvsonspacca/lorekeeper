<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages;

use Filament\Resources\Pages\ViewRecord;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Actions\StopRunAction;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\BotRunResource;
use Override;

final class ViewBotRun extends ViewRecord
{
    protected static string $resource = BotRunResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            StopRunAction::make(),
        ];
    }
}
