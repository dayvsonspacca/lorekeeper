<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Resistance;

use Lorekeeper\Core\Game\Attribute\Stat\StatDescription;
use Override;

final readonly class PhysicalResistance extends Resistance
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription('Reduces incoming physical and hybrid damage.');
    }
}
