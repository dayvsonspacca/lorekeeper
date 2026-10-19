<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment;

use Lorekeeper\Core\Item\EquipmentSlot;
use Override;

final readonly class Cape extends Equipment
{
    #[Override]
    public function slot(): EquipmentSlot
    {
        return EquipmentSlot::Cape;
    }
}
