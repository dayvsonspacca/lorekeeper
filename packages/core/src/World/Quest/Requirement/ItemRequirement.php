<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Requirement;

use Lorekeeper\Core\Item\HoldsItem;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Support\Quantity;

final readonly class ItemRequirement implements QuestRequirement, HoldsItem
{
    public function __construct(
        public Item $item,
        public Quantity $quantity,
    ) {}
}
