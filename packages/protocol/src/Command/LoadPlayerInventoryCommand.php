<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Command;

use Lorekeeper\Protocol\AreaId;
use Lorekeeper\Protocol\SocketId;
use Override;

final readonly class LoadPlayerInventoryCommand implements Command
{
    public function __construct(
        private AreaId $areaId,
        private SocketId $socketId,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%retrieveInventory%{$this->areaId}%{$this->socketId}%";
    }
}
