<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Primary;

use Lorekeeper\Core\Game\Attribute\Stat\StatDescription;
use Override;

final readonly class Luck extends PrimaryAttribute
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription(
            'Luck increases your Critical Strike modifier value directly, and may have effects outside of combat.',
        );
    }
}
