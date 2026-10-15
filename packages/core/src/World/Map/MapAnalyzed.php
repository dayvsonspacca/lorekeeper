<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\Asset\Gamefile;

final readonly class MapAnalyzed
{
    public function __construct(
        public Gamefile $gamefile,
    ) {}
}
