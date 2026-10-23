<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment;

use Lorekeeper\Core\Item\Equipment\Traits\GrantsBoosts;
use Lorekeeper\Core\Item\EquipmentSlot;
use Override;

final class Armor extends Equipment implements GrantsBoosts
{
    #[Override]
    public function slot(): EquipmentSlot
    {
        return EquipmentSlot::Armor;
    }
}
