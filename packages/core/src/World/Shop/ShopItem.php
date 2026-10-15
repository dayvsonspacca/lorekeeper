<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Shop;

use Lorekeeper\Core\Item\ItemId;

final readonly class ShopItem
{
    public function __construct(
        public ItemId $itemId,
    ) {}
}
