<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Shop;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;

final readonly class MergeCost
{
    public function __construct(
        public ItemId $itemId,
        public Quantity $quantity,
    ) {}
}
