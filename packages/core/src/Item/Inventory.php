<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use InvalidArgumentException;
use Psl\Collection\Map;
use Psl\Option;

/**
 * @template T of Item
 */
abstract readonly class Inventory
{
    /** @var Map<positive-int, T> */
    public Map $items;

    /**
     * @param list<T> $items
     *
     * @throws InvalidArgumentException
     */
    final public function __construct(array $items)
    {
        $byId = [];
        foreach ($items as $item) {
            if (array_key_exists($item->id->value, $byId)) {
                throw new InvalidArgumentException("Item {$item->id} is in the inventory more than once.");
            }

            $byId[$item->id->value] = $item;
        }

        $this->items = new Map($byId);
    }

    /** @return Option\Option<T> */
    public function find(ItemId $id): Option\Option
    {
        return Option\from_nullable($this->items->get($id->value));
    }
}
