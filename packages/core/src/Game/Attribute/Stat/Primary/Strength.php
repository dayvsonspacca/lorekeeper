<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Primary;

use Lorekeeper\Core\Game\Attribute\Stat\StatDescription;
use Override;

final readonly class Strength extends PrimaryAttribute
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription(
            'Strength increases Attack Power, which boosts physical damage. It also improves Critical Strike chance for melee classes.',
        );
    }
}
