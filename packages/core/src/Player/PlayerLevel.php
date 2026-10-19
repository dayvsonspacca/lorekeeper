<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Progression\Level;
use Override;

final readonly class PlayerLevel extends Level
{
    #[Override]
    protected static function maximum(): int
    {
        return 100;
    }
}
