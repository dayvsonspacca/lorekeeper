<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Lorekeeper\Core\Progression\Level;
use Override;

final readonly class ItemLevel extends Level
{
    #[Override]
    protected static function maximum(): int
    {
        return 100;
    }
}
