<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Primary;

use Lorekeeper\Core\Attribute\Stat\StatDescription;
use Override;

final readonly class Dexterity extends PrimaryAttribute
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription(
            'Dexterity is valuable to melee classes. It increases Haste, Hit chance, and Evasion chance. It increases only Evasion chance for caster classes.',
        );
    }
}
