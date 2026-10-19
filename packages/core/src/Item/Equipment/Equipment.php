<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment;

use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\Item;

abstract readonly class Equipment extends Item
{
    abstract public function slot(): EquipmentSlot;
}
