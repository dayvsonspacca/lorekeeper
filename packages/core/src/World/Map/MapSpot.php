<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

final readonly class MapSpot
{
    public function __construct(
        public MapCell $cell,
        public MapPad $pad,
    ) {}
}
