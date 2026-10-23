<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero\Inventory;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\Equipment;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\Stock;
use Psl\Option;

abstract class HeroSlots
{
    /** @var array<string, ItemId> */
    private array $slots = [];

    final public function __construct(
        private readonly Stock $kept,
    ) {}

    /** @throws InvalidArgumentException */
    protected function put(Equipment $item): void
    {
        if ($this->kept->find($item->id)->isNone()) {
            throw new InvalidArgumentException("Item {$item->id} is not kept, so it cannot be put on.");
        }

        $this->slots[$item->slot()->value] = $item->id;
    }

    /** @return Option\Option<Equipment> */
    protected function at(EquipmentSlot $slot): Option\Option
    {
        return Option\from_nullable($this->slots[$slot->value] ?? null)
            ->andThen(fn(ItemId $id): Option\Option => $this->kept->find($id))
            ->andThen(static fn(Item $item): Option\Option => $item instanceof Equipment
                ? Option\some($item)
                : Option\none());
    }

    protected function holds(ItemId $id): bool
    {
        return $this->kept
            ->find($id)
            ->andThen(static fn(Item $item): Option\Option => $item instanceof Equipment
                ? Option\some($item)
                : Option\none())
            ->isSomeAnd(fn(Equipment $item): bool => $this->at($item->slot())->isSomeAnd(
                static fn(Equipment $on): bool => $on === $item,
            ));
    }
}
