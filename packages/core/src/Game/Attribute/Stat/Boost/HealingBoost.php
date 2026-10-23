<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Boost;

use Lorekeeper\Core\Game\Attribute\Stat\StatDescription;
use Override;

final readonly class HealingBoost extends Boost
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription('Increases the healing given to other players.');
    }
}
