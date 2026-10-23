<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Game\Level\Level;
use Override;

final readonly class PlayerLevel extends Level
{
    #[Override]
    protected static function maximum(): int
    {
        return 100;
    }
}
