<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Primary;

use Lorekeeper\Core\Game\Attribute\Stat\StatDescription;
use Override;

final readonly class Wisdom extends PrimaryAttribute
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription(
            'Wisdom is valuable to caster classes. It increases Hit chance, Crit chance, and Evasion chance. It improves only Evasion chance for melee classes.',
        );
    }
}
