<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots;

use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\AreaId;

final readonly class Position
{
    public function __construct(
        public MapName $map,
        public AreaId $area,
    ) {}
}
