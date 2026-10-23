<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Support\Quantity;

final readonly class HeldItem
{
    public function __construct(
        public Item $item,
        public Quantity $quantity = new Quantity(1),
        public bool $equipped = false,
        public bool $worn = false,
    ) {}
}
