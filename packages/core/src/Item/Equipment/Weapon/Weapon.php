<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment\Weapon;

use Lorekeeper\Core\Item\Equipment\Enchantable;
use Lorekeeper\Core\Item\Equipment\Equipment;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Override;
use Psl\Option;

final readonly class Weapon extends Equipment
{
    use Enchantable;

    /**
     * @param Option\Option<Enhancement> $enhancement
     *
     * @mago-expect lint:excessive-parameter-list
     */
    public function __construct(
        ItemId $id,
        ItemName $name,
        ItemLevel $level,
        public WeaponType $type,
        public WeaponDamage $damage,
        Option\Option $enhancement,
    ) {
        parent::__construct($id, $name, $level);
        $this->enhancement = $enhancement;
    }

    #[Override]
    public function slot(): EquipmentSlot
    {
        return EquipmentSlot::Weapon;
    }
}
