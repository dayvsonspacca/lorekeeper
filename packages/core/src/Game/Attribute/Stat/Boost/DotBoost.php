<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Boost;

use Lorekeeper\Core\Game\Attribute\Stat\StatDescription;
use Override;

final readonly class DotBoost extends Boost
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription('Increases damage over time, fixed when it is applied.');
    }
}
