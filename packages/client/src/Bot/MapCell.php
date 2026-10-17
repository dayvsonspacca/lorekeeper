<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot;

use Lorekeeper\Core\World\Map\Cell;

final readonly class MapCell
{
    /** @param list<Spawn> $spawns */
    public function __construct(
        public Cell $cell,
        public array $spawns,
    ) {}
}
