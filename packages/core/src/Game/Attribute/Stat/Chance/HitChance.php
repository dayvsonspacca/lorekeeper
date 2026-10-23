<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Chance;

use Lorekeeper\Core\Game\Attribute\Stat\StatDescription;
use Override;

final readonly class HitChance extends Chance
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription(
            'Hit chance determines how likely you are to hit a target, before any other modifiers.',
        );
    }
}
