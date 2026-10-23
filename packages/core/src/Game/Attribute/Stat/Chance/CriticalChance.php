<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Chance;

use Lorekeeper\Core\Game\Attribute\Stat\StatDescription;
use Override;

final readonly class CriticalChance extends Chance
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription(
            'Critical Strike chance increases the likelihood of dealing additional damage on any attack.',
        );
    }
}
