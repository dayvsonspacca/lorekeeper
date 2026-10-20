<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Power;

use Lorekeeper\Core\Attribute\Stat\StatDescription;
use Override;

final readonly class SpellPower extends Power
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription('Spell Power increases the effectiveness of your magical damage attacks.');
    }
}
