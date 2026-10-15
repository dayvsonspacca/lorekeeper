<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Requirement;

use Lorekeeper\Core\Item\ItemId;

final readonly class ItemRequirement implements QuestRequirement
{
    public function __construct(
        public ItemId $itemId,
    ) {}
}
