<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment;

use Lorekeeper\Core\Item\Equipment\Traits\Enchantable;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Override;
use Psl\Option;

final class Helm extends Equipment implements Enchantable
{
    /** @param Option\Option<Enhancement> $enhancement */
    public function __construct(
        ItemId $id,
        ItemName $name,
        ItemLevel $level,
        public private(set) Option\Option $enhancement,
    ) {
        parent::__construct($id, $name, $level);
    }

    #[Override]
    public function enchant(Enhancement $enhancement): void
    {
        $this->enhancement = Option\some($enhancement);
    }

    #[Override]
    public function slot(): EquipmentSlot
    {
        return EquipmentSlot::Helm;
    }
}
