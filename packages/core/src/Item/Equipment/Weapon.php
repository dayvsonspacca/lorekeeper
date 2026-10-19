<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment;

use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Override;

final readonly class Weapon extends Equipment
{
    public function __construct(
        ItemId $id,
        ItemName $name,
        public WeaponType $type,
    ) {
        parent::__construct($id, $name);
    }

    #[Override]
    public function slot(): EquipmentSlot
    {
        return EquipmentSlot::Weapon;
    }
}
