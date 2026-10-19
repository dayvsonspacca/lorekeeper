<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use InvalidArgumentException;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Psl\Option\Option;

final readonly class PlayerInventory
{
    /** @throws InvalidArgumentException */
    public function __construct(
        public PlayerItems $permanent,
        public Quantity $slots,
        public PlayerItems $temporary = new PlayerItems([]),
    ) {
        foreach ($temporary->items->keys() as $id) {
            if ($permanent->items->contains($id)) {
                throw new InvalidArgumentException("Item {$id} is both kept and temporary.");
            }
        }
    }

    /** @return Option<PlayerItem> */
    public function find(ItemId $id): Option
    {
        return $this->permanent->find($id)->orElse(fn(): Option => $this->temporary->find($id));
    }

    public function quantity(ItemId $id): int
    {
        return $this
            ->find($id)
            ->map(static fn(PlayerItem $held): int => $held->quantity->value)
            ->unwrapOr(0);
    }
}
