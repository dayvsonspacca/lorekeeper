<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Server\Event\Inventory\HeldItem;

final readonly class Drops
{
    /** @param array<int, HeldItem> $items */
    public function __construct(
        public array $items = [],
    ) {}

    public function find(ItemId $id): ?HeldItem
    {
        return $this->items[$id->value] ?? null;
    }
}
