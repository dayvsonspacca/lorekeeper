<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Resistance;

use Lorekeeper\Core\Game\Attribute\Stat\StatDescription;
use Override;

final readonly class DotResistance extends Resistance
{
    protected const float CAP = 1.0;

    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription('Reduces incoming damage over time, while it ticks.');
    }
}
