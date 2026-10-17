<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot;

use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Server\Event\Map\MonsterPlacement;

final readonly class MapLayout
{
    /** @param list<MonsterPlacement> $placements */
    public function __construct(
        private array $placements = [],
    ) {}

    /** @return list<MonsterPlacement> */
    public function monstersIn(Cell $cell): array
    {
        return array_values(array_filter(
            $this->placements,
            static fn(MonsterPlacement $placement): bool => $placement->cell->value === $cell->value,
        ));
    }

    /** @return list<Cell> */
    public function cells(): array
    {
        $cells = [];
        foreach ($this->placements as $placement) {
            $cells[$placement->cell->value] ??= $placement->cell;
        }

        return array_values($cells);
    }
}
