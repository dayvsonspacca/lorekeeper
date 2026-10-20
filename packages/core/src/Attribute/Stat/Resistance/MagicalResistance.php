<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Resistance;

use Lorekeeper\Core\Attribute\Stat\StatDescription;
use Override;

final readonly class MagicalResistance extends Resistance
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription('Reduces incoming magical damage.');
    }
}
