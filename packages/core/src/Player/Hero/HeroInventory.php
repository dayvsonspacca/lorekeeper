<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Inventory;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Support\Quantity;
use Psl\Option\Option;

final readonly class HeroInventory
{
    /**
     * @param Inventory<HeroItem> $permanent
     * @param Inventory<HeroItem> $temporary
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public Inventory $permanent,
        public Quantity $slots,
        public Inventory $temporary = new Inventory([]),
    ) {
        foreach ($permanent as $held) {
            if ($held->item->tags->has(ItemTag::Temporary)) {
                throw new InvalidArgumentException("Item {$held->item->id} is temporary but kept.");
            }
        }

        foreach ($temporary as $held) {
            if (!$held->item->tags->has(ItemTag::Temporary)) {
                throw new InvalidArgumentException("Item {$held->item->id} is not temporary but held as one.");
            }

            if ($permanent->has($held)) {
                throw new InvalidArgumentException("Item {$held->item->id} is both kept and temporary.");
            }
        }
    }

    /** @return Option<HeroItem> */
    public function find(ItemId $id): Option
    {
        return $this->permanent->find($id)->orElse(fn(): Option => $this->temporary->find($id));
    }

    public function quantity(ItemId $id): int
    {
        return $this
            ->find($id)
            ->map(static fn(HeroItem $held): int => $held->quantity->value)
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
