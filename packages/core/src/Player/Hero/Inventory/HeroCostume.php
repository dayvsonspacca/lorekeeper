<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero\Inventory;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\Equipment;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Psl\Option;

final class HeroCostume extends HeroSlots
{
    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function wear(Equipment $item): static
    {
        $this->put($item);

        return $this;
    }

    /** @return Option\Option<Equipment> */
    public function worn(EquipmentSlot $slot): Option\Option
    {
        return $this->at($slot);
    }

    public function wears(ItemId $id): bool
    {
        return $this->holds($id);
    }
}
