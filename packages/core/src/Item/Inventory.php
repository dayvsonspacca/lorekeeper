<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use InvalidArgumentException;
use Psl\Collection\Map;
use Psl\Option;

/**
 * @template T of HoldsItem
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
        foreach ($items as $holding) {
            $id = $holding->item->id;
            if (array_key_exists($id->value, $byId)) {
                throw new InvalidArgumentException("Item {$id} is in the inventory more than once.");
            }

            $byId[$id->value] = $holding;
        }

        $this->items = new Map($byId);
    }

    /** @return Option\Option<T> */
    public function find(ItemId $id): Option\Option
    {
        return Option\from_nullable($this->items->get($id->value));
    }
}
