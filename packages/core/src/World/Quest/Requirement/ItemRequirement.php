<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Requirement;

use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Support\Quantity;

final readonly class ItemRequirement implements QuestRequirement
{
    public function __construct(
        public Item $item,
        public Quantity $quantity,
    ) {}
}
