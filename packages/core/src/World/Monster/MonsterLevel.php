<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Monster;

use Lorekeeper\Core\Progression\Level;
use Override;

final readonly class MonsterLevel extends Level
{
    #[Override]
    protected static function maximum(): int
    {
        return 255;
    }
}
