<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Shop;

final readonly class CurrencyShop extends Shop
{
    public function __construct(
        ShopId $id,
        ShopName $name,
        public CurrencyShopInventory $inventory,
    ) {
        parent::__construct($id, $name);
    }
}
