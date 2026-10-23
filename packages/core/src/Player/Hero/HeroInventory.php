<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\Equipment;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\Inventory;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Traits\Stackable;
use Lorekeeper\Core\Support\Quantity;
use Psl\Option;

final class HeroInventory
{
    /** @var Inventory<Item> */
    public readonly Inventory $permanent;

    /** @var Inventory<Item> */
    public readonly Inventory $temporary;

    /** @var array<positive-int, Quantity> */
    private array $quantities = [];

    /** @var array<string, ItemId> */
    private array $equipped = [];

    public function __construct(
        public readonly Quantity $slots,
    ) {
        $this->permanent = new Inventory([]);
        $this->temporary = new Inventory([]);
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function add(Item $item, Quantity $quantity = new Quantity(1)): static
    {
        $most = $item instanceof Stackable ? $item->stack->value : 1;
        if ($quantity->value > $most) {
            throw new InvalidArgumentException("{$item->name} stacks up to {$most}, not {$quantity}.");
        }

        $temporary = $item->tags->has(ItemTag::Temporary);
        if (($temporary ? $this->permanent : $this->temporary)->has($item)) {
            throw new InvalidArgumentException("Item {$item->id} is both kept and temporary.");
        }

        ($temporary ? $this->temporary : $this->permanent)->add($item);
        if ($item instanceof Stackable) {
            $this->quantities[$item->id->value] = $quantity;
        }

        return $this;
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function equip(Equipment $item): static
    {
        if (!$this->permanent->has($item)) {
            throw new InvalidArgumentException("Item {$item->id} is not kept, so it cannot be equipped.");
        }

        $this->equipped[$item->slot()->value] = $item->id;

        return $this;
    }

    /** @return Option\Option<Equipment> */
    public function equipped(EquipmentSlot $slot): Option\Option
    {
        return Option\from_nullable($this->equipped[$slot->value] ?? null)
            ->andThen(fn(ItemId $id): Option\Option => $this->permanent->find($id))
            ->andThen(static fn(Item $item): Option\Option => $item instanceof Equipment
                ? Option\some($item)
                : Option\none());
    }

    /** @return Option\Option<Item> */
    public function find(ItemId $id): Option\Option
    {
        return $this->permanent->find($id)->orElse(fn(): Option\Option => $this->temporary->find($id));
    }

    public function quantity(ItemId $id): int
    {
        return $this
            ->find($id)
            ->map(fn(Item $item): int => $item instanceof Stackable
                ? $this->quantities[$item->id->value]->value ?? 1
                : 1)
            ->unwrapOr(0);
    }

    /** @return non-negative-int */
    public function free(): int
    {
        return max(0, $this->slots->value - count($this->permanent));
    }

    public function full(): bool
    {
        return count($this->permanent) >= $this->slots->value;
    }
}
