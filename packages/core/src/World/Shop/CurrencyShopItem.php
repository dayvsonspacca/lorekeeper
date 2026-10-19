<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Shop;

use Lorekeeper\Core\Item\HoldsItem;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemPrice;

final readonly class CurrencyShopItem implements HoldsItem
{
    public function __construct(
        public Item $item,
        public ItemPrice $price,
    ) {}
}
