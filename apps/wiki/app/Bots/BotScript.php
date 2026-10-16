<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots;

use Filament\Support\Contracts\HasLabel;
use Override;

enum BotScript: string implements HasLabel
{
    case StayInMap = 'stay-in-map';

    #[Override]
    public function getLabel(): string
    {
        return match ($this) { self::StayInMap => 'Stay in map' };
    }
}
