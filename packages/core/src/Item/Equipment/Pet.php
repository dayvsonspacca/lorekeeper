<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment;

use Lorekeeper\Core\Item\EquipmentSlot;
use Override;

final class Pet extends Equipment
{
    #[Override]
    public function slot(): EquipmentSlot
    {
        return EquipmentSlot::Pet;
    }
}
