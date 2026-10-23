<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Shop;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Inventory;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemPrice;
use Psl\Option;

final readonly class CurrencyShop extends Shop
{
    /**
     * @param Inventory<Item> $inventory
     * @param array<positive-int, ItemPrice> $prices
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        ShopId $id,
        ShopName $name,
        public Inventory $inventory,
        private array $prices,
    ) {
        parent::__construct($id, $name);

        foreach ($inventory as $item) {
            if (!array_key_exists($item->id->value, $prices)) {
                throw new InvalidArgumentException("{$item->name} is sold without a price.");
            }
        }
    }

    /** @return Option\Option<ItemPrice> */
    public function price(ItemId $id): Option\Option
    {
        return Option\from_nullable($this->prices[$id->value] ?? null);
    }
}
