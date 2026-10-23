<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Inventory;
use Lorekeeper\Core\Item\ItemId;
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
        foreach ($temporary as $held) {
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
}
