<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment\Weapon;

use Lorekeeper\Core\Item\Equipment\Equipment;
use Lorekeeper\Core\Item\Equipment\Traits\Enchantable;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Override;
use Psl\Option;

final readonly class Weapon extends Equipment implements Enchantable
{
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
        public Option\Option $enhancement,
    ) {
        parent::__construct($id, $name, $level);
    }

    #[Override]
    public function slot(): EquipmentSlot
    {
        return EquipmentSlot::Weapon;
    }
}
