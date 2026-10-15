<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\World\Monster\MonsterId;

final readonly class MapMonster
{
    public function __construct(
        public MonsterId $monsterId,
    ) {}
}
