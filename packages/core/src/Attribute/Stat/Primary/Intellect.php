<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Primary;

use Lorekeeper\Core\Attribute\Stat\StatDescription;
use Override;

final readonly class Intellect extends PrimaryAttribute
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription(
            'Intellect increases Spell Power, which boosts magical damage. It also improves Critical Strike chance for caster classes.',
        );
    }
}
