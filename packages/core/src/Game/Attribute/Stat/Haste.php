<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat;

use Lorekeeper\Core\Support\Ratio;
use Override;

final readonly class Haste extends Ratio implements Stat
{
    private const float CAP = 0.5;

    public function effective(): float
    {
        return min($this->value, self::CAP);
    }

    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription(
            'Haste reduces the cooldown on all of your attacks and spells, including Auto Attack, by a certain percentage (hard capped at 50%).',
        );
    }
}
