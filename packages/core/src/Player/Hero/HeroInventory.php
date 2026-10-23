<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use InvalidArgumentException;
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

    public readonly HeroEquipment $equipment;

    /** @var array<positive-int, Quantity> */
    private array $quantities = [];

    public function __construct(
        public readonly Quantity $slots,
    ) {
        $this->permanent = new Inventory([]);
        $this->temporary = new Inventory([]);
        $this->equipment = new HeroEquipment($this->permanent);
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function add(Item $item, Quantity $quantity = new Quantity(1)): static
    {
        self::stack($item, $quantity->value);

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
    public function receive(Item $item, Quantity $quantity = new Quantity(1)): static
    {
        $held = $this->quantity($item->id);
        if ($held === 0) {
            return $this->add($item, $quantity);
        }

        $total = self::stack($item, $held + $quantity->value);
        if ($item instanceof Stackable) {
            $this->quantities[$item->id->value] = $total;
        }

        return $this;
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function take(ItemId $id, Quantity $quantity): static
    {
        $left = $this->quantity($id) - $quantity->value;
        if ($this->find($id)->isNone()) {
            throw new InvalidArgumentException("Item {$id} is not held, so none can be taken.");
        }

        if ($left > 0) {
            $this->quantities[$id->value] = new Quantity($left);

            return $this;
        }

        ($this->permanent->find($id)->isSome() ? $this->permanent : $this->temporary)->remove($id);
        unset($this->quantities[$id->value]);

        return $this;
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

    public function fits(ItemId $id): bool
    {
        return $this->permanent->find($id)->isSome() || !$this->full();
    }

    /** @throws InvalidArgumentException */
    private static function stack(Item $item, int $total): Quantity
    {
        $most = $item instanceof Stackable ? $item->stack->value : 1;
        if ($total > $most) {
            throw new InvalidArgumentException("{$item->name} stacks up to {$most}, not {$total}.");
        }

        return new Quantity($total);
    }
}
