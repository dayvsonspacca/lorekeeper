<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Player\PlayerItem;

final readonly class Drops
{
    /** @param array<int, PlayerItem> $items */
    public function __construct(
        public array $items = [],
    ) {}

    public function find(ItemId $id): ?PlayerItem
    {
        return $this->items[$id->value] ?? null;
    }
}
