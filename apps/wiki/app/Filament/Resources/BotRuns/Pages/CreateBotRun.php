<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages;

use Filament\Resources\Pages\CreateRecord;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\BotRunResource;

final class CreateBotRun extends CreateRecord
{
    protected static string $resource = BotRunResource::class;
}
