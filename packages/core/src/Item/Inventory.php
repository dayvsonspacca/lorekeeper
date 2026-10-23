<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Lorekeeper\Core\Support\Collection;

/**
 * @template T of Item
 *
 * @extends Collection<ItemId, T>
 */
class Inventory extends Collection
{
    /** @param T $item */
    final protected static function key(mixed $item): ItemId
    {
        return $item->id;
    }
}
