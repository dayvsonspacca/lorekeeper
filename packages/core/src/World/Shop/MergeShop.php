<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Shop;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Inventory;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Psl\Option;

final readonly class MergeShop extends Shop
{
    /**
     * @param Inventory<Item> $inventory
     * @param array<positive-int, list<MergeCost>> $costs
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        ShopId $id,
        ShopName $name,
        public Inventory $inventory,
        private array $costs,
    ) {
        parent::__construct($id, $name);

        foreach ($inventory as $item) {
            if (($costs[$item->id->value] ?? []) === []) {
                throw new InvalidArgumentException("Merging {$item->name} costs nothing.");
            }
        }
    }

    /** @return Option\Option<list<MergeCost>> */
    public function costs(ItemId $id): Option\Option
    {
        return Option\from_nullable($this->costs[$id->value] ?? null);
    }
}
