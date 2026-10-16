<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Shop;

use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\World\Shop\MergeCost;

final readonly class ShopLoadedCost
{
    public function __construct(
        public MergeCost $cost,
        public ItemName $name,
    ) {}
}
