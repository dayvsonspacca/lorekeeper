<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Support;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Collection;

/**
 * @extends Collection<ItemId, ItemId>
 */
final class ItemIds extends Collection
{
    /** @param ItemId $item */
    protected static function key(mixed $item): ItemId
    {
        return $item;
    }
}
