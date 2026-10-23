<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat;

use Lorekeeper\Core\Support\Ratio;
use Override;

final readonly class CriticalMultiplier extends Ratio implements Stat
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription('The multiplier applied to damage or healing dealt by a critical hit.');
    }
}
