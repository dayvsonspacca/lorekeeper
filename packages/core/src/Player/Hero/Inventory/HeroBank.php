<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero\Inventory;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Stock;
use Lorekeeper\Core\Support\Quantity;
use Psl\Option;

final class HeroBank
{
    public readonly Stock $items;

    public function __construct(
        public readonly Quantity $slots,
        private readonly HeroInventory $inventory,
    ) {
        $this->items = new Stock();
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function add(Item $item, Quantity $quantity = new Quantity(1)): static
    {
        if ($item->tags->has(ItemTag::Temporary)) {
            throw new InvalidArgumentException("{$item->name} is temporary, so it cannot be banked.");
        }

        if ($item->tags->has(ItemTag::CannotBeBanked)) {
            throw new InvalidArgumentException("{$item->name} cannot be banked.");
        }

        $this->items->add($item, $quantity);

        return $this;
    }

    /** @return Option\Option<Item> */
    public function find(ItemId $id): Option\Option
    {
        return $this->items->find($id);
    }

    public function quantity(ItemId $id): int
    {
        return $this->items->quantity($id);
    }

    /** @return non-negative-int */
    public function free(): int
    {
        return max(0, $this->slots->value - count($this->items));
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function deposit(ItemId $id): static
    {
        $item = $this->inventory
            ->permanent
            ->find($id)
            ->unwrapOrElse(
                static fn(): never => throw new InvalidArgumentException(
                    "Item {$id} is not kept, so it cannot be banked.",
                ),
            );

        if ($this->inventory->equipment->equips($id)) {
            throw new InvalidArgumentException("{$item->name} is equipped, so it cannot be banked.");
        }

        if ($this->free() === 0) {
            throw new InvalidArgumentException("The bank has no free slot for {$item->name}.");
        }

        $quantity = new Quantity($this->inventory->quantity($id));
        $this->add($item, $quantity);
        $this->inventory->take($id, $quantity);

        return $this;
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function withdraw(ItemId $id): static
    {
        $item = $this->find($id)->unwrapOrElse(
            static fn(): never => throw new InvalidArgumentException("Item {$id} is not in the bank."),
        );

        if ($this->inventory->full()) {
            throw new InvalidArgumentException("The bag has no free slot for {$item->name}.");
        }

        $this->inventory->add($item, new Quantity($this->quantity($id)));
        $this->items->remove($id);

        return $this;
    }
}
