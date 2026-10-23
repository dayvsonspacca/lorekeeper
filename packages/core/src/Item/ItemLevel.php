<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Lorekeeper\Core\Game\Level\Level;
use Override;

final readonly class ItemLevel extends Level
{
    #[Override]
    protected static function maximum(): int
    {
        return 100;
    }
}
