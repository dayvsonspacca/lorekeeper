<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

final readonly class PlayerInventory
{
    /** @param list<PlayerItem> $items */
    public function __construct(
        public array $items,
    ) {}
}
