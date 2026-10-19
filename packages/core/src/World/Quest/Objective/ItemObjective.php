<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Objective;

use Lorekeeper\Core\Item\HoldsItem;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Support\Quantity;

final readonly class ItemObjective implements QuestObjective, HoldsItem
{
    public function __construct(
        public Item $item,
        public Quantity $quantity,
    ) {}
}
