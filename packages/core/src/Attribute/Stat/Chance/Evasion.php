<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Chance;

use Lorekeeper\Core\Attribute\Stat\StatDescription;
use Override;

final readonly class Evasion extends Chance
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription('Evasion chance allows you to completely avoid incoming damage.');
    }
}
