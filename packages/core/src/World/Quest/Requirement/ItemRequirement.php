<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Requirement;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;

final readonly class ItemRequirement implements QuestRequirement
{
    public function __construct(
        public ItemId $itemId,
        public Quantity $quantity,
    ) {}
}
