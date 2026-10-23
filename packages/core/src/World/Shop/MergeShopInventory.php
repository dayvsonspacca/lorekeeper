<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Shop;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Collection;

/**
 * @extends Collection<ItemId, MergeShopItem>
 */
final class MergeShopInventory extends Collection
{
    /** @param MergeShopItem $item */
    protected static function key(mixed $item): ItemId
    {
        return $item->item->id;
    }
}
