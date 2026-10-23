<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Countable;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use Lorekeeper\Core\Item\Traits\Stackable;
use Lorekeeper\Core\Support\Quantity;
use Psl\Option;

/**
 * @implements IteratorAggregate<array-key, Item>
 */
final class Stock implements IteratorAggregate, Countable
{
    /** @var Inventory<Item> */
    private readonly Inventory $items;

    /** @var array<positive-int, Quantity> */
    private array $quantities = [];

    public function __construct()
    {
        $this->items = new Inventory([]);
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function add(Item $item, Quantity $quantity = new Quantity(1)): static
    {
        if ($this->find($item->id)->isSome()) {
            throw new InvalidArgumentException("{$item->name} is already held.");
        }

        $this->hold($item, $quantity->value);
        $this->items->add($item);

        return $this;
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function receive(Item $item, Quantity $quantity = new Quantity(1)): static
    {
        if ($this->find($item->id)->isNone()) {
            return $this->add($item, $quantity);
        }

        $this->hold($item, $this->quantity($item->id) + $quantity->value);

        return $this;
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function take(ItemId $id, Quantity $quantity): static
    {
        $item = $this->find($id)->unwrapOrElse(
            static fn(): never => throw new InvalidArgumentException("Item {$id} is not held, so none can be taken."),
        );

        $left = $this->quantity($id) - $quantity->value;
        if ($left <= 0) {
            return $this->remove($id);
        }

        $this->hold($item, $left);

        return $this;
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function remove(ItemId $id): static
    {
        $this->items->remove($id);
        unset($this->quantities[$id->value]);

        return $this;
    }

    /** @return Option\Option<Item> */
    public function find(ItemId $id): Option\Option
    {
        return $this->items->find($id);
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

    /** @return Iterator<array-key, Item> */
    public function getIterator(): Iterator
    {
        return $this->items->getIterator();
    }

    public function count(): int
    {
        return count($this->items);
    }

    /** @throws InvalidArgumentException */
    private function hold(Item $item, int $total): void
    {
        $most = $item instanceof Stackable ? $item->stack->value : 1;
        if ($total > $most) {
            throw new InvalidArgumentException("{$item->name} stacks up to {$most}, not {$total}.");
        }

        if ($item instanceof Stackable) {
            $this->quantities[$item->id->value] = new Quantity($total);
        }
    }
}
