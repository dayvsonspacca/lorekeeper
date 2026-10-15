<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

final readonly class MapLink
{
    public function __construct(
        public MapName $mapName,
    ) {}
}
