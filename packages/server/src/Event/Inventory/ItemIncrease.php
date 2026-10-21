<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;

final readonly class ItemIncrease
{
    public function __construct(
        public ItemId $item,
        public Quantity $quantity,
        public ?Quantity $held = null,
    ) {}
}
