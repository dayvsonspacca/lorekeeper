<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemDescription;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Item\Type;
use Psl\Option\Option;

final readonly class InventoryItem
{
    public Type\ItemCategory $category;

    /**
     * @param Option<ItemDescription> $description
     * @param Option<ItemPrice> $price
     * @param Option<Gamefile> $file
     * @param Option<Linkage> $link
     * @param Option<EquipmentSlot> $slot
     *
     * @mago-expect lint:excessive-parameter-list
     */
    public function __construct(
        public ItemId $id,
        public ItemName $name,
        public Type\Equipment|Type\Weapon|Type\House|Type\Misc $detail,
        public Option $description,
        public Option $price,
        public Option $file,
        public Option $link,
        public Option $slot,
        public bool $memberOnly,
    ) {
        $this->category = $detail instanceof Type\Weapon ? $detail->parent()->parent() : $detail->parent();
    }
}
