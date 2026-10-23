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

final class HeroEquipment
{
    /** @var array<string, ItemId> */
    private array $worn = [];

    public function __construct(
        private readonly Stock $kept,
    ) {}

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function equip(Equipment $item): static
    {
        if ($this->kept->find($item->id)->isNone()) {
            throw new InvalidArgumentException("Item {$item->id} is not kept, so it cannot be equipped.");
        }

        $this->worn[$item->slot()->value] = $item->id;

        return $this;
    }

    public function wears(ItemId $id): bool
    {
        return $this->kept
            ->find($id)
            ->andThen(static fn(Item $item): Option\Option => $item instanceof Equipment
                ? Option\some($item)
                : Option\none())
            ->isSomeAnd(fn(Equipment $item): bool => $this->equipped($item->slot())->isSomeAnd(
                static fn(Equipment $worn): bool => $worn === $item,
            ));
    }

    /** @return Option\Option<Equipment> */
    public function equipped(EquipmentSlot $slot): Option\Option
    {
        return Option\from_nullable($this->worn[$slot->value] ?? null)
            ->andThen(fn(ItemId $id): Option\Option => $this->kept->find($id))
            ->andThen(static fn(Item $item): Option\Option => $item instanceof Equipment
                ? Option\some($item)
                : Option\none());
    }
}
