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

/** @mago-expect lint:too-many-methods */
final class HeroInventory
{
    public readonly Stock $permanent;

    public readonly Stock $temporary;

    public readonly HeroEquipment $equipment;

    public readonly HeroCostume $costume;

    public readonly HeroBank $bank;

    public function __construct(
        public private(set) Quantity $slots,
        Quantity $bankSlots,
        public readonly HeroWallet $wallet = new HeroWallet(),
    ) {
        $this->permanent = new Stock();
        $this->temporary = new Stock();
        $this->equipment = new HeroEquipment($this->permanent);
        $this->costume = new HeroCostume($this->permanent);
        $this->bank = new HeroBank($bankSlots, $this);
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function add(Item $item, Quantity $quantity = new Quantity(1)): static
    {
        $temporary = $item->tags->has(ItemTag::Temporary);
        if (($temporary ? $this->permanent : $this->temporary)->find($item->id)->isSome()) {
            throw new InvalidArgumentException("Item {$item->id} is both kept and temporary.");
        }

        ($temporary ? $this->temporary : $this->permanent)->add($item, $quantity);

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

        $this->holding($item->id)->receive($item, $quantity);

        return $this;
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function take(ItemId $id, Quantity $quantity): static
    {
        $this->holding($id)->take($id, $quantity);

        return $this;
    }

    /** @return Option\Option<Item> */
    public function find(ItemId $id): Option\Option
    {
        return $this->permanent->find($id)->orElse(fn(): Option\Option => $this->temporary->find($id));
    }

    public function quantity(ItemId $id): int
    {
        return $this->holding($id)->quantity($id);
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

    public function resize(Quantity $slots): void
    {
        $this->slots = $slots;
    }

    public function fits(ItemId $id): bool
    {
        return $this->permanent->find($id)->isSome() || !$this->full();
    }

    private function holding(ItemId $id): Stock
    {
        return $this->permanent->find($id)->isSome() ? $this->permanent : $this->temporary;
    }
}
