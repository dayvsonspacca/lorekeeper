<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use Lorekeeper\Core\Item\HoldsItem;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Support\Quantity;

final readonly class HeroItem implements HoldsItem
{
    public function __construct(
        public Item $item,
        public Quantity $quantity = new Quantity(1),
        public bool $equipped = false,
    ) {}
}
