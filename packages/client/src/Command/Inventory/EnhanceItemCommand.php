<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Inventory;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Server\AreaId;
use Override;

final readonly class EnhanceItemCommand implements Command
{
    public function __construct(
        private AreaId $area,
        private ItemId $item,
        private ItemId $enhancement,
        private ShopId $shop,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%enhanceItemShop%{$this->area}%{$this->item}%{$this->enhancement}%{$this->shop}%";
    }
}
