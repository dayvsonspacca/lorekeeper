<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Objective;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;

final readonly class ItemObjective implements QuestObjective
{
    public function __construct(
        public ItemId $itemId,
        public Quantity $quantity,
    ) {}
}
