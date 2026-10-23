<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Misc\Enhancement;

use Lorekeeper\Core\Item\Equipment\Equipment;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;

final class EnhancementItem extends Item
{
    public function __construct(
        ItemId $id,
        ItemName $name,
        public readonly Enhancement $enhancement,
        public readonly EquipmentSlot $slot,
    ) {
        parent::__construct($id, $name, $enhancement->level);
    }

    public function fits(Item $item): bool
    {
        return $item instanceof Equipment && $item->slot() === $this->slot;
    }
}
