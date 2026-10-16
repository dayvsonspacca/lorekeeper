<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command;

use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Server\AreaId;
use Override;

final readonly class LoadShopCommand implements Command
{
    public function __construct(
        private AreaId $areaId,
        private ShopId $shopId,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%loadShop%{$this->areaId}%{$this->shopId}%";
    }
}
