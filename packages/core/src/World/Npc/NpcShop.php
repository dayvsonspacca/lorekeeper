<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Npc;

use Lorekeeper\Core\World\Shop\ShopId;

final readonly class NpcShop
{
    public function __construct(
        public ShopId $shopId,
    ) {}
}
